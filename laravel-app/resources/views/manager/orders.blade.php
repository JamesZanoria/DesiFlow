@extends('layouts.app')

@section('title', 'Order Workflow')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Order workflow</h1>
        <p class="mt-1 text-sm text-gray-500">Move ready orders through pickup or completion. Kitchen preparation stays in the separate KDS.</p>
    </div>

    @if (session('status'))
        <div role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold">{{ $order->order_number ?: 'Order #' . $order->order_id }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $order->order_type === 'takeout' ? 'Takeout' : ($order->table_number ? 'Table ' . $order->table_number : 'Dine-in') }}
                                    · {{ $order->created_at?->format('M j, g:i A') }}
                                </p>
                            </td>
                            <td class="px-4 py-4 align-top text-gray-600">
                                @foreach ($order->orderItems as $item)
                                    <p>{{ $item->quantity }} × {{ $item->menuItem?->item_name ?? 'Item' }}</p>
                                @endforeach
                            </td>
                            <td class="px-4 py-4 align-top font-medium">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-4 py-4 align-top">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->status === 'ready' ? 'bg-green-100 text-green-800' : ($order->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">
                                    {{ str_replace('_', ' ', ucfirst($order->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if ($order->status === 'pending')
                                    <form method="POST" action="{{ route('manager.orders.status', $order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="ready">
                                        <button class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700">Mark ready</button>
                                    </form>
                                @elseif ($order->status === 'ready')
                                    <div class="flex flex-wrap gap-2">
                                        @foreach (['picked_up' => 'Picked up', 'dropped' => 'Complete'] as $status => $label)
                                            <form method="POST" action="{{ route('manager.orders.status', $order) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ $label }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">No action required</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">No orders are available in the manager workflow.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
