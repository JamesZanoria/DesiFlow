{{--
    Board fragment for Real-Time Order Monitoring (REQ-4.3-1).

    Rendered two ways from OrderMonitorController@index:
      1. Normal page load, included directly inside admin.orders.index.
      2. AJAX poll refresh, returned alone (as HTML inside a JSON envelope)
         every few seconds, so newly confirmed/prepared/served orders appear
         without the admin reloading the page. See setInterval in index.blade.php.

    $columns is ['pending' => Collection, 'preparing' => Collection, 'ready' => Collection],
    pre-filtered server-side to just the selected column(s) when a status filter is active.
--}}
@php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META;
@endphp
<div id="orders-board-grid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach ($columns as $status => $orders)
        <div class="bg-white border rounded-xl shadow-sm flex flex-col min-h-[28rem]">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $meta[$status]['dot'] }}"></span>
                    <h3 class="text-sm font-semibold text-gray-800">{{ $meta[$status]['label'] }}</h3>
                </div>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $orders->count() }}</span>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                @forelse ($orders as $order)
                    <div class="order-card bg-white border-l-4 {{ $meta[$status]['border'] }} border-y border-r rounded-lg shadow-sm p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Order #{{ $order->order_id }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @if ($order->order_type === 'takeout')
                                        Takeout
                                    @elseif ($order->table_number)
                                        Table {{ $order->table_number }}
                                    @else
                                        Dine-in
                                    @endif
                                </p>
                            </div>
                            <span class="order-elapsed text-xs font-medium text-gray-400 whitespace-nowrap"
                                data-created-at="{{ $order->created_at->toIso8601String() }}"></span>
                        </div>

                        <div class="mt-2 text-xs text-gray-600 space-y-0.5">
                            @foreach ($order->orderItems->take(3) as $item)
                                <p class="truncate">{{ $item->quantity }}&times; {{ $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item' }}</p>
                            @endforeach
                            @if ($order->orderItems->count() > 3)
                                <p class="text-gray-400">+{{ $order->orderItems->count() - 3 }} more item{{ $order->orderItems->count() - 3 === 1 ? '' : 's' }}</p>
                            @endif
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-gray-400">
                                @if ($order->cashier)
                                    by {{ $order->cashier->name }}
                                @endif
                            </p>
                            <button type="button"
                                class="view-order-btn text-xs font-medium text-amber-600 hover:underline"
                                data-id="{{ $order->order_id }}">
                                View
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-gray-400 py-8">No orders in this stage.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>