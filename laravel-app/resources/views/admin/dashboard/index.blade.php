@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div id="dashboard" data-url="{{ route('admin.dashboard.data') }}" class="max-w-6xl mx-auto space-y-6">

    {{-- Page header + live indicator --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">A quick look at today's business activity.</p>
        </div>
        <div class="shrink-0 flex items-center gap-2 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-full px-3 py-1.5 shadow-sm">
            <span class="relative flex h-2 w-2">
                <span id="live-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span id="live-dot" class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span id="live-status">Live</span>
        </div>
    </div>

    {{-- Insights panel: business health score --}}
    <section id="health-panel" class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-slate-300 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Insights panel · Business health</p>
                <div class="mt-3 flex items-baseline flex-wrap gap-2">
                    <span id="health-score" class="text-5xl font-bold text-slate-300">—</span>
                    <span id="health-max" class="hidden text-lg font-medium text-slate-400">/ 100</span>
                    <span id="health-label" class="ml-2 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Not available</span>
                </div>
                <p id="health-summary" class="mt-3 text-sm text-slate-600 max-w-2xl"></p>
            </div>
            <a href="{{ route('admin.analytics.index') }}" class="shrink-0 text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors">View analytics →</a>
        </div>
    </section>

    {{-- Today's sales + active orders --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">₱</div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Today's sales</p>
            </div>
            <p id="sales-total" class="mt-4 text-3xl font-bold text-slate-900">₱0.00</p>
            <p id="sales-count" class="mt-1 text-xs text-slate-500">0 paid orders today</p>
        </section>

        <a href="{{ route('admin.orders.index') }}" class="block bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-amber-300 hover:shadow-md transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Active orders (live)</p>
            </div>
            <p id="active-count" class="mt-4 text-3xl font-bold text-slate-900">0 Orders</p>
            <p class="mt-1 text-xs text-slate-500">Pending / Preparing</p>
        </a>
    </div>

    {{-- Low stock alerts feed --}}
    <section class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-amber-400 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Low stock alerts feed</p>
                <span id="low-count" class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">0</span>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors">View inventory →</a>
        </div>

        <ul id="low-list" class="divide-y divide-slate-100"></ul>
        <p id="low-empty" class="hidden px-6 py-8 text-center text-sm text-slate-400">All ingredients are above their restock thresholds.</p>
        <p id="low-more" class="hidden px-6 py-3 text-xs font-medium text-slate-500 bg-slate-50 border-t border-slate-100"></p>
    </section>
</div>

<script>
(function () {
    const initial = @json($dashboard);
    const root = document.getElementById('dashboard');
    const url = root.dataset.url;
    const POLL_MS = 3000; // refresh of the real-time
    const $ = (id) => document.getElementById(id);

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const BANDS = {
        good: { border: 'border-l-emerald-500', text: 'text-emerald-600', pill: 'bg-emerald-50 text-emerald-700', label: 'Healthy' },
        fair: { border: 'border-l-amber-500', text: 'text-amber-600', pill: 'bg-amber-50 text-amber-700', label: 'Needs attention' },
        poor: { border: 'border-l-red-500', text: 'text-red-600', pill: 'bg-red-50 text-red-700', label: 'At risk' },
        none: { border: 'border-l-slate-300', text: 'text-slate-300', pill: 'bg-slate-100 text-slate-500', label: 'Not available' },
    };

    function renderHealth(health) {
        const band = BANDS[health.band] ?? BANDS.none;
        $('health-panel').className = 'bg-white rounded-2xl border border-slate-200 border-l-4 shadow-sm p-6 ' + band.border;
        $('health-score').className = 'text-5xl font-bold ' + band.text;
        $('health-score').textContent = health.available ? health.score : '—';
        $('health-max').classList.toggle('hidden', !health.available);
        $('health-label').className = 'ml-2 text-xs font-semibold px-2.5 py-1 rounded-full ' + band.pill;
        $('health-label').textContent = band.label;
        $('health-summary').textContent = health.available
            ? health.summary
            : 'The analytics engine needs more recorded activity before it can compute your business health score.';
    }

    function renderSales(sales) {
        $('sales-total').textContent = '₱' + sales.total;
        $('sales-count').textContent = sales.count + (sales.count === 1 ? ' paid order today' : ' paid orders today');
    }

    function renderActiveOrders(orders) {
        $('active-count').textContent = orders.count + (orders.count === 1 ? ' Order' : ' Orders');
    }

    function renderLowStock(low) {
        $('low-count').textContent = low.count;
        $('low-count').className = 'text-xs font-bold px-2 py-0.5 rounded-full ' + (low.count > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600');
        $('low-empty').classList.toggle('hidden', low.count > 0);
        $('low-more').classList.toggle('hidden', low.count <= low.items.length);
        $('low-more').textContent = '+ ' + (low.count - low.items.length) + ' more in Inventory';

        $('low-list').innerHTML = low.items.map((item) => `
            <li class="flex items-center justify-between gap-4 px-6 py-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate">${esc(item.name)}</p>
                    <p class="text-xs text-slate-500">${esc(item.current)} ${esc(item.unit)} remaining · threshold ${esc(item.threshold)} ${esc(item.unit)}</p>
                </div>
                <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full ${item.out ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700'}">${item.out ? 'Out of stock' : 'Low stock'}</span>
            </li>`).join('');
    }

    function setLive(ok, updatedAt) {
        $('live-status').textContent = ok ? 'Live · updated ' + updatedAt : 'Connection lost · retrying';
        $('live-dot').className = 'relative inline-flex rounded-full h-2 w-2 ' + (ok ? 'bg-emerald-500' : 'bg-amber-500');
        $('live-ping').classList.toggle('hidden', !ok);
    }

    function render(data) {
        renderHealth(data.health);
        renderSales(data.sales);
        renderActiveOrders(data.activeOrders);
        renderLowStock(data.lowStock);
        setLive(true, data.updatedAt);
    }

    async function refresh() {
        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Request failed: ' + response.status);
            render(await response.json());
        } catch (error) {
            console.error('Dashboard refresh failed', error);
            setLive(false);
        }
    }

    // Poll while the tab is visible; pause when it is hidden.
    let timer = null;
    const start = () => { if (!timer) timer = setInterval(refresh, POLL_MS); };
    const stop = () => { clearInterval(timer); timer = null; };

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stop();
        } else {
            refresh();
            start();
        }
    });

    render(initial);
    start();
})();
</script>
@endsection