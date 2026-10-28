<?php $__env->startSection('title', 'Executive Analytics'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        // Colors matched to a professional, muted palette.
        $quadrantMeta = [
            'star' => ['label' => 'Stars', 'hex' => '#10b981'],      // Emerald 500
            'dog' => ['label' => 'Dogs', 'hex' => '#ef4444'],       // Red 500
            'plowhorse' => ['label' => 'Plowhorses', 'hex' => '#f59e0b'], // Amber 500
            'puzzle' => ['label' => 'Puzzles', 'hex' => '#6366f1'],   // Indigo 500
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
    ?>

    <div class="flex flex-col h-full bg-slate-50">
        
        <div class="flex items-start justify-between mb-8 gap-4 flex-wrap relative">
            <div class="z-10">
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Executive Analytics</h1>
                <p class="text-sm text-slate-500 mt-1">Advanced data-driven insights generated by the DesiFlow analytics engine.</p>
            </div>
            <div class="flex items-center gap-3 z-10">
                <button type="button" id="analytics-export-btn"
                    class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm hover:shadow">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Export Report
                </button>
                <button type="button" id="analytics-refresh-btn"
                    class="flex items-center gap-2 bg-amber-500 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-amber-600 transition-all shadow-md shadow-amber-200 active:scale-95">
                    <svg id="refresh-icon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Refresh Data
                </button>
            </div>
            
            <div id="analytics-progress-bar" class="hidden absolute top-0 left-0 h-1 bg-amber-500 transition-all duration-300" style="width: 0%"></div>
        </div>

        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php
                $kpiTiles = [
                    ['label' => 'Total Revenue', 'val' => '₱' . number_format($kpis['total_revenue'], 2), 'icon' => 'M12 6v12m-3-2.818l.879-1.255a1.779 1.779 0 011.784 0l.879 1.255M15 15H9m6 0a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
                    ['label' => 'Total Orders', 'val' => number_format($kpis['total_orders']), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 17h.01', 'color' => 'blue'],
                    ['label' => 'Active Menu', 'val' => $kpis['active_menu_items'], 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'color' => 'indigo'],
                    ['label' => 'High Risk Stock', 'val' => $kpis['high_risk_ingredients'], 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', 'color' => 'red'],
                ];
            ?>
            <?php $__currentLoopData = $kpiTiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 group hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-<?php echo e($tile['color']); ?>-50 text-<?php echo e($tile['color']); ?>-600 flex items-center justify-center group-hover:bg-<?php echo e($tile['color']); ?>-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e($tile['icon']); ?>" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest"><?php echo e($tile['label']); ?></p>
                            <p class="text-2xl font-black text-slate-900 tracking-tight"><?php echo e($tile['val']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden mb-8 transition-all">
            <div class="border-l-8 border-emerald-500 p-8">
                <?php if(! $businessHealth): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-20 h-20 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-6 animate-pulse">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Business Health Analysis Pending</h3>
                        <p class="text-sm text-slate-500 mt-2 max-w-md">The analytics engine requires a consistent stream of order and inventory data to compute your health score. Keep recording activity to unlock this insight.</p>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                        <div class="flex-1 space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Business Health Score</h2>
                            </div>

                            <div class="h-64 w-full bg-slate-50 rounded-2xl p-4">
                                <canvas id="health-trend-chart"></canvas>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3m0-8a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" /></svg>
                                </div>
                                <div class="text-sm text-slate-600 leading-relaxed italic font-medium">
                                    "<?php echo $highlightSummary($healthData['summary'] ?? null); ?>"
                                </div>
                            </div>
                        </div>
                        <div class="text-center p-8 bg-slate-50 rounded-3xl border border-slate-100 min-w-[240px]">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Current Score</p>
                            <span class="text-7xl font-black <?php echo e($score === null ? 'text-slate-400' : ($score >= 70 ? 'text-emerald-600' : ($score >= 40 ? 'text-amber-500' : 'text-red-600'))); ?>"><?php echo e($score ?? '?'); ?></span>
                            <p class="text-sm font-bold text-slate-500 mt-2">Overall Health Index</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-8 transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Menu Engineering Matrix</h2>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 px-3 py-1 bg-slate-100 rounded-full uppercase tracking-widest">Profit vs Popularity</span>
                </div>

                <?php if($menuScatterHasData): ?>
                    <div class="h-80 w-full">
                        <canvas id="menu-scatter-chart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <p class="text-sm text-slate-500">Insufficient data to plot the dish matrix. Keep recording orders to see your Stars and Dogs.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-8">
            
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-8 transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Demand Forecast</h2>
                    </div>
                    <span class="text-xs font-bold text-slate-400 px-3 py-1 bg-slate-100 rounded-full uppercase tracking-widest">Volume (kg)</span>
                </div>

                <?php if($forecastDatasets->isNotEmpty()): ?>
                    <div class="h-64 w-full">
                        <canvas id="forecast-bar-chart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-900">Forecasting data not yet available</p>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs">We need a baseline of order history to project future demand. Continue recording orders to unlock this.</p>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-8 transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Risk Distribution</h2>
                    </div>
                    <?php if(($riskCounts['High Risk'] ?? 0) > 0): ?>
                        <span class="flex items-center gap-1.5 text-xs font-bold text-red-600 bg-red-50 px-3 py-1 rounded-full uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            High Risk Detected
                        </span>
                    <?php endif; ?>
                </div>

                <?php if($riskChartHasData): ?>
                    <div class="flex flex-col sm:flex-row items-center gap-12">
                        <div class="w-48 h-48 flex-shrink-0">
                            <canvas id="risk-donut-chart"></canvas>
                        </div>
                        <div class="grid grid-cols-1 gap-3 w-full">
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></span>
                                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">High Risk</span>
                                </div>
                                <span class="text-sm font-black text-slate-900"><?php echo e($riskCounts['High Risk']); ?></span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-amber-400 shadow-sm shadow-amber-200"></span>
                                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Medium Risk</span>
                                </div>
                                <span class="text-sm font-black text-slate-900"><?php echo e($riskCounts['Medium Risk']); ?></span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-green-400 shadow-sm shadow-green-200"></span>
                                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Safe</span>
                                </div>
                                <span class="text-sm font-black text-slate-900"><?php echo e($riskCounts['Safe']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-900">No risk data available</p>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs">Your inventory is either too new or lacks the history required for risk scoring.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const healthPoints = <?php echo json_encode($healthChartPoints, 15, 512) ?>;
            const scatterDatasets = <?php echo json_encode($menuScatterDatasets, 15, 512) ?>;
            const forecastLabels = <?php echo json_encode($forecastLabels, 15, 512) ?>;
            const forecastDatasets = <?php echo json_encode($forecastDatasets, 15, 512) ?>;
            const riskCounts = <?php echo json_encode($riskCounts, 15, 512) ?>;
            const forecastExportRows = <?php echo json_encode($forecastExportRows, 15, 512) ?>;
            const riskExportRows = <?php echo json_encode($riskExportRows, 15, 512) ?>;

            const gridColor = 'transparent';
            const tickFont = { size: 11, family: 'Inter, system-ui, sans-serif' };

            // ---- Business Health trend line ----
            if (healthPoints.length && document.getElementById('health-trend-chart')) {
                const ctx = document.getElementById('health-trend-chart').getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
                gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: healthPoints.map((p) => p.label),
                        datasets: [{
                            data: healthPoints.map((p) => p.score),
                            borderColor: '#10b981',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#10b981',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
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

                // Custom plugin to draw quadrants
                const quadrantPlugin = {
                    id: 'quadrants',
                    beforeDraw: (chart) => {
                        const {ctx, chartArea: {top, bottom, left, right, width, height}} = chart;
                        ctx.save();
                        ctx.strokeStyle = '#e2e8f0';
                        ctx.lineWidth = 1;
                        ctx.setLineDash([5, 5]);

                        // Mid-lines
                        ctx.beginPath();
                        ctx.moveTo(left + width/2, top);
                        ctx.lineTo(left + width/2, bottom);
                        ctx.stroke();

                        ctx.beginPath();
                        ctx.moveTo(left, top + height/2);
                        ctx.lineTo(right, top + height/2);
                        ctx.stroke();

                        // Quadrant Labels
                        ctx.setLineDash([]);
                        ctx.fillStyle = '#94a3b8';
                        ctx.font = 'bold 10px Inter';
                        ctx.textAlign = 'center';
                        ctx.fillText('STARS', left + width * 0.75, top + 20);
                        ctx.fillText('PUZZLES', left + width * 0.25, top + 20);
                        ctx.fillText('PLOWHORSES', left + width * 0.75, bottom - 20);
                        ctx.fillText('DOGS', left + width * 0.25, bottom - 20);

                        ctx.restore();
                    }
                };
                Chart.register(quadrantPlugin);

                new Chart(ctx, {
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
                new Chart(forecastCanvas.getContext('2d'), {
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
                new Chart(riskCanvas.getContext('2d'), {
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
                    csvRows.push(Object.values(row).map((v) => `"${String(v).replace(/"/g, '""'")`));
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

            document.getElementById('analytics-refresh-btn').addEventListener('click', function() {
                const btn = this;
                const icon = document.getElementById('refresh-icon');
                const progress = document.getElementById('analytics-progress-bar');

                btn.disabled = true;
                icon.classList.add('animate-spin');
                progress.classList.remove('hidden');
                progress.style.width = '0%';

                // Simulate progress bar
                let width = 0;
                const interval = setInterval(() => {
                    width += 10;
                    progress.style.width = width + '%';
                    if (width >= 100) clearInterval(interval);
                }, 100);

                // Actual reload
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            });

            // --- Auto-Refresh Logic ---
            // Refresh the page every 30 seconds to ensure analytics are up-to-date
            setInterval(() => {
                window.location.reload();
            }, 30000);
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/analytics/index.blade.php ENDPATH**/ ?>