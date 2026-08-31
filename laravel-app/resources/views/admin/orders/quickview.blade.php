{{--
    Order Quick-View — read-only. The admin can observe an order's full detail
    here, but all actions on an order (accepting, preparing, serving, voiding)
    happen through the Cashier/POS or Kitchen Display modules per each role's
    scoped privileges (SRS §2.3) — this view intentionally has no buttons that
    change order state.
--}}
@php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META[$order->status] ?? null;
@endphp
<div class="space-y-4">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-lg font-bold text-gray-800">Order #{{ $order->order_id }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $order->created_at->format('M j, Y — g:i A') }}</p>
        </div>
        @if ($meta)
            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
        @else
            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ ucfirst($order->status) }}</span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500">Order Type</p>
            <p class="font-medium text-gray-800">
                {{ $order->order_type === 'takeout' ? 'Takeout' : ($order->table_number ? 'Dine-in — Table ' . $order->table_number : 'Dine-in') }}
            </p>
        </div>
        <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500">Handled By</p>
            <p class="font-medium text-gray-800">{{ $order->cashier->name ?? '—' }}</p>
        </div>
    </div>

    <div>
        <p class="text-xs font-semibold text-gray-500 mb-2">Items</p>
        <div class="border rounded-lg divide-y">
            @foreach ($order->orderItems as $item)
                <div class="flex items-center justify-between px-3 py-2 text-sm">
                    <span class="text-gray-700">{{ $item->quantity }}&times; {{ $item->menuItem->item_name ?? 'Item' }}</span>
                    <span class="text-gray-600">₱{{ number_format($item->total, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between pt-2 border-t">
        <p class="text-sm font-semibold text-gray-700">Total</p>
        <p class="text-base font-bold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</p>
    </div>

    @if ($order->payment)
        <div class="bg-gray-50 rounded-lg px-3 py-2 text-sm">
            <p class="text-xs text-gray-500 mb-1">Payment</p>
            <p class="font-medium text-gray-800">
                {{ ucfirst($order->payment->payment_method) }}
                @if ($order->payment->gcash_ref_num)
                    — Ref #{{ $order->payment->gcash_ref_num }}
                @endif
            </p>
        </div>
    @endif
</div>