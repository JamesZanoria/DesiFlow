@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div id="sales-report" data-url="{{ route('admin.sales-report.data') }}" class="flex flex-col h-full bg-slate-50/50 p-4 md:p-8">

    {{-- Header: title + filters + live indicator --}}
    <div class="flex items-start justify-between mb-8 gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Sales Report</h1>
            <p class="text-sm text-slate-500 mt-1 font-medium">Revenue and order totals for the selected period.</p>
        </div>

        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Filters:</span>
                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm" role="group" aria-label="Report period">
                    @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
                        <button type="button" data-period="{{ $key }}"
                            class="period-btn px-4 py-1.5 rounded-md text-sm font-semibold transition-all {{ $period === $key ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-full px-3 py-1.5 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span id="live-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span id="live-dot" class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span id="live-status">Live · updated {{ $report['updatedAt'] }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Key Metrics --}}
        <section class="lg:col-span-2 bg-white border border-slate-100 rounded-xl shadow-sm p-8 transition-all hover:shadow-md self-start">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Key Metrics</h2>
            </div>
            <p id="period-label" class="mt-3 text-xs font-medium text-slate-500">{{ $report['periodLabel'] }}</p>

            <div class="mt-6 pt-6 border-t border-slate-100 space-y-8">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Sales</p>
                    <p id="total-sales" class="mt-2 text-4xl font-extrabold text-slate-900 tracking-tight">₱{{ $report['totalSales'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Orders</p>
                    <p id="total-orders" class="mt-2 text-4xl font-extrabold text-slate-900 tracking-tight">{{ number_format($report['totalOrders']) }}</p>
                </div>
            </div>
        </section>

        {{-- Revenue Trends --}}
        <section class="lg:col-span-3 bg-white border border-slate-100 rounded-xl shadow-sm p-8 transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                    </div>
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Revenue Trends</h2>
                </div>
                <span class="text-[10px] font-bold text-gray-400 px-3 py-1 bg-slate-100 rounded-full uppercase tracking-widest">Revenue (₱)</span>
            </div>

            <div class="relative h-80 w-full">
                <canvas id="revenue-chart"></canvas>
                <div id="chart-empty" class="{{ $report['hasSales'] ? 'hidden' : '' }} absolute inset-0 flex items-center justify-center pointer-events-none">
                    <p class="text-sm font-medium text-slate-400 bg-white/90 px-4 py-2 rounded-lg">No sales recorded for this period yet.</p>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('sales-report');
    const url = root.dataset.url;
    const initial = @json($report);
    const POLL_MS = 3000; // refresh every 3 seconds
    const $ = (id) => document.getElementById(id);

    let period = initial.period;
    let renderedPeriod = initial.period;
    let chart = null;
    let timer = null;

    // Full class names so Tailwind can find them.
    const ACTIVE = ['bg-amber-500', 'text-white', 'shadow-sm'];
    const INACTIVE = ['text-slate-600', 'hover:bg-slate-50'];

    // Uses the Chart.js the layout already loads; falls back to a CDN copy if it isn't global.
    function loadChart() {
        if (window.Chart) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function createChart() {
        const canvas = $('revenue-chart');
        if (!window.Chart || !canvas) return;

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
        const tickFont = { size: 11, family: 'Inter, system-ui, sans-serif' };

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: initial.chart.labels,
                datasets: [{
                    label: 'Revenue',
                    data: initial.chart.values,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 2,
                    pointHoverRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (item) => ' ₱' + Number(item.parsed.y).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: tickFont, callback: (value) => '₱' + Number(value).toLocaleString('en-PH') },
                    },
                    x: { grid: { display: false }, ticks: { font: tickFont, maxRotation: 0, autoSkip: true } },
                },
            },
        });
    }

    function setLive(ok, updatedAt) {
        $('live-status').textContent = ok ? 'Live · updated ' + updatedAt : 'Connection lost · retrying';
        $('live-dot').className = 'relative inline-flex rounded-full h-2 w-2 ' + (ok ? 'bg-emerald-500' : 'bg-amber-500');
        $('live-ping').classList.toggle('hidden', !ok);
    }

    function setActiveButton(selected) {
        document.querySelectorAll('.period-btn').forEach((btn) => {
            const isActive = btn.dataset.period === selected;
            btn.classList.remove(...ACTIVE, ...INACTIVE);
            btn.classList.add(...(isActive ? ACTIVE : INACTIVE));
        });
    }

    function render(data) {
        $('total-sales').textContent = '₱' + data.totalSales;
        $('total-orders').textContent = Number(data.totalOrders).toLocaleString();
        $('period-label').textContent = data.periodLabel;
        $('chart-empty').classList.toggle('hidden', data.hasSales);

        if (chart) {
            chart.data.labels = data.chart.labels;
            chart.data.datasets[0].data = data.chart.values;
            // Animate when the filter changes; update silently on routine refreshes.
            chart.update(data.period === renderedPeriod ? 'none' : undefined);
        }

        renderedPeriod = data.period;
        setLive(true, data.updatedAt);
    }

    async function refresh() {
        const requested = period;
        try {
            const response = await fetch(url + '?period=' + encodeURIComponent(requested), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Request failed: ' + response.status);
            const data = await response.json();
            if (requested !== period) return; // filter changed while this request was running
            render(data);
        } catch (error) {
            console.error('Sales report refresh failed', error);
            setLive(false);
        }
    }

    document.querySelectorAll('.period-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.dataset.period === period) return;
            period = btn.dataset.period;
            setActiveButton(period);
            history.replaceState(null, '', '?period=' + period);
            refresh();
        });
    });

    // Poll while the tab is visible; pause while it is hidden.
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

    loadChart()
        .catch((error) => console.error('Chart.js could not be loaded', error))
        .finally(() => {
            createChart();
            render(initial);
            start();
        });
})();
</script>
@endsection