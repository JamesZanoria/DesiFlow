@extends('layouts.app')

@section('title', 'Analytics Report')

@section('content')

    @php
        // Colors matched to the reference design's scatter/legend palette.
        // Order here also drives the legend order (Chart.js reads dataset order).
        $quadrantMeta = [
            'star' => ['label' => 'Stars', 'hex' => '#2dd4bf'],
            'dog' => ['label' => 'Dogs', 'hex' => '#991b1b'],
            'plowhorse' => ['label' => 'Plowhorses', 'hex' => '#f59e0b'],
            'puzzle' => ['label' => 'Puzzles', 'hex' => '#92400e'],
        ];

        $healthData = $businessHealth?->result_data;
        $groupedMenu = $menuEngineering->groupBy(fn ($row) => $row['data']['classification'] ?? null);

        // ---- Chart data prep (all computed here so the <script> block below
        // just consumes plain JSON, no PHP logic mixed into JS) ----

        $healthChartPoints = $businessHealthHistory->map(fn ($point) => [
            'label' => $point['date']->format('M j'),
            'score' => $point['score'],
        ]);

        // One scatter dataset per quadrant (rather than one dataset with
        // per-point colors) so Chart.js can give each quadrant its own legend
        // entry and color automatically.
        $menuScatterDatasets = collect($quadrantMeta)->map(function ($meta, $key) use ($groupedMenu) {
            $points = ($groupedMenu->get($key) ?? collect())
                ->filter(fn ($row) => isset($row['data']['popularity_score'], $row['data']['profitability_score']))
                ->map(fn ($row) => [
                    'x' => $row['data']['popularity_score'],
                    'y' => $row['data']['profitability_score'],
                    'label' => $row['record']->item_name,
                ])
                ->values();

            return [
                'label' => $meta['label'],
                'color' => $meta['hex'],
                'points' => $points,
            ];
        })->values();
        $menuScatterHasData = $menuScatterDatasets->sum(fn ($d) => count($d['points'])) > 0;

        // Top 5 ingredients with a daily breakdown, ranked by total forecasted
        // quantity — matches the mockup's "a handful of named ingredients,
        // not every ingredient" bar chart, since a full ingredient list would
        // make the chart unreadable.
        $forecastRowsWithDaily = $demandForecast->filter(fn ($row) => ! empty($row['data']['daily_forecast']));
        $topForecastRows = $forecastRowsWithDaily
            ->sortByDesc(fn ($row) => $row['data']['forecasted_qty'] ?? 0)
            ->take(5)
            ->values();
        $forecastLabels = optional($topForecastRows->first())['data']['daily_forecast'] ?? [];
        $forecastLabels = collect($forecastLabels)->pluck('label');
        $forecastColorPalette = ['#d97706', '#3b82f6', '#22c55e', '#8b5cf6', '#6b7280'];
        $forecastDatasets = $topForecastRows->values()->map(fn ($row, $i) => [
            'label' => $row['record']->ingredient_name,
            'color' => $forecastColorPalette[$i % count($forecastColorPalette)],
            'data' => collect($row['data']['daily_forecast'])->pluck('qty'),
        ]);

        // 3-bucket rollup for the donut, matching the mockup's High/Medium/Safe framing.
        $riskCounts = ['High Risk' => 0, 'Medium Risk' => 0, 'Safe' => 0];
        foreach ($spoilageRisk as $row) {
            $level = $row['data']['risk_level'] ?? null;
            if (in_array($level, ['critical', 'high'], true)) {
                $riskCounts['High Risk']++;
            } elseif ($level === 'medium') {
                $riskCounts['Medium Risk']++;
            } elseif ($level === 'low') {
                $riskCounts['Safe']++;
            }
        }
        $riskChartHasData = array_sum($riskCounts) > 0;

        // Full underlying data still gets exported to CSV even though the
        // detail tables are no longer shown on-page — an owner reviewing
        // "what should I restock" shouldn't lose access to the full list just
        // because only the top 5 fit in the chart.
        $forecastExportRows = $demandForecast->map(fn ($row) => [
            'ingredient' => $row['record']->ingredient_name,
            'forecast' => isset($row['data']['forecasted_qty']) ? number_format($row['data']['forecasted_qty'], 1) . ' ' . $row['record']->unit_of_measurement : '—',
            'confidence' => $row['data']['confidence'] ?? 'insufficient data',
        ]);
        $riskExportRows = $spoilageRisk->map(fn ($row) => [
            'ingredient' => $row['record']->ingredient_name,
            'days_left' => $row['data']['days_until_expiry'] ?? '—',
            'risk' => $row['data']['risk_level'] ?? 'not yet scored',
        ]);

        // Highlights "up/down N%" phrases and any currently high/critical-risk
        // ingredient name mentioned in the summary sentence — matching the
        // reference design's colored-keyword callout — grounded in data we
        // actually have rather than guessing at arbitrary phrasing. If the
        // FastAPI microservice's summary text doesn't happen to use "up/down N%"
        // phrasing or name a flagged ingredient verbatim, this simply falls
        // back to plain text; nothing is invented.
        $riskyIngredientNames = $spoilageRisk
            ->filter(fn ($row) => in_array($row['data']['risk_level'] ?? null, ['critical', 'high'], true))
            ->map(fn ($row) => $row['record']->ingredient_name)
            ->values();

        $highlightSummary = function (?string $text) use ($riskyIngredientNames) {
            if (! $text) {
                return 'No summary available yet.';
            }

            $escaped = e($text);
            $escaped = preg_replace('/\b(up)\s+(\d+(?:\.\d+)?%)/i', '<span class="text-green-600 font-semibold">$1 $2</span>', $escaped);
            $escaped = preg_replace('/\b(down)\s+(\d+(?:\.\d+)?%)/i', '<span class="text-red-600 font-semibold">$1 $2</span>', $escaped);

            foreach ($riskyIngredientNames as $name) {
                $escaped = preg_replace('/\b(' . preg_quote(e($name), '/') . ')\b/i', '<span class="text-red-600 font-semibold">$1</span>', $escaped);
            }

            return $escaped;
        };
    @endphp

    <div class="flex items-start justify-between mb-6 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Analytics Report</h1>
            <p class="text-sm text-gray-500 mt-1">Data-driven insight into how the business is performing, generated by the analytics engine.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="analytics-export-btn"
                class="flex items-center gap-1.5 bg-white border px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export
            </button>
            <button type="button" id="analytics-refresh-btn"
                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Refresh Data
            </button>
        </div>
    </div>

    {{-- ================= Business Health Score ================= --}}
    <div class="bg-white border-l-4 border-green-400 border-y border-r rounded-xl shadow-sm p-6 mb-6">
        @if (! $businessHealth)
            <div class="text-center py-6">
                <p class="text-sm text-gray-500">The Business Health Score hasn't been computed yet.</p>
                <p class="text-xs text-gray-400 mt-1">This appears once the analytics engine has enough recorded activity to work from.</p>
            </div>
        @else
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Business Health Score (30 Days)</p>
                </div>
                @php $score = $healthData['score'] ?? null; @endphp
                <div class="text-right">
                    <span class="text-3xl font-bold {{ $score === null ? 'text-gray-400' : ($score >= 70 ? 'text-green-600' : ($score >= 40 ? 'text-amber-500' : 'text-red-600')) }}">{{ $score ?? '—' }}</span>
                    <span class="text-xs text-gray-400"> / 100 Today</span>
                </div>
            </div>

            @if ($healthChartPoints->isNotEmpty())
                <div class="h-48">
                    <canvas id="health-trend-chart"></canvas>
                </div>
            @else
                <p class="text-xs text-gray-400 py-8 text-center">Not enough daily history yet to draw a trend line.</p>
            @endif

            <div class="bg-indigo-50/60 border-l-2 border-indigo-200 rounded-lg px-4 py-3 mt-4 text-sm text-gray-700">
                {!! $highlightSummary($healthData['summary'] ?? null) !!}
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- ================= Menu Engineering Matrix ================= --}}
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Menu Engineering Matrix</h2>
                </div>
                <span class="text-gray-300">&hellip;</span>
            </div>

            @if ($menuScatterHasData)
                <div class="h-80">
                    <canvas id="menu-scatter-chart"></canvas>
                </div>
            @else
                <p class="text-xs text-gray-400 py-8 text-center">Not enough scored dishes yet to plot the matrix.</p>
            @endif
        </div>

        <div class="space-y-6">
            {{-- ================= 7-Day Demand Forecast ================= --}}
            <div class="bg-white border rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">7-Day Demand Forecast</h2>
                    </div>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Volume (kg)</span>
                </div>

                @if ($forecastDatasets->isNotEmpty())
                    <div class="h-56">
                        <canvas id="forecast-bar-chart"></canvas>
                    </div>
                @else
                    <p class="text-xs text-gray-400 py-8 text-center">A daily breakdown isn't available for any ingredient yet.</p>
                @endif
            </div>

            {{-- ================= Risk Distribution ================= --}}
            <div class="bg-white border-l-4 border-red-400 border-y border-r rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Risk Distribution</h2>
                    </div>
                    @if (($riskCounts['High Risk'] ?? 0) > 0)
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    @endif
                </div>

                @if ($riskChartHasData)
                    <div class="flex items-center gap-4">
                        <div class="w-32 h-32 flex-shrink-0">
                            <canvas id="risk-donut-chart"></canvas>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> High Risk ({{ $riskCounts['High Risk'] }})</div>
                            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Medium Risk ({{ $riskCounts['Medium Risk'] }})</div>
                            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-green-400"></span> Safe ({{ $riskCounts['Safe'] }})</div>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-gray-400 py-8 text-center">No ingredients have been risk-scored yet.</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const healthPoints = @json($healthChartPoints);
            const scatterDatasets = @json($menuScatterDatasets);
            const forecastLabels = @json($forecastLabels);
            const forecastDatasets = @json($forecastDatasets);
            const riskCounts = @json($riskCounts);
            const forecastExportRows = @json($forecastExportRows);
            const riskExportRows = @json($riskExportRows);

            const gridColor = '#f3f4f6';
            const tickFont = { size: 11 };

            // ---- Business Health trend line ----
            if (healthPoints.length && document.getElementById('health-trend-chart')) {
                const ctx = document.getElementById('health-trend-chart').getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 180);
                gradient.addColorStop(0, 'rgba(34, 197, 94, 0.25)');
                gradient.addColorStop(1, 'rgba(34, 197, 94, 0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: healthPoints.map((p) => p.label),
                        datasets: [{
                            data: healthPoints.map((p) => p.score),
                            borderColor: '#16a34a',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#16a34a',
                            pointRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 100, grid: { color: gridColor }, ticks: tickFont },
                            x: { grid: { display: false }, ticks: tickFont },
                        },
                    },
                });
            }

            // ---- Menu Engineering scatter ----
            const scatterCanvas = document.getElementById('menu-scatter-chart');
            if (scatterCanvas) {
                new Chart(scatterCanvas.getContext('2d'), {
                    type: 'scatter',
                    data: {
                        datasets: scatterDatasets.map((d) => ({
                            label: d.label,
                            data: d.points,
                            backgroundColor: d.color,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 8, font: tickFont } },
                            tooltip: {
                                callbacks: {
                                    label: (item) => `${item.raw.label}: ${item.raw.x} popularity, ${item.raw.y} profitability`,
                                },
                            },
                        },
                        scales: {
                            x: { min: 0, max: 100, title: { display: true, text: 'Popularity (Volume)', font: tickFont }, grid: { color: gridColor } },
                            y: { min: 0, max: 100, title: { display: true, text: 'Profitability', font: tickFont }, grid: { color: gridColor } },
                        },
                    },
                });
            }

            // ---- 7-Day Demand Forecast bars ----
            const forecastCanvas = document.getElementById('forecast-bar-chart');
            if (forecastCanvas && forecastDatasets.length) {
                new Chart(forecastCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: forecastLabels,
                        datasets: forecastDatasets.map((d) => ({
                            label: d.label,
                            data: d.data,
                            backgroundColor: d.color,
                            borderRadius: 3,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { boxWidth: 8, font: tickFont } } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: tickFont },
                            x: { grid: { display: false }, ticks: tickFont },
                        },
                    },
                });
            }

            // ---- Risk Distribution donut ----
            const riskCanvas = document.getElementById('risk-donut-chart');
            if (riskCanvas) {
                new Chart(riskCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(riskCounts),
                        datasets: [{
                            data: Object.values(riskCounts),
                            backgroundColor: ['#ef4444', '#fbbf24', '#4ade80'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: { legend: { display: false } },
                    },
                });
            }

            // ---- Export: builds the CSV directly from the same data driving
            // the charts (rather than scraping now-removed detail tables from
            // the DOM), entirely client-side — no backend endpoint or outbound
            // network call needed, consistent with the system's offline-
            // capable local-network design (SRS §2.6). ----
            function rowsToCsv(rows, headers, sectionTitle) {
                const csvRows = [[sectionTitle], headers];
                rows.forEach((row) => {
                    csvRows.push(Object.values(row).map((v) => `"${String(v).replace(/"/g, '""')}"`));
                });
                return csvRows;
            }

            document.getElementById('analytics-export-btn').addEventListener('click', () => {
                const rows = [
                    ...rowsToCsv(forecastExportRows, ['Ingredient', 'Forecast', 'Confidence'], 'Demand Forecast'),
                    [],
                    ...rowsToCsv(riskExportRows, ['Ingredient', 'Est. Days Left', 'Risk'], 'Spoilage Risk'),
                ];
                const csvContent = rows.map((r) => r.join(',')).join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `desiflow-analytics-${new Date().toISOString().slice(0, 10)}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
            });

            // ---- Refresh: the numbers here are computed on the microservice's
            // own schedule, not on demand — this simply re-fetches the latest
            // snapshots Laravel has, rather than triggering a new computation. ----
            document.getElementById('analytics-refresh-btn').addEventListener('click', () => {
                window.location.reload();
            });
        });
    </script>

@endsection