@extends('layouts.module')

@section('title', 'Cashier Dashboard')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <section class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-5 py-6 text-white shadow-xl shadow-slate-900/10 sm:px-7 sm:py-8">
        <div class="pointer-events-none absolute -right-12 -top-20 h-64 w-64 rounded-full border-[36px] border-white/5"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-amber-400">Cashier workspace</p>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-2 text-sm text-slate-300">{{ now()->format('l, F j, Y') }} <span class="mx-1 text-slate-500">·</span> Daily totals use the application timezone.</p>
            </div>
            <a href="{{ route('cashier.menu') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-amber-950/30 transition hover:-translate-y-0.5 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-900">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" /></svg>
                Start a new order
            </a>
        </div>
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Today's cashier statistics">
        <button type="button" data-dashboard-orders="{{ route('cashier.dashboard.orders', 'paid-today') }}" data-dashboard-title="Paid orders today" aria-label="Show paid orders today" class="group w-full rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-100">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-medium text-slate-500">Paid orders today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $processedToday }}</p></div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span>
            </div>
            <span class="mt-3 block text-xs font-medium text-amber-700 opacity-0 transition group-hover:opacity-100 group-focus:opacity-100">View matching orders →</span>
        </button>
        <button type="button" data-dashboard-orders="{{ route('cashier.dashboard.orders', 'pending') }}" data-dashboard-title="Pending orders" aria-label="Show pending orders" class="group w-full rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-100">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-medium text-slate-500">Pending orders</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $pendingOrderCount }}</p></div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" /></svg></span>
            </div>
            <span class="mt-3 block text-xs font-medium text-amber-700 opacity-0 transition group-hover:opacity-100 group-focus:opacity-100">View matching orders →</span>
        </button>
        <button type="button" data-dashboard-orders="{{ route('cashier.dashboard.orders', 'active') }}" data-dashboard-title="Active kitchen orders" aria-label="Show active kitchen orders" class="group w-full rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-100">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-medium text-slate-500">Active kitchen orders</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $activeOrderCount }}</p></div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 3.75v6m10.5-6v6M5.25 20.25h13.5a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" /></svg></span>
            </div>
            <span class="mt-3 block text-xs font-medium text-amber-700 opacity-0 transition group-hover:opacity-100 group-focus:opacity-100">View matching orders →</span>
        </button>
        <button type="button" data-dashboard-orders="{{ route('cashier.dashboard.orders', 'awaiting-payment') }}" data-dashboard-title="Awaiting payment" aria-label="Show orders awaiting payment" class="group w-full rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-100">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-medium text-slate-500">Awaiting payment</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $awaitingPaymentCount }}</p></div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5m-18 0A2.25 2.25 0 0 0 1.5 10.5v8.25A2.25 2.25 0 0 0 3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V10.5a2.25 2.25 0 0 0-2.25-2.25m-18 0V6A2.25 2.25 0 0 1 4.5 3.75h15A2.25 2.25 0 0 1 21.75 6v2.25M6 15h3" /></svg></span>
            </div>
            <span class="mt-3 block text-xs font-medium text-amber-700 opacity-0 transition group-hover:opacity-100 group-focus:opacity-100">View matching orders →</span>
        </button>
    </section>

    <section id="dashboard-orders-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="dashboard-orders-title">
        <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 id="dashboard-orders-title" class="text-lg font-bold text-slate-900"></h2>
                    <p id="dashboard-orders-count" class="mt-0.5 text-xs text-slate-500"></p>
                </div>
                <button type="button" id="dashboard-orders-close" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-2xl leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close order list">&times;</button>
            </header>
            <div id="dashboard-orders-content" class="min-h-32 overflow-y-auto p-4 sm:p-6" aria-live="polite"></div>
            <footer id="dashboard-orders-pagination" class="hidden items-center justify-between border-t border-slate-100 px-5 py-3 text-sm sm:px-6">
                <span id="dashboard-orders-page-label" class="text-slate-500"></span>
                <div class="flex gap-2">
                    <button type="button" id="dashboard-orders-previous" class="rounded-lg border border-slate-200 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
                    <button type="button" id="dashboard-orders-next" class="rounded-lg border border-slate-200 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </footer>
        </div>
    </section>

    <script>
        (() => {
            const modal = document.getElementById('dashboard-orders-modal');
            const title = document.getElementById('dashboard-orders-title');
            const count = document.getElementById('dashboard-orders-count');
            const content = document.getElementById('dashboard-orders-content');
            const pagination = document.getElementById('dashboard-orders-pagination');
            const pageLabel = document.getElementById('dashboard-orders-page-label');
            const previous = document.getElementById('dashboard-orders-previous');
            const next = document.getElementById('dashboard-orders-next');
            let activeUrl = null;
            let activePage = 1;
            let lastPage = 1;

            const close = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const money = (amount) => `₱${Number(amount).toFixed(2)}`;

            function renderOrders(orders) {
                content.replaceChildren();
                if (orders.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'rounded-xl border border-dashed border-slate-200 px-5 py-12 text-center';
                    const heading = document.createElement('p');
                    heading.className = 'font-semibold text-slate-700';
                    heading.textContent = 'No matching orders';
                    const hint = document.createElement('p');
                    hint.className = 'mt-1 text-sm text-slate-500';
                    hint.textContent = 'There are no orders in this section right now.';
                    empty.append(heading, hint);
                    content.append(empty);
                    return;
                }

                orders.forEach((order) => {
                    const card = document.createElement('article');
                    card.className = 'mb-3 overflow-hidden rounded-xl border border-slate-200 bg-white last:mb-0';

                    const heading = document.createElement('div');
                    heading.className = 'flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3';
                    const details = document.createElement('div');
                    const number = document.createElement('h3');
                    number.className = 'font-bold text-slate-900';
                    number.textContent = order.number;
                    const meta = document.createElement('p');
                    meta.className = 'mt-1 text-xs text-slate-500';
                    meta.textContent = `${order.type === 'takeout' ? 'Takeout' : `Dine-in · table ${order.table || '—'}`} · ${order.created_at || ''}`;
                    details.append(number, meta);

                    const summary = document.createElement('div');
                    summary.className = 'text-right';
                    const status = document.createElement('span');
                    status.className = 'rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold capitalize text-amber-700';
                    status.textContent = order.status.replaceAll('_', ' ');
                    const total = document.createElement('p');
                    total.className = 'mt-2 text-sm font-bold text-slate-900';
                    total.textContent = money(order.total);
                    summary.append(status, total);
                    heading.append(details, summary);

                    const list = document.createElement('ul');
                    list.className = 'divide-y divide-slate-100 px-4';
                    order.items.forEach((item) => {
                        const row = document.createElement('li');
                        row.className = 'py-3';
                        const itemLine = document.createElement('div');
                        itemLine.className = 'flex justify-between gap-3 text-sm';
                        const itemName = document.createElement('span');
                        itemName.className = 'font-medium text-slate-800';
                        itemName.textContent = `${item.quantity} × ${item.name}`;
                        const itemTotal = document.createElement('span');
                        itemTotal.className = 'shrink-0 text-slate-700';
                        itemTotal.textContent = money(item.total);
                        itemLine.append(itemName, itemTotal);
                        row.append(itemLine);
                        item.add_ons.forEach((addOn) => {
                            const addOnLine = document.createElement('p');
                            addOnLine.className = 'mt-1 pl-4 text-xs text-slate-500';
                            addOnLine.textContent = `+ ${addOn.name} × ${addOn.quantity} · ${money(addOn.total)}`;
                            row.append(addOnLine);
                        });
                        list.append(row);
                    });
                    card.append(heading, list);
                    content.append(card);
                });
            }

            async function loadOrders(page = 1) {
                content.replaceChildren();
                const loading = document.createElement('p');
                loading.className = 'py-10 text-center text-sm text-slate-500';
                loading.textContent = 'Loading orders...';
                content.append(loading);
                pagination.classList.add('hidden');

                const url = new URL(activeUrl, window.location.href);
                url.searchParams.set('page', String(page));
                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Could not load the matching orders.');
                    }

                    activePage = data.pagination.current_page;
                    lastPage = data.pagination.last_page;
                    count.textContent = `${data.pagination.total} ${data.pagination.total === 1 ? 'order' : 'orders'}`;
                    renderOrders(data.orders);
                    pagination.classList.toggle('hidden', lastPage <= 1);
                    pagination.classList.toggle('flex', lastPage > 1);
                    pageLabel.textContent = `Page ${activePage} of ${lastPage}`;
                    previous.disabled = activePage <= 1;
                    next.disabled = activePage >= lastPage;
                } catch (error) {
                    content.replaceChildren();
                    const message = document.createElement('p');
                    message.className = 'rounded-lg bg-red-50 p-4 text-sm text-red-700';
                    message.setAttribute('role', 'alert');
                    message.textContent = error.message;
                    content.append(message);
                }
            }

            document.querySelectorAll('[data-dashboard-orders]').forEach((button) => button.addEventListener('click', () => {
                activeUrl = button.dataset.dashboardOrders;
                title.textContent = button.dataset.dashboardTitle;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                loadOrders();
            }));
            previous.addEventListener('click', () => loadOrders(activePage - 1));
            next.addEventListener('click', () => loadOrders(activePage + 1));
            document.getElementById('dashboard-orders-close').addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
            });
        })();
    </script>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v3.75l2.25 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span>
                <div>
                    <h2 class="font-bold text-slate-900">Unpaid orders</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Saved orders waiting for payment before going to the kitchen.</p>
                </div>
            </div>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ $awaitingPaymentCount }} awaiting</span>
        </div>
        @if ($unpaidOrders->isEmpty())
            <div class="px-6 py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span>
                <p class="mt-3 text-sm font-semibold text-slate-700">All caught up</p>
                <p class="mt-1 text-sm text-slate-500">There are no unpaid cashier orders right now.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Placed</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($unpaidOrders as $order)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $order->order_number }}</td>
                                <td class="px-5 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $order->order_type === 'takeout' ? 'Takeout' : 'Dine-in · table ' . $order->table_number }}</span></td>
                                <td class="px-5 py-4 text-slate-500">{{ $order->created_at?->format('g:i A') }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-900">₱{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('cashier.orders.payment', $order) }}" class="inline-flex items-center gap-1 rounded-lg bg-amber-500 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600">Complete payment <span aria-hidden="true">→</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
