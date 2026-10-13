@php
    $labels = ['pending' => 'New', 'preparing' => 'Preparing', 'ready' => 'Ready'];
    $styles = [
        'pending' => ['dot' => 'bg-blue-500', 'border' => 'border-blue-500'],
        'preparing' => ['dot' => 'bg-amber-500', 'border' => 'border-amber-500'],
        'ready' => ['dot' => 'bg-green-500', 'border' => 'border-green-500'],
    ];
@endphp
<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    @foreach ($columns as $status => $orders)
        <section class="flex min-h-64 flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
            <header class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <span class="h-2.5 w-2.5 rounded-full {{ $styles[$status]['dot'] }}"></span>
                    {{ $labels[$status] }}
                </h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600">{{ $orders->count() }}</span>
            </header>
            <div class="flex-1 space-y-3 p-3">
                @forelse ($orders as $order)
                    @php
                        $nextStatus = ['pending' => 'preparing', 'preparing' => 'ready', 'ready' => 'preparing'][$status];
                        $buttonLabel = ['pending' => 'Start preparing', 'preparing' => 'Mark ready', 'ready' => 'Reopen ticket'][$status];
                        $buttonStyle = $status === 'ready' ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' : 'bg-gray-900 text-white hover:bg-gray-700';
                    @endphp
                    <article class="rounded-lg border-l-4 {{ $styles[$status]['border'] }} border-y border-r border-gray-200 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold">{{ $order->order_number ?: 'Order #' . $order->order_id }}</h3>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $order->order_type === 'takeout' ? 'Takeout' : ($order->table_number ? 'Table ' . $order->table_number : 'Dine-in') }}
                                    · {{ $order->created_at?->format('g:i A') }}
                                </p>
                            </div>
                            @if ($order->code)
                                <span class="rounded bg-gray-100 px-2 py-1 font-mono text-sm font-bold tracking-wider">{{ $order->code }}</span>
                            @endif
                        </div>
                        <ul class="mt-3 space-y-1 border-t border-gray-100 pt-2 text-sm">
                            @foreach ($order->orderItems as $item)
                                <li>{{ $item->quantity }} × {{ $item->menuItem?->item_name ?? 'Item' }}</li>
                            @endforeach
                        </ul>
                        <button type="button" data-next-status="{{ $nextStatus }}"
                            data-url="{{ route('kds.orders.status', $order) }}"
                            class="mt-3 w-full rounded-lg px-3 py-2 text-sm font-semibold {{ $buttonStyle }}">
                            {{ $buttonLabel }}
                        </button>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-gray-400">No orders in this stage.</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
