<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderWorkflowController extends Controller
{
    public function display()
    {
        return Inertia::render('Orders/Display');
    }

    public function displayOrders()
    {
        $orders = Order::query()
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->whereNotNull('code')
            ->orderBy('created_at')
            ->get(['code', 'status'])
            ->map(fn (Order $order) => [
                'code' => $order->code,
                'status' => $order->status,
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function managerIndex()
    {
        $orders = Order::with(['orderItems.menuItem', 'cashier'])
            ->whereIn('status', ['pending', 'ready', 'picked_up', 'dropped'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return Inertia::render('Manager/Orders', ['orders' => $orders]);
    }

    public function managerUpdateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:ready,picked_up,dropped'],
        ]);

        $allowedTransitions = [
            'pending' => ['ready'],
            'ready' => ['picked_up', 'dropped'],
        ];

        DB::transaction(function () use ($order, $validated, $allowedTransitions) {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($validated['status'], $allowedTransitions[$lockedOrder->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This order has already moved to another stage.',
                ]);
            }

            $lockedOrder->update(['status' => $validated['status']]);
        });

        return redirect()->route('manager.orders.index')
            ->with('status', "Order {$order->order_number} updated to {$validated['status']}.");
    }
}
