<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierController extends Controller
{
    public function index()
    {
        $orders = Order::with('orderItems.menuItem')
            ->whereIn('status', ['unapproved', 'pending'])
            ->orderBy('created_at')
            ->get();

        return view('cashier.index', [
            'unapprovedOrders' => $orders->where('status', 'unapproved')->values(),
            'pendingOrders' => $orders->where('status', 'pending')->values(),
        ]);
    }

    public function approveByCode(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        $order = DB::transaction(function () use ($request, $validated) {
            $order = Order::query()->where('code', $validated['code'])->lockForUpdate()->first();

            if (! $order || $order->status !== 'unapproved') {
                throw ValidationException::withMessages([
                    'code' => 'Pickup code not found or the order has already been processed.',
                ]);
            }

            $order->update([
                'status' => 'pending',
                'cashier_id' => $request->user()->user_id,
            ]);

            return $order;
        });

        return redirect()->route('cashier.index')
            ->with('status', "Order {$order->order_number} accepted and sent to the kitchen.");
    }

    public function reject(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($request, $order, $validated) {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== 'unapproved') {
                throw ValidationException::withMessages([
                    'order' => 'Only unapproved customer orders can be rejected.',
                ]);
            }

            $lockedOrder->update([
                'status' => 'rejected',
                'cashier_id' => $request->user()->user_id,
                'notes' => $validated['reason'],
            ]);
        });

        return redirect()->route('cashier.index')
            ->with('status', "Order {$order->order_number} was rejected.");
    }
}
