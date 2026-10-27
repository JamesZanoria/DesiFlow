{{--
    Premium Board fragment for Real-Time Order Monitoring.
--}}
@php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META;
@endphp
<div id="orders-board-grid" class="flex gap-6 h-full min-w-max">
    @foreach ($columns as $status => $orders)
        <div class="w-80 flex flex-col h-full">
            {{-- Column Header --}}
            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $meta[$status]['dot'] }}"></span>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">{{ $meta[$status]['label'] }}</h3>
                </div>
                <span class="text-[10px] font-bold bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">{{ $orders->count() }}</span>
            </div>

            {{-- Cards Container --}}
            <div class="flex-1 overflow-y-auto pr-2 space-y-4 pb-8 scrollbar-hide">
                @forelse ($orders as $order)
                    <div class="order-card group bg-white border-l-4 {{ $meta[$status]['border'] }} border-y border-r border-slate-200 rounded-xl shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-0.5">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="text-sm font-bold text-slate-900">Order #{{ $order->order_id }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                                    @if ($order->order_type === 'takeout')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-medium">Takeout</span>
                                    @elseif ($order->table_number)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-medium">Table {{ $order->table_number }}</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-50 text-slate-700 font-medium">Dine-in</span>
                                    @endif
                                </p>
                            </div>
                            <span class="order-elapsed text-[10px] font-medium text-slate-400 whitespace-nowrap"
                                data-created-at="{{ $order->created_at->toIso8601String() }}"></span>
                        </div>

                        <div class="space-y-1.5 mb-4">
                            @foreach ($order->orderItems->take(4) as $item)
                                <div class="flex justify-between items-center text-xs text-slate-600">
                                    <span class="truncate">{{ $item->menuItem->item_name ?? 'Item' }}</span>
                                    <span class="font-semibold text-slate-900 ml-2">{{ $item->quantity }}x</span>
                                </div>
                            @endforeach
                            @if ($order->orderItems->count() > 4)
                                <p class="text-[10px] text-slate-400 italic">+{{ $order->orderItems->count() - 4 }} more items</p>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-50 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <p class="text-[10px] text-slate-400">By <span class="text-slate-600 font-medium">{{ $order->cashier->name ?? 'Staff' }}</span></p>
                            </div>
                            <button type="button"
                                class="view-order-btn text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors flex items-center gap-1"
                                data-id="{{ $order->order_id }}">
                                View Details
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center">
                        <p class="text-xs text-slate-400 italic">No orders in this stage.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>