<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CashierController extends Controller
{
    private const CART_KEY = 'cashier_pos_cart_v2';

    private const ACTIVE_STATUSES = ['pending', 'preparing', 'ready'];

    
    public function index(Request $request)
    {
        $cashierId = $request->user()->user_id;
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $processedToday = Order::query()
            ->where('cashier_id', $cashierId)
            ->whereHas('payment', fn ($query) => $query
                ->where('transaction_status', 'completed')
                ->whereBetween('created_at', [$startOfDay, $endOfDay]))
            ->count();

        $activeOrderCount = Order::query()
            ->where('cashier_id', $cashierId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->count();

        $pendingOrderCount = Order::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'pending')
            ->count();

        $awaitingPaymentCount = Order::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'awaiting_payment')
            ->count();

        $unpaidOrders = Order::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'awaiting_payment')
            ->with('orderItems')
            ->orderBy('created_at')
            ->limit(8)
            ->get();

<<<<<<< HEAD
        return view('cashier.index', [
            'processedToday' => $processedToday,
            'activeOrderCount' => $activeOrderCount,
            'pendingOrderCount' => $pendingOrderCount,
            'awaitingPaymentCount' => $awaitingPaymentCount,
            'unpaidOrders' => $unpaidOrders,
=======
        $orders = Order::query()
            ->where('cashier_id', $cashierId)
            ->whereIn('status', ['unapproved', 'pending'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Cashier/Index', [
            'processedToday' => $processedToday,
            'activeOrders' => $activeOrders,
            'pendingOrderCount' => $pendingOrderCount,
            'awaitingPaymentCount' => $awaitingPaymentCount,
            'unpaidOrders' => $unpaidOrders,
            'unapprovedOrders' => $orders->where('status', 'unapproved')->values(),
            'pendingOrders' => $orders->where('status', 'pending')->values(),
>>>>>>> eda5337b817a274bea8215e705e0f427f0a6bdd6
        ]);
    }

    public function activeOrders(Request $request)
    {
        $orders = Order::query()
            ->with(['orderItems.menuItem', 'orderItems.addOns', 'payment'])
            ->where('cashier_id', $request->user()->user_id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('created_at')
            ->get();

        return view('cashier.orders', ['orders' => $orders]);
    }

    public function dashboardOrders(Request $request, string $area)
    {
        abort_unless(in_array($area, ['paid-today', 'pending', 'active', 'awaiting-payment'], true), 404);

        $orders = Order::query()
            ->with(['orderItems.menuItem', 'orderItems.addOns', 'payment'])
            ->where('cashier_id', $request->user()->user_id)
            ->when($area === 'paid-today', fn ($query) => $query->whereHas('payment', fn ($paymentQuery) => $paymentQuery
                ->where('transaction_status', 'completed')
                ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])))
            ->when($area === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($area === 'active', fn ($query) => $query->whereIn('status', self::ACTIVE_STATUSES))
            ->when($area === 'awaiting-payment', fn ($query) => $query->where('status', 'awaiting_payment'))
            ->orderByDesc('created_at')
            ->orderByDesc('order_id')
            ->paginate(10);

        return response()->json([
            'orders' => $orders->getCollection()->map(fn (Order $order) => [
                'number' => $order->order_number,
                'status' => $order->status,
                'type' => $order->order_type,
                'table' => $order->table_number,
                'created_at' => $order->created_at?->format('M j, Y g:i A'),
                'total' => $order->total_amount,
                'payment_method' => $order->payment?->payment_method,
                'items' => $order->orderItems->map(fn ($item) => [
                    'name' => $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item',
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'add_ons' => $item->addOns->map(fn ($addOn) => [
                        'name' => $addOn->add_on_name_snapshot,
                        'quantity' => $item->quantity,
                        'total' => $addOn->total,
                    ])->values(),
                ])->values(),
            ])->values(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function quickView(Request $request, Order $order)
    {
        abort_unless(
            (int) $order->cashier_id === (int) $request->user()->user_id
                && in_array($order->status, self::ACTIVE_STATUSES, true),
            404
        );

        $order->load(['orderItems.menuItem', 'orderItems.addOns']);

        return response()->json([
            'order' => [
                'number' => $order->order_number,
                'status' => $order->status,
                'type' => $order->order_type,
                'table' => $order->table_number,
                'customer' => $order->customer_name,
                'created_at' => $order->created_at?->format('M j, Y g:i A'),
                'total' => $order->total_amount,
                'items' => $order->orderItems->map(fn ($item) => [
                    'name' => $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item',
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'add_ons' => $item->addOns->map(fn ($addOn) => [
                        'name' => $addOn->add_on_name_snapshot,
                        'unit_price' => $addOn->unit_price,
                    ]),
                ]),
            ],
        ]);
    }

    public function menu(Request $request)
    {
        $categories = MenuItem::query()
            ->where('is_available', true)
            ->orderBy('category')
            ->pluck('category')
            ->unique()
            ->values();
        $category = $request->query('category');
        $category = is_string($category) ? $category : null;
        $search = $request->query('search', '');
        $search = is_string($search) ? trim($search) : '';

        $menuItems = MenuItem::query()
            ->with(['addOns' => fn ($query) => $query->where('add_ons.is_available', true)])
            ->where('is_available', true)
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($search !== '', fn ($query) => $query->where('item_name', 'like', '%' . $search . '%'))
            ->orderBy('category')
            ->orderBy('item_name')
            ->get();

        return view('cashier.menu', [
            'categories' => $categories,
            'menuItems' => $menuItems,
            'selectedCategory' => $category,
            'search' => $search,
            ...$this->cartPayload($request),
        ]);
    }

    public function cart(Request $request)
    {
        return view('cashier.cart', $this->cartPayload($request));
    }

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:menu_items,menu_item_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'addon_ids' => ['sometimes', 'array', 'max:10'],
            'addon_ids.*' => ['integer', 'distinct'],
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

        $addOnIds = collect($validated['addon_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values();
        $availableAddOns = $menuItem->addOns()
            ->where('add_ons.is_available', true)
            ->whereIn('add_ons.add_on_id', $addOnIds)
            ->get();

        if ($availableAddOns->count() !== $addOnIds->count()) {
            throw ValidationException::withMessages([
                'addon_ids' => 'One or more selected add-ons are unavailable for this menu item.',
            ]);
        }

        $cart = $request->session()->get(self::CART_KEY, []);
        $key = $this->cartLineKey((int) $menuItem->menu_item_id, $addOnIds->all());
        $existingQuantity = (int) ($cart[$key]['quantity'] ?? 0);
        $cart[$key] = [
            'menu_item_id' => (int) $menuItem->menu_item_id,
            'addon_ids' => $addOnIds->all(),
            'quantity' => min($existingQuantity + (int) $validated['quantity'], 99),
        ];
        $request->session()->put(self::CART_KEY, $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload($request));
        }

        return redirect()->route('cashier.cart')->with('status', "{$menuItem->item_name} added to the order.");
    }

    public function updateCart(Request $request)
    {
        $validated = $request->validate([
            'cart_key' => ['required', 'string', 'max:100'],
            'action' => ['required', Rule::in(['plus', 'minus', 'remove'])],
        ]);

        $cart = $request->session()->get(self::CART_KEY, []);
        $key = $validated['cart_key'];

        if (isset($cart[$key])) {
            if ($validated['action'] === 'remove') {
                unset($cart[$key]);
            } else {
                $quantity = (int) ($cart[$key]['quantity'] ?? 0) + ($validated['action'] === 'plus' ? 1 : -1);
                if ($quantity < 1) {
                    unset($cart[$key]);
                } else {
                    $cart[$key]['quantity'] = min($quantity, 99);
                }
            }
        }

        $request->session()->put(self::CART_KEY, $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload($request));
        }

        return redirect()->route('cashier.cart');
    }

    public function storeOrder(Request $request)
    {
        $orderType = $request->input('order_type');
        $tableNumber = $request->input('table_number');
        $request->merge([
            'order_type' => is_string($orderType) ? strtolower(trim($orderType)) : $orderType,
            'table_number' => is_string($tableNumber)
                ? (trim($tableNumber) === '' ? null : trim($tableNumber))
                : $tableNumber,
        ]);

        $validated = $request->validate([
            'order_type' => ['required', 'string', 'filled', Rule::in(['dine-in', 'takeout'])],
            'table_number' => ['nullable', 'required_if:order_type,dine-in', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = $request->session()->get(self::CART_KEY, []);
        if ($cart === []) {
            throw ValidationException::withMessages([
                'cart' => 'The order is empty. Add at least one menu item before checkout.',
            ]);
        }

        $order = DB::transaction(function () use ($cart, $validated, $request) {
            $requestedIds = collect($cart)
                ->map(fn ($line) => (int) ($line['menu_item_id'] ?? 0))
                ->unique()
                ->values();
            $menuItems = MenuItem::query()
                ->whereIn('menu_item_id', $requestedIds)
                ->where('is_available', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('menu_item_id');

            if ($menuItems->count() !== $requestedIds->count()) {
                throw ValidationException::withMessages([
                    'cart' => 'A menu item in this order is no longer available. Review the menu and try again.',
                ]);
            }

            $preparedLines = [];
            $totalCents = 0;
            foreach ($cart as $key => $line) {
                $menuItemId = (int) ($line['menu_item_id'] ?? 0);
                $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);
                $addOnIds = collect($line['addon_ids'] ?? [])
                    ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))
                    ->filter(fn ($id) => $id !== false && $id > 0)
                    ->unique()
                    ->sort()
                    ->values();

                if ($quantity === false || $quantity < 1 || $quantity > 99 || $menuItemId < 1) {
                    throw ValidationException::withMessages([
                        'cart' => 'An item quantity is invalid. Review the order and try again.',
                    ]);
                }

                $menuItem = $menuItems->get($menuItemId);
                $addOns = $menuItem->addOns()
                    ->where('add_ons.is_available', true)
                    ->whereIn('add_ons.add_on_id', $addOnIds)
                    ->lockForUpdate()
                    ->get();

                if ($addOns->count() !== $addOnIds->count()) {
                    throw ValidationException::withMessages([
                        'cart' => 'An add-on in this order is no longer available. Review the order and try again.',
                    ]);
                }

                $itemCents = $this->moneyToCents($menuItem->price);
                $addOnCents = $addOns->sum(fn ($addOn) => $this->moneyToCents($addOn->price));
                $lineCents = ($itemCents + $addOnCents) * $quantity;
                $totalCents += $lineCents;
                $preparedLines[] = compact('key', 'menuItem', 'quantity', 'addOns', 'itemCents', 'lineCents');
            }

            $order = Order::create([
                'cashier_id' => $request->user()->user_id,
                'order_number' => $this->newOrderNumber(),
                'code' => null,
                'table_number' => $validated['order_type'] === 'dine-in' ? $validated['table_number'] : null,
                'order_type' => $validated['order_type'],
                'status' => 'awaiting_payment',
                'total_amount' => $totalCents / 100,
                'created_at' => now(),
            ]);

            foreach ($preparedLines as $line) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->order_id,
                    'menu_item_id' => $line['menuItem']->menu_item_id,
                    'quantity' => $line['quantity'],
                    'total' => $line['lineCents'] / 100,
                    'item_name_snapshot' => $line['menuItem']->item_name,
                    'unit_price' => $line['itemCents'] / 100,
                ]);

                foreach ($line['addOns'] as $addOn) {
                    $addOnCents = $this->moneyToCents($addOn->price);
                    OrderItemAddOn::create([
                        'order_item_id' => $orderItem->order_item_id,
                        'add_on_id' => $addOn->add_on_id,
                        'add_on_name_snapshot' => $addOn->add_on_name,
                        'unit_price' => $addOnCents / 100,
                        'total' => ($addOnCents * $line['quantity']) / 100,
                    ]);
                }
            }

            return $order;
        });

        $request->session()->forget(self::CART_KEY);

        return redirect()->route('cashier.orders.payment', $order)
            ->with('status', 'Order created. Complete payment to send it to the kitchen.');
    }

    private function cartPayload(Request $request): array
    {
        $cart = $request->session()->get(self::CART_KEY, []);
        if (! is_array($cart) || $cart === []) {
            return ['cart' => [], 'cartTotal' => 0, 'cartItemCount' => 0, 'cartError' => null];
        }

        $menuItemIds = collect($cart)->filter(fn ($line) => is_array($line))
            ->map(fn ($line) => (int) ($line['menu_item_id'] ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->unique();
        $menuItems = MenuItem::query()
            ->whereIn('menu_item_id', $menuItemIds)
            ->where('is_available', true)
            ->get()
            ->keyBy('menu_item_id');

        $items = [];
        $totalCents = 0;
        $itemCount = 0;
        $cartError = null;
        foreach ($cart as $key => $line) {
            if (! is_array($line)) {
                $cartError = 'An item in your cart is invalid. Remove it and add the item again.';
                continue;
            }

            $item = $menuItems->get((int) ($line['menu_item_id'] ?? 0));
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (! $item || $quantity === false || $quantity < 1 || $quantity > 99) {
                $cartError = 'A menu item or quantity in your cart is no longer valid. Remove it and try again.';
                continue;
            }

            $addOnIds = collect($line['addon_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $addOns = $item->addOns()
                ->where('add_ons.is_available', true)
                ->whereIn('add_ons.add_on_id', $addOnIds)
                ->get();

            if ($addOns->count() !== $addOnIds->count()) {
                $cartError = 'An add-on in your cart is no longer available. Remove the item and add it again.';
                continue;
            }

            $priceCents = $this->moneyToCents($item->price);
            $addOnTotalCents = $addOns->sum(fn ($addOn) => $this->moneyToCents($addOn->price));
            $lineTotalCents = ($priceCents + $addOnTotalCents) * $quantity;
            $items[$key] = [
                'key' => $key,
                'id' => $item->menu_item_id,
                'name' => $item->item_name,
                'price' => $priceCents / 100,
                'quantity' => $quantity,
                'addOns' => $addOns->map(fn ($addOn) => [
                    'name' => $addOn->add_on_name,
                    'price' => $this->moneyToCents($addOn->price) / 100,
                ])->all(),
                'lineTotal' => $lineTotalCents / 100,
            ];
            $totalCents += $lineTotalCents;
            $itemCount += $quantity;
        }

        return [
            'cart' => $items,
            'cartTotal' => $totalCents / 100,
            'cartItemCount' => $itemCount,
            'cartError' => $cartError,
        ];
    }

    private function cartLineKey(int $menuItemId, array $addOnIds): string
    {
        return $menuItemId . ':' . implode('-', $addOnIds);
    }

    private function moneyToCents(string|int|float $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', number_format((float) $amount, 2, '.', '')), 2, '00');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function newOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
