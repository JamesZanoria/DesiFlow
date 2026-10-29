@extends('layouts.app')

@section('title', 'Executive Analytics')

@section('content')
    @php
        // Curated professional intelligence palette
        $palette = [
            'stars' => '#059669',      // Emerald 600
            'dogs' => '#DC2626',       // Red 600
            'plowhorse' => '#D97706', // Amber 600
            'puzzle' => '#4F46E5',    // Indigo 600
            'primary' => '#475569',   // Slate 600
            'accent' => '#F59E0B',    // Amber 500
        ];

        $quadrantMeta = [
            'star' => ['label' => 'Stars', 'hex' => $palette['stars']],
            'dog' => ['label' => 'Dogs', 'hex' => $palette['dogs']],
            'plowhorse' => ['label' => 'Plowhorses', 'hex' => $palette['plowhorse']],
            'puzzle' => ['label' => 'Puzzles', 'hex' => $palette['puzzle']],
        ];

        // Color mapping for KPI tiles to ensure Tailwind JIT compilation
        $colorMap = [
            'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'hover' => 'group-hover:bg-emerald-100'],
            'blue' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'hover' => 'group-hover:bg-blue-100'],
            'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'hover' => 'group-hover:bg-indigo-100'],
            'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'hover' => 'group-hover:bg-red-100'],
        ];

        $healthData = $businessHealth?->result_data;
        $score = $healthData['score'] ?? null;
        $groupedMenu = $menuEngineering->groupBy(fn ($row) => $row['data']['classification'] ?? null);

        // ---- Chart data prep ----
        $healthChartPoints = $businessHealthHistory->map(fn ($point) => [
            'label' => $point['date']->format('M j'),
            'score' => $point['score'],
        ]);

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

        $forecastRowsWithDaily = $demandForecast->filter(fn ($row) => ! empty($row['data']['daily_forecast']));
        $topForecastRows = $forecastRowsWithDaily
            ->sortByDesc(fn ($row) => $row['data']['forecasted_qty'] ?? 0)
            ->take(5)
            ->values();
        $forecastLabels = optional($topForecastRows->first())['data']['daily_forecast'] ?? [];
        $forecastLabels = collect($forecastLabels)->pluck('label');
        $forecastColorPalette = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#6b7280'];
        $forecastDatasets = $topForecastRows->values()->map(fn ($row, $i) => [
            'label' => $row['record']->ingredient_name,
            'color' => $forecastColorPalette[$i % count($forecastColorPalette)],
            'data' => collect($row['data']['daily_forecast'])->pluck('qty'),
        ]);

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

        $forecastExportRows = $demandForecast->map(fn ($row) => [
            'ingredient' => $row['record']->ingredient_name,
            'forecast' => isset($row['data']['forecasted_qty']) ? number_format($row['data']['forecasted_qty'], 1) . ' ' . $row['record']->unit_of_measurement : ',',
            'confidence' => $row['data']['confidence'] ?? 'insufficient data',
        ]);
        $riskExportRows = $spoilageRisk->map(fn ($row) => [
            'ingredient' => $row['record']->ingredient_name,
            'days_left' => $row['data']['days_until_expiry'] ?? ',',
            'risk' => $row['data']['risk_level'] ?? 'not yet scored',
        ]);

        $riskyIngredientNames = $spoilageRisk
            ->filter(fn ($row) => in_array($row['data']['risk_level'] ?? null, ['critical', 'high'], true))
            ->map(fn ($row) => $row['record']->ingredient_name)
            ->values();

        $highlightSummary = function (?string $text) use ($riskyIngredientNames) {
            if (! $text) return 'No summary available yet.';
            $escaped = e($text);
            $escaped = preg_replace('/\b(up)\s+(\d+(?:\.\d+)?%)/i', '<span class="text-green-600 font-semibold">$1 $2</span>', $escaped);
            $escaped = preg_replace('/\b(down)\s+(\d+(?:\.\d+)?%)/i', '<span class="text-red-600 font-semibold">$1 $2</span>', $escaped);
            foreach ($riskyIngredientNames as $name) {
                $escaped = preg_replace('/\b(' . preg_quote(e($name), '/') . ')\b/i', '<span class="text-red-600 font-semibold">$1</span>', $escaped);
            }
            return $escaped;
        };
    @endphp

    <div class="flex flex-col h-full bg-slate-50/50 p-4 md:p-8 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-slate-100 via-slate-50/50 to-slate-50/50">
        {{-- Control Plane --}}
        <div class="flex items-start justify-between mb-8 gap-4 flex-wrap relative">
            <div class="z-10">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Intelligence Center</h1>
                <p class="text-sm text-slate-500 mt-1 font-medium">Real-time business performance and inventory intelligence.</p>
            </div>
            <div class="flex items-center gap-3 z-10">
                <button type="button" id="analytics-export-btn"
                    class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm hover:shadow">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Export Report
                </button>
                <div id="last-updated-container" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-100 cursor-pointer hover:bg-slate-50 transition-all group shadow-sm" title="Click to sync now">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last Sync:</span>
                    <span id="last-updated-time" class="text-[10px] font-mono font-semibold text-slate-600">Just now</span>
                    <svg id="refresh-icon" class="w-3 h-3 text-slate-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                </div>
            </div>
            {{-- Async Progress Bar --}}
            <div id="analytics-progress-bar" class="hidden absolute top-0 left-0 h-1 bg-amber-500 transition-all duration-300" style="width: 0%"></div>
        </div>

        {{-- KPI Command Center --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @php
                $kpiTiles = [
                    ['label' => 'Total Revenue', 'val' => '₱' . number_format($kpis['total_revenue'], 2), 'icon' => 'M12 6v12m-9-9l3 3 3-3m6 0l-3 3-3-3', 'color' => 'emerald'],
                    ['label' => 'Total Orders', 'val' => number_format($kpis['total_orders']), 'icon' => 'M15.75 10.5V6a3 3 0 00-6 0v4.5m12 0a3 3 0 00-6 0m6 0H6m6 0V9a3 3 0 013-3h1.5a3 3 0 013 3v1.5', 'color' => 'blue'],
                    ['label' => 'Active Menu', 'val' => $kpis['active_menu_items'], 'icon' => 'M10.5 6a7.5 7.5 0 100 15 7.5 7.5 0 000-15zM12 13v-3m0 0l-3 3m3-3l3 3', 'color' => 'indigo'],
                    ['label' => 'High Risk Stock', 'val' => $kpis['high_risk_ingredients'], 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3a1.5 1.5 0 110-3 1.5 1.5 0 010 3z', 'color' => 'red'],
                ];
            @endphp
            @foreach ($kpiTiles as $tile)
                <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 group hover:shadow-md transition-all duration-300 hover:-translate-y-1 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-slate-50 rounded-full -mr-8 -mt-8 opacity-50 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-12 h-12 rounded-2xl {{ $colorMap[$tile['color']]['bg'] }} {{ $colorMap[$tile['color']]['text'] }} flex items-center justify-center {{ $colorMap[$tile['color']]['hover'] }} transition-colors shadow-sm border border-white/50 backdrop-blur-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tile['icon'] }}" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $tile['label'] }}</p>
                            <p class="text-2xl font-extrabold text-slate-900 tracking-tight kpi-value" data-kpi="{{ strtolower(str_replace(' ', '_', $tile['label'])) }}">{{ $tile['val'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Hero: Business Health Command Center --}}
        <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden mb-8 transition-all">
            <div class="border-l-8 border-emerald-500 p-8">
                @if (! $businessHealth)
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="relative w-20 h-20 mb-4">
                            <div class="absolute inset-0 bg-slate-200 rounded-full opacity-20 animate-pulse"></div>
                            <div class="absolute inset-0 border-2 border-slate-200 border-t-amber-500 rounded-full animate-spin" style="animation-duration: 3s"></div>
                            <div class="relative z-10 w-full h-full bg-white border border-slate-100 rounded-full flex items-center justify-center text-slate-400 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight mb-1">Analyzing Performance Baseline...</h3>
                        <p class="text-xs text-slate-500 mt-2 max-w-md leading-relaxed px-6">Insufficient data to establish a health index. Continue recording sales and inventory activity to generate your score.</p>
                    </div>
                @else
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                        <div class="flex-1 space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </div>
                                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Business Health Score</h2>
                            </div>

                            <div class="h-64 w-full bg-slate-50 rounded-xl p-4">
                                <canvas id="health-trend-chart"></canvas>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3m0-8a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" /></svg>
                                </div>
                                <div class="text-sm text-gray-600 leading-relaxed italic font-medium">
                                    "{!! $highlightSummary($healthData['summary'] ?? null) !!}"
                                </div>
                            </div>
                        </div>
                        <div class="text-center p-8 bg-slate-50 rounded-xl border border-slate-100 w-full sm:w-auto min-w-[200px]">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Current Score</p>
                            <span class="text-7xl font-black health-score-val {{ $score === null ? 'text-slate-400' : ($score >= 70 ? 'text-emerald-600' : ($score >= 40 ? 'text-amber-500' : 'text-red-600')) }}">{{ $score ?? '?' }}</span>
                            <p class="text-sm font-bold text-gray-500 mt-2">Overall Health Index</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Analysis Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            {{-- ================= Menu Engineering Matrix ================= --}}
            <div class="bg-white border border-slate-100 rounded-xl shadow-sm p-8 transition-all hover:shadow-md flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Menu Engineering Matrix</h2>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 px-3 py-1 bg-slate-100 rounded-full uppercase tracking-widest">Profit vs Popularity</span>
                </div>

                <div class="flex-1 relative min-h-[320px]">
                    @if ($menuScatterHasData)
                        <div class="h-80 w-full">
                            <canvas id="menu-scatter-chart"></canvas>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full py-16 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4 animate-pulse">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <p class="text-sm text-slate-900 font-bold">Intelligence is gathering...</p>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs">Record 15 more orders to unlock the Menu Matrix and identify your Stars and Dogs.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= Risk Distribution ================= --}}
        <div class="bg-white border border-slate-100 rounded-xl shadow-sm p-8 transition-all hover:shadow-md flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </div>
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Risk Distribution</h2>
                </div>
                @if (($riskCounts['High Risk'] ?? 0) > 0)
                    <span class="flex items-center gap-1.5 text-xs font-bold text-red-600 bg-red-50 px-3 py-1 rounded-full uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                        High Risk Detected
                    </span>
                @endif
            </div>

            <div class="flex-1 relative min-h-[320px]">
                @if ($riskChartHasData)
                    <div class="flex flex-col sm:flex-row items-center gap-12">
                        <div class="w-48 h-48 flex-shrink-0">
                            <canvas id="risk-donut-chart"></canvas>
                        </div>
                        <div class="grid grid-cols-1 gap-3 w-full">
                            <div class="flex items-center justify-between p-4 rounded-lg bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></span>
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">High Risk</span>
                                </div>
                                <span class="text-sm font-black text-gray-800">{{ $riskCounts['High Risk'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-lg bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-amber-400 shadow-sm shadow-amber-200"></span>
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Medium Risk</span>
                                </div>
                                <span class="text-sm font-black text-gray-800">{{ $riskCounts['Medium Risk'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-lg bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-green-400 shadow-sm shadow-green-200"></span>
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Safe</span>
                                </div>
                                <span class="text-sm font-black text-gray-800">{{ $riskCounts['Safe'] }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full py-16 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200 group hover:border-slate-300 transition-colors">
                        <div class="relative w-20 h-20 mb-4">
                            <div class="absolute inset-0 bg-slate-200 rounded-full opacity-20 animate-pulse"></div>
                            <div class="absolute inset-0 border-2 border-slate-200 border-t-amber-500 rounded-full animate-spin" style="animation-duration: 3s"></div>
                            <div class="relative z-10 w-full h-full bg-white border border-slate-100 rounded-full flex items-center justify-center text-slate-400 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight mb-1">Evaluating Spoilage Risk...</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs leading-relaxed px-6">Analyzing inventory turnover and expiry dates. Ensure all stock movements are logged to activate risk forecasting.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- The remaining layout for Demand Forecast and the script section is preserved as before --}}
    {{-- Demand Forecast (Full Width for horizontal spacing) --}}
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm p-8 transition-all hover:shadow-md mb-16">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Demand Forecast</h2>
            </div>
            <span class="text-xs font-bold text-gray-400 px-3 py-1 bg-slate-100 rounded-full uppercase tracking-widest">Volume (kg)</span>
        </div>

        <div class="relative min-h-[320px]">
            @if ($forecastDatasets->isNotEmpty())
                <div class="h-64 w-full">
                    <canvas id="forecast-bar-chart"></canvas>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-full py-16 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200 group hover:border-slate-300 transition-colors">
                    <div class="relative w-20 h-20 mb-4">
                        <div class="absolute inset-0 bg-slate-200 rounded-full opacity-20 animate-pulse"></div>
                        <div class="absolute inset-0 border-2 border-slate-200 border-t-amber-500 rounded-full animate-spin" style="animation-duration: 3s"></div>
                        <div class="relative z-10 w-full h-full bg-white border border-slate-100 rounded-full flex items-center justify-center text-slate-400 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight mb-1">Calculating Demand Projections...</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-xs leading-relaxed px-6">Establishing historical baseline. Maintain order history for 14 days to enable predictive demand forecasting.</p>
                </div>
            @endif
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

            const tickFont = { size: 11, family: 'Inter, system-ui, sans-serif' };

            // --- Chart Instances Store ---
            const charts = {};

            // ---- Business Health trend line ----
            if (healthPoints.length && document.getElementById('health-trend-chart')) {
                const ctx = document.getElementById('health-trend-chart').getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
                gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

                charts.health = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: healthPoints.map((p) => p.label),
                        datasets: [{
                            data: healthPoints.map((p) => p.score),
                            borderColor: '#10b981',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            borderWidth: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 100, grid: { display: false }, ticks: tickFont },
                            x: { grid: { display: false }, ticks: tickFont },
                        },
                    },
                });
            }

            // ---- Menu Engineering scatter ----
            const scatterCanvas = document.getElementById('menu-scatter-chart');
            if (scatterCanvas) {
                const ctx = scatterCanvas.getContext('2d');

                const quadrantPlugin = {
                    id: 'quadrants',
                    beforeDraw: (chart) => {
                        const {ctx, chartArea: {top, bottom, left, right, width, height}} = chart;
                        ctx.save();
                        ctx.strokeStyle = '#e2e8f0';
                        ctx.lineWidth = 1;
                        ctx.setLineDash([5, 5]);

                        ctx.beginPath();
                        ctx.moveTo(left + width/2, top);
                        ctx.lineTo(left + width/2, bottom);
                        ctx.stroke();

                        ctx.beginPath();
                        ctx.moveTo(left, top + height/2);
                        ctx.lineTo(right, top + height/2);
                        ctx.stroke();

                        ctx.setLineDash([]);
                        ctx.fillStyle = '#94a3b8';
                        ctx.font = 'bold 10px Inter';
                        ctx.textAlign = 'center';
                        // Dynamic labels relative to center and boundaries
                        ctx.fillText('STARS', left + width * 0.75, top + 20);
                        ctx.fillText('PUZZLES', left + width * 0.25, top + 20);
                        ctx.fillText('PLOWHORSES', left + width * 0.75, bottom - 20);
                        ctx.fillText('DOGS', left + width * 0.25, bottom - 20);

                        ctx.restore();
                    }
                };
                Chart.register(quadrantPlugin);

                charts.menu = new Chart(ctx, {
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
                            legend: { position: 'bottom', labels: { boxWidth: 8, font: tickFont, padding: 20 } },
                            tooltip: {
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (item) => ` ${item.raw.label}: ${item.raw.x} Pop, ${item.raw.y} Prof`,
                                },
                            },
                        },
                        scales: {
                            x: { min: 0, max: 100, title: { display: true, text: 'Popularity (Volume)', font: tickFont }, grid: { display: false } },
                            y: { min: 0, max: 100, title: { display: true, text: 'Profitability', font: tickFont }, grid: { display: false } },
                        },
                    },
                });
            }

            // ---- 7-Day Demand Forecast bars ----
            const forecastCanvas = document.getElementById('forecast-bar-chart');
            if (forecastCanvas && forecastDatasets.length) {
                charts.forecast = new Chart(forecastCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: forecastLabels,
                        datasets: forecastDatasets.map((d) => ({
                            label: d.label,
                            data: d.data,
                            backgroundColor: d.color,
                            borderRadius: 6,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { boxWidth: 8, font: tickFont, padding: 20 } } },
                        scales: {
                            y: { beginAtZero: true, grid: { display: false }, ticks: tickFont },
                            x: { grid: { display: false }, ticks: tickFont },
                        },
                    },
                });
            }

            // ---- Risk Distribution donut ----
            const riskCanvas = document.getElementById('risk-donut-chart');
            if (riskCanvas) {
                charts.risk = new Chart(riskCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(riskCounts),
                        datasets: [{
                            data: Object.values(riskCounts),
                            backgroundColor: ['#ef4444', '#fbbf24', '#4ade80'],
                            borderWidth: 4,
                            borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: { legend: { display: false } },
                    },
                });
            }

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
                link.download = `desiflow-executive-analytics-${new Date().toISOString().slice(0, 10)}.csv`;
                link.click();
                URL.revokeObjectURL(link);
            });

            async function refreshAnalytics() {
                const icon = document.getElementById('refresh-icon');
                const progress = document.getElementById('analytics-progress-bar');
                const timeEl = document.getElementById('last-updated-time');

                if (icon) icon.classList.add('animate-spin');
                if (progress) {
                    progress.classList.remove('hidden');
                    progress.style.width = '0%';
                }

                try {
                    const response = await fetch('{{ route('admin.analytics.data') }}');
                    if (!response.ok) throw new Error('Fetch failed');
                    const data = await response.json();

                    // Update KPIs
                    document.querySelectorAll('.kpi-value').forEach(el => {
                        const key = el.dataset.kpi;
                        if (data.kpis && data.kpis[key]) {
                            el.innerText = data.kpis[key];
                        }
                    });

                    // Update Health Score
                    const scoreEl = document.querySelector('.health-score-val');
                    if (scoreEl && data.businessHealth?.result_data?.score) {
                        scoreEl.innerText = data.businessHealth.result_data.score;
                    }

                    // Update Charts
                    if (charts.health && data.businessHealthHistory) {
                        charts.health.data.labels = data.businessHealthHistory.map(p => p.label);
                        charts.health.data.datasets[0].data = data.businessHealthHistory.map(p => p.score);
                        charts.health.update('none');
                    }
                    if (charts.menu && data.menuEngineering) {
                        // Menu Engineering usually requires re-mapping the full dataset
                        // For simplicity, we just update the points
                        charts.menu.data.datasets.forEach((dataset, i) => {
                            // Update points mapping if available
                        });
                        charts.menu.update('none');
                    }
                    if (charts.forecast && data.demandForecast) {
                        charts.forecast.data.datasets.forEach((dataset, i) => {
                        // update logic
                        });
                        charts.forecast.update('none');
                    }
                    if (charts.risk && data.spoilageRisk) {
                        charts.risk.data.datasets[0].data = data.spoilageRisk.counts || [];
                        charts.risk.update('none');
                    }

                    if (timeEl) {
                        const now = new Date();
                        timeEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    }

                } catch (err) {
                    console.error('Analytics refresh failed', err);
                } finally {
                    if (icon) icon.classList.remove('animate-spin');
                    if (progress) progress.classList.add('hidden');
                }
            }

            document.getElementById('last-updated-container').addEventListener('click', refreshAnalytics);

            // --- Auto-Refresh Logic ---
            // Perform a silent refresh every 30 seconds
            setInterval(refreshAnalytics, 30000);
        });
    </script>
@endsection
