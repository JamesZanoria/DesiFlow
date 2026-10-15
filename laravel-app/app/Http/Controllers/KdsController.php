<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KdsController extends Controller
{
    private const ACTIVE_STATUSES = ['pending', 'preparing', 'ready'];

    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.menuItem', 'orderItems.addOns'])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('created_at')
            ->get();

        $columns = collect(self::ACTIVE_STATUSES)->mapWithKeys(fn (string $status) => [
            $status => $orders->where('status', $status)->values(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('kds._board', ['columns' => $columns])->render(),
            ]);
        }

        return view('kds.index', ['columns' => $columns]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->merge([
            'status' => strtolower(trim((string) $request->input('status', ''))),
        ]);

        $validated = $request->validate([
            'status' => ['required', 'string', 'filled', 'in:pending,preparing,ready'],
        ]);

        $allowedTransitions = [
            'pending' => ['preparing'],
            'preparing' => ['pending', 'ready'],
            'ready' => ['preparing'],
        ];

        DB::transaction(function () use ($order, $validated, $allowedTransitions) {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($validated['status'], $allowedTransitions[$lockedOrder->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'That order has already moved to another stage. Refresh the board and try again.',
                ]);
            }

            $lockedOrder->update(['status' => $validated['status']]);
        });

        return response()->json(['message' => 'Order status updated.']);
    }
}
