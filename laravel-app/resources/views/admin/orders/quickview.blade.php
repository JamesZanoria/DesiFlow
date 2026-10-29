{{--
    Order Quick-View, read-only. The admin can observe an order's full detail
    here, but all actions on an order (accepting, preparing, serving, voiding)
    happen through the Cashier/POS or Kitchen Display modules per each role's
    scoped privileges (SRS §2.3), this view intentionally has no buttons that
    change order state.
--}}
@php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META[$order->status] ?? null;
@endphp
<div class="space-y-6">
    <div class="flex items-start justify-between">
        <div class="min-w-0">
            <p class="text-2xl font-mono font-black tracking-tighter text-white">#{{ $order->order_id }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $order->created_at->format('M j, Y, g:i A') }}</p>
        </div>
        @if ($meta)
            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $meta['badge'] }} shadow-sm">
                {{ $meta['label'] }}
            </span>
        @else
            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest bg-slate-700 text-slate-300 shadow-sm">
                {{ ucfirst($order->status) }}
            </span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Order Type</p>
            <p class="font-semibold text-slate-100">
                {{ $order->order_type === 'takeout' ? 'Takeout' : ($order->table_number ? 'Dine-in, Table ' . $order->table_number : 'Dine-in') }}
            </p>
        </div>
        <div class="bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Handled By</p>
            <p class="font-semibold text-slate-100">{{ $order->cashier->name ?? 'Staff' }}</p>
        </div>
    </div>

    <div class="space-y-3">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Order Items</p>
        <div class="bg-slate-800/30 border border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-700">
            @foreach ($order->orderItems as $item)
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <div class="flex items-center gap-3 truncate">
                        <span class="font-bold text-amber-500">{{ $item->quantity }}&times;</span>
                        <span class="text-slate-300 truncate">{{ $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item' }}</span>
                    </div>
                    <span class="text-slate-400 font-mono text-xs">₱{{ number_format($item->total, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-slate-700">
        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Total Amount</p>
        <p class="text-2xl font-mono font-black text-white">₱{{ number_format($order->total_amount, 2) }}</p>
    </div>

    @if ($order->payment)
        <div class="bg-amber-500/10 rounded-xl px-4 py-3 border border-amber-500/20 text-sm">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Payment Confirmed</p>
            </div>
            <p class="font-semibold text-slate-200">
                {{ ucfirst($order->payment->payment_method) }}
                @if ($order->payment->gcash_ref_num),
                <span class="text-slate-400 font-mono text-xs">Ref #{{ $order->payment->gcash_ref_num }}</span>
                @endif
            </p>
        </div>
    @endif
</div>