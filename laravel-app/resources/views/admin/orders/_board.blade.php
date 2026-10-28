{{--
    Premium Board fragment for Real-Time Order Monitoring.
--}}
@php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META;
@endphp
<div id="orders-board-grid" class="flex gap-6 h-full">
    @foreach ($columns as $status => $orders)
        <div class="min-w-[300px] flex-1 flex flex-col h-full">
            {{-- Column Header --}}
            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $meta[$status]['dot'] }}"></span>
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ $meta[$status]['label'] }}</h3>
                </div>
                <span class="text-[10px] font-bold bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">{{ $orders->count() }}</span>
            </div>

            {{-- Cards Container --}}
            <div class="flex-1 overflow-y-auto pr-2 space-y-4 pb-8 scrollbar-hide">
                @forelse ($orders as $order)
                    <div class="order-card group bg-white border-t-4 {{ $meta[$status]['border'] }} border-x border-b border-slate-200 rounded-xl shadow-sm p-4 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-3">
                            <div class="min-w-0">
                                <p class="text-lg font-mono font-black tracking-tighter text-slate-800 truncate">#{{ $order->order_id }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                                    @if ($order->order_type === 'takeout')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-tight">Takeout</span>
                                    @elseif ($order->table_number)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-tight">Table {{ $order->table_number }}</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-tight">Dine-in</span>
                                    @endif
                                </p>
                            </div>
                            <span class="order-elapsed text-[10px] font-medium text-slate-400 whitespace-nowrap"
                                data-created-at="{{ $order->created_at->toIso8601String() }}"></span>
                        </div>

                        <div class="space-y-2 mb-4">
                            @foreach ($order->orderItems->take(4) as $item)
                                <div class="flex justify-between items-center text-xs text-slate-600 py-1 border-b border-slate-50 last:border-0">
                                    <span class="truncate">{{ $item->menuItem->item_name ?? 'Item' }}</span>
                                    <span class="font-bold text-slate-900 ml-2">{{ $item->quantity }}x</span>
                                </div>
                            @endforeach
                            @if ($order->orderItems->count() > 4)
                                <p class="text-[10px] text-slate-400 italic text-right">+{{ $order->orderItems->count() - 4 }} more items</p>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <p class="text-[10px] text-slate-400">By <span class="text-slate-600 font-medium">{{ $order->cashier->name ?? 'Staff' }}</span></p>
                            </div>
                            <button type="button"
                                class="view-order-btn text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors flex items-center gap-1 group-hover:translate-x-1 transition-transform"
                                data-id="{{ $order->order_id }}">
                                View Details
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center p-8 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                        <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs text-slate-400 italic">Waiting for orders...</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>