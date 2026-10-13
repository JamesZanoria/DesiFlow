<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerOrderingController extends Controller
{
    private const CART_KEY = 'customer_order_cart';

    public function menu()
    {
        $menuItems = MenuItem::query()
            ->where('is_available', true)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get();

        return view('orders.menu', [
            'menuItems' => $menuItems,
            'categories' => $menuItems->pluck('category')->unique()->values(),
        ]);
    }

    public function products(Request $request)
    {
        $products = MenuItem::query()
            ->where('is_available', true)
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->map(fn (MenuItem $item) => [
                'id' => $item->menu_item_id,
                'name' => $item->item_name,
                'price' => $item->price,
                'image' => $item->image_url ? route('customer.menu-image', ['filename' => $item->image_url]) : null,
                'category' => $item->category,
            ]);

        return response()->json(['products' => $products]);
    }

    public function cart(Request $request)
    {
        return response()->json($this->cartPayload($request));
    }

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:menu_items,menu_item_id'],
        ]);

        $menuItem = MenuItem::query()
            ->whereKey($validated['product_id'])
            ->where('is_available', true)
            ->first();

        if (! $menuItem) {
            throw ValidationException::withMessages([
                'product_id' => 'This menu item is currently unavailable.',
            ]);
        }

        $cart = $request->session()->get(self::CART_KEY, []);
        $id = (string) $menuItem->menu_item_id;
        $cart[$id] = min(($cart[$id] ?? 0) + 1, 99);
        $request->session()->put(self::CART_KEY, $cart);

        return response()->json(['success' => true] + $this->cartPayload($request));
    }

    public function removeFromCart(Request $request)
    {
        $validated = $request->validate(['product_id' => ['required', 'integer']]);
        $cart = $request->session()->get(self::CART_KEY, []);
        unset($cart[(string) $validated['product_id']]);
        $request->session()->put(self::CART_KEY, $cart);

        return response()->json(['success' => true] + $this->cartPayload($request));
    }

    public function updateCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['plus', 'minus'])],
        ]);

        $cart = $request->session()->get(self::CART_KEY, []);
        $id = (string) $validated['product_id'];
        if (isset($cart[$id])) {
            $quantity = $cart[$id] + ($validated['action'] === 'plus' ? 1 : -1);
            if ($quantity < 1) {
                unset($cart[$id]);
            } else {
                $cart[$id] = min($quantity, 99);
            }
        }
        $request->session()->put(self::CART_KEY, $cart);

        return response()->json(['success' => true] + $this->cartPayload($request));
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'order_type' => ['required', Rule::in(['dine-in', 'takeout'])],
            'table_number' => ['nullable', 'required_if:order_type,dine-in', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = $request->session()->get(self::CART_KEY, []);
        if ($cart === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty. Add an item before placing your order.',
            ]);
        }

        $checkout = DB::transaction(function () use ($cart, $validated) {
            $requestedIds = array_map('intval', array_keys($cart));
            $menuItems = MenuItem::query()
                ->whereIn('menu_item_id', $requestedIds)
                ->where('is_available', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('menu_item_id');

            if ($menuItems->count() !== count($requestedIds)) {
                throw ValidationException::withMessages([
                    'cart' => 'A menu item in your cart is no longer available. Refresh the menu and try again.',
                ]);
            }

            $totalCents = collect($cart)->reduce(function (int $total, int $quantity, string|int $menuItemId) use ($menuItems) {
                $unitCents = (int) round((float) $menuItems->get((int) $menuItemId)->price * 100);
                return $total + ($unitCents * $quantity);
            }, 0);

            $orderNumber = $this->newOrderNumber();
            $pickupCode = $this->newPickupCode();

            $order = Order::create([
                'cashier_id' => null,
                'order_number' => $orderNumber,
                'code' => $pickupCode,
                'table_number' => $validated['order_type'] === 'dine-in' ? $validated['table_number'] : null,
                'order_type' => $validated['order_type'],
                'status' => 'unapproved',
                'total_amount' => $totalCents / 100,
                'created_at' => now(),
            ]);

            foreach ($cart as $menuItemId => $quantity) {
                $unitCents = (int) round((float) $menuItems->get((int) $menuItemId)->price * 100);
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'menu_item_id' => (int) $menuItemId,
                    'quantity' => $quantity,
                    'total' => ($unitCents * $quantity) / 100,
                ]);
            }

            return [
                'order_number' => $orderNumber,
                'order_id' => $order->order_id,
                'code' => $pickupCode,
            ];
        });

        $request->session()->forget(self::CART_KEY);

        return response()->json([
            'success' => true,
            ...$checkout,
            'status' => 'unapproved',
        ], 201);
    }

    public function details(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'code' => ['required', 'digits:6'],
        ]);

        $order = $this->findCustomerOrder($validated['order_number'], $validated['code']);

        return response()->json(['success' => true, 'order' => $this->orderPayload($order)]);
    }

    public function status(Request $request, string $orderNumber)
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $order = $this->findCustomerOrder($orderNumber, $validated['code']);

        return response()->json([
            'success' => true,
            'order' => [
                'order_number' => $order->order_number,
                'code' => $order->code,
                'status' => $order->status,
                'updated_at' => $order->created_at?->toIso8601String(),
            ],
            'items' => $order->orderItems->map(fn (OrderItem $item) => [
                'name' => $item->menuItem?->item_name ?? 'Item',
                'quantity' => $item->quantity,
            ]),
        ]);
    }

    private function cartPayload(Request $request): array
    {
        $cart = $request->session()->get(self::CART_KEY, []);
        if ($cart === []) {
            return ['cart' => [], 'total' => 0];
        }

        $menuItems = MenuItem::query()
            ->whereIn('menu_item_id', array_map('intval', array_keys($cart)))
            ->where('is_available', true)
            ->get()
            ->keyBy('menu_item_id');

        $payload = [];
        $totalCents = 0;
        foreach ($cart as $id => $quantity) {
            $item = $menuItems->get((int) $id);
            if (! $item) {
                continue;
            }

            $priceCents = (int) round((float) $item->price * 100);
            $payload[(string) $item->menu_item_id] = [
                'id' => $item->menu_item_id,
                'name' => $item->item_name,
                'price' => $priceCents / 100,
                'image' => $item->image_url ? route('customer.menu-image', ['filename' => $item->image_url]) : null,
                'quantity' => $quantity,
            ];
            $totalCents += $priceCents * $quantity;
        }

        return ['cart' => $payload, 'total' => $totalCents / 100];
    }

    private function findCustomerOrder(string $orderNumber, string $code): Order
    {
        return Order::with('orderItems.menuItem')
            ->where('order_number', $orderNumber)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function orderPayload(Order $order): array
    {
        return [
            'order_id' => $order->order_id,
            'order_number' => $order->order_number,
            'code' => $order->code,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'order_type' => $order->order_type,
            'table_number' => $order->table_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $order->orderItems->map(fn (OrderItem $item) => [
                'name' => $item->menuItem?->item_name ?? 'Item',
                'quantity' => $item->quantity,
                'total' => $item->total,
            ]),
        ];
    }

    private function newOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function newPickupCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
