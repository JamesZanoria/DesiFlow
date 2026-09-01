<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderMonitorController extends Controller{
    // The three active-order stages this board displays. 'ready' is
    // the KDS's "To Be Served" stage, once an order is actually served (backflush
    // triggered) or paid, it's no longer "active" and drops off this board.
    private const ACTIVE_STATUSES = ['pending', 'preparing', 'ready'];

    // Human labels + column styling, single source of truth shared by the
    // Blade views so a status's color/label can't drift between the board,
    // the quick-view badge, and the filter pills.
    public const STATUS_META = [
        'pending' => ['label' => 'Pending / Confirmed', 'dot' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700', 'border' => 'border-amber-400'],
        'preparing' => ['label' => 'In Preparation', 'dot' => 'bg-blue-500', 'badge' => 'bg-blue-100 text-blue-700', 'border' => 'border-blue-400'],
        'ready' => ['label' => 'Ready to Serve', 'dot' => 'bg-green-500', 'badge' => 'bg-green-100 text-green-700', 'border' => 'border-green-400'],
    ];

    // Display all active orders grouped into Pending/Confirmed, In Preparation,
    // and Ready to Serve columns, with an optional status filter
    // narrowing which column(s) are populated.
    public function index(Request $request){
        $statusFilter = $request->input('status');
        $statuses = in_array($statusFilter, self::ACTIVE_STATUSES, true)
            ? [$statusFilter]
            : self::ACTIVE_STATUSES;

        $activeOrders = Order::with(['orderItems.menuItem', 'cashier'])
            ->whereIn('status', $statuses)
            ->orderByDesc('created_at')
            ->get();

        $columns = collect(self::ACTIVE_STATUSES)->mapWithKeys(fn ($status) => [
            $status => $activeOrders->where('status', $status)->values(),
        ]);

        // AJAX refresh: the page polls this same endpoint asking for JSON every
        // few seconds (see index.blade.php) and swaps in just the board fragment,
        // so newly confirmed/prepared/served orders appear without a manual
        // reload, the polling stand-in for real WebSocket push until
        // Laravel Reverb is wired into this module.
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.orders._board', [
                    'columns' => $columns,
                    'statusFilter' => $statusFilter,
                ])->render(),
            ]);
        }

        return view('admin.orders.index', [
            'columns' => $columns,
            'statusFilter' => $statusFilter,
        ]);
    }

    // Show full details for a single order. AJAX requests get back just the
    // Quick-View modal fragment (read-only, the admin can observe an order here
    // but all actions on it still happen through POS/KDS, per
    // role-scoped privileges); a direct/no-JS visit gets a full page instead.
    public function show(Request $request, Order $order){
        $order->load(['orderItems.menuItem', 'cashier', 'payment']);

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.orders._quickview', [
                    'order' => $order,
                ])->render(),
            ]);
        }

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }
}