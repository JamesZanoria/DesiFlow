@extends('layouts.module')

@section('title', 'Active Orders')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Order management</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Active orders</h1>
            <p class="mt-1 text-sm text-slate-500">Orders accepted by you and moving through the kitchen.</p>
        </div>
        <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">{{ $orders->count() }} active</span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($orders->isEmpty())
            <div class="px-6 py-14 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 3.75v6m10.5-6v6M5.25 20.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" /></svg></span>
                <p class="mt-3 text-sm font-semibold text-slate-700">No active orders</p>
                <p class="mt-1 text-sm text-slate-500">Accepted orders will appear here while the kitchen prepares them.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Customer / table</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Placed</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $order->customer_name ?: ($order->order_type === 'takeout' ? 'Takeout / pickup' : 'Table ' . $order->table_number) }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold capitalize text-blue-700">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                <td class="px-5 py-4 text-slate-500">{{ $order->created_at?->format('M j, g:i A') }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-900">₱{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if (! $order->payment)
                                            <a href="{{ route('cashier.orders.payment', $order) }}" class="rounded-lg bg-amber-500 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600">Take payment</a>
                                        @endif
                                        <button type="button" data-quick-view="{{ route('cashier.orders.quick-view', $order) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50">View</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <section id="quick-view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="quick-view-title">
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div><h2 id="quick-view-title" class="text-xl font-bold">Order details</h2><p id="quick-view-meta" class="mt-1 text-sm text-gray-500"></p></div>
                <button type="button" data-close-quick-view class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Close order details">&times;</button>
            </div>
            <div id="quick-view-error" class="mt-4 hidden rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert"></div>
            <ul id="quick-view-items" class="mt-4 max-h-72 divide-y divide-gray-100 overflow-y-auto border-y border-gray-100"></ul>
            <p class="mt-4 flex justify-between font-bold"><span>Total</span><span id="quick-view-total"></span></p>
            <div class="mt-5 flex justify-end"><button type="button" data-close-quick-view class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Close</button></div>
        </div>
    </section>
    <script>
        (() => {
            const modal = document.getElementById('quick-view-modal');
            const meta = document.getElementById('quick-view-meta');
            const items = document.getElementById('quick-view-items');
            const total = document.getElementById('quick-view-total');
            const error = document.getElementById('quick-view-error');
            const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };

            document.querySelectorAll('[data-close-quick-view]').forEach((button) => button.addEventListener('click', close));
            document.querySelectorAll('[data-quick-view]').forEach((button) => button.addEventListener('click', async () => {
                error.classList.add('hidden');
                items.replaceChildren();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                try {
                    const response = await fetch(button.dataset.quickView, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Could not load this order.');
                    const order = data.order;
                    meta.textContent = `${order.number} · ${order.type === 'takeout' ? 'Takeout' : `Table ${order.table || '—'}`} · ${order.created_at} · ${order.status.replaceAll('_', ' ')}`;
                    total.textContent = `₱${Number(order.total).toFixed(2)}`;
                    order.items.forEach((item) => {
                        const li = document.createElement('li');
                        li.className = 'py-3';
                        const row = document.createElement('div');
                        row.className = 'flex justify-between gap-3 text-sm';
                        const name = document.createElement('span');
                        name.textContent = `${item.quantity} × ${item.name}`;
                        const amount = document.createElement('span');
                        amount.textContent = `₱${Number(item.total).toFixed(2)}`;
                        row.append(name, amount);
                        li.append(row);
                        item.add_ons.forEach((addOn) => {
                            const detail = document.createElement('p');
                            detail.className = 'mt-1 pl-4 text-xs text-gray-500';
                            detail.textContent = `+ ${addOn.name} (₱${Number(addOn.unit_price).toFixed(2)} each)`;
                            li.append(detail);
                        });
                        items.append(li);
                    });
                } catch (exception) {
                    error.textContent = exception.message;
                    error.classList.remove('hidden');
                }
            }));
            modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
        })();
    </script>
@endsection
