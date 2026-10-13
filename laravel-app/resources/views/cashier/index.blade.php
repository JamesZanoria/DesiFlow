@extends('layouts.module')

@section('title', 'Cashier')
@section('module-name', 'Cashier')
@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold">Cashier order queue</h1>
        <p class="mt-1 text-sm text-gray-500">Confirm a customer's pickup code to send the order to the kitchen.</p>
    </div>

    <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Accept an order</h2>
        <form method="POST" action="{{ route('cashier.orders.approve-code') }}" class="mt-3 flex flex-col gap-3 sm:flex-row">
            @csrf
            <label class="sr-only" for="pickup-code">Six-digit pickup code</label>
            <input id="pickup-code" name="code" inputmode="numeric" autocomplete="off" maxlength="6" pattern="[0-9]{6}"
                value="{{ old('code') }}" placeholder="Enter 6-digit pickup code" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono tracking-widest focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:max-w-xs">
            <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Accept order</button>
        </form>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <section>
            <h2 class="mb-3 text-lg font-bold">Awaiting confirmation <span class="text-sm font-medium text-gray-500">({{ $unapprovedOrders->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($unapprovedOrders as $order)
                    <article class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold">{{ $order->order_number }}</h3>
                                <p class="text-sm text-gray-500">{{ $order->order_type === 'takeout' ? 'Takeout / Pickup' : 'Dine-in, table ' . $order->table_number }} · {{ $order->created_at?->format('g:i A') }}</p>
                            </div>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Unconfirmed</span>
                        </div>
                        <ul class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-sm text-gray-700">
                            @foreach ($order->orderItems as $item)
                                <li class="flex justify-between gap-3"><span>{{ $item->quantity }} × {{ $item->menuItem?->item_name ?? 'Item' }}</span><span>₱{{ number_format($item->total, 2) }}</span></li>
                            @endforeach
                        </ul>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 font-semibold">
                            <span>Total</span><span>₱{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        <form method="POST" action="{{ route('cashier.orders.reject', $order) }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <label class="sr-only" for="reason-{{ $order->order_id }}">Reason for rejection</label>
                            <input id="reason-{{ $order->order_id }}" name="reason" maxlength="150" required placeholder="Reason for rejection"
                                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Reject</button>
                        </form>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No orders are waiting for confirmation.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-lg font-bold">Sent to kitchen <span class="text-sm font-medium text-gray-500">({{ $pendingOrders->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($pendingOrders as $order)
                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold">{{ $order->order_number }}</h3>
                                <p class="text-sm text-gray-500">{{ $order->order_type === 'takeout' ? 'Takeout / Pickup' : 'Dine-in, table ' . $order->table_number }} · {{ $order->created_at?->format('g:i A') }}</p>
                            </div>
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">Kitchen queue</span>
                        </div>
                        <ul class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-sm text-gray-700">
                            @foreach ($order->orderItems as $item)
                                <li>{{ $item->quantity }} × {{ $item->menuItem?->item_name ?? 'Item' }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-right text-sm font-semibold">₱{{ number_format($order->total_amount, 2) }}</p>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No orders have been accepted yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
