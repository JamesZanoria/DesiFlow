<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSnapshot;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;


class AnalyticsController extends Controller{
    // Display the analytics report, each section pulled from the most recent
    // batch the analytics microservice generated.
    public function index(){
        $data = $this->getAnalyticsData();

        return view('admin.analytics.index', $data);
    }

    public function getData(){
        return response()->json($this->getAnalyticsData());
    }

    private function getAnalyticsData(){
        $businessHealth = AnalyticsSnapshot::latestOfType('business_health')->first();
        $businessHealthHistory = $this->businessHealthHistory();

        $menuEngineering = $this->latestBatchWithNames('menu_engineering', MenuItem::class, 'menu_item_id');
        $demandForecast = $this->latestBatchWithNames('demand_forecast', Ingredient::class, 'ingredient_id');
        $spoilageRisk = $this->latestBatchWithNames('spoilage_risk', Ingredient::class, 'ingredient_id');

        // Real-time KPIs for immediate business overview
        $kpis = [
            'total_revenue' => Order::sum('total_amount'),
            'total_orders' => Order::count(),
            'active_menu_items' => MenuItem::count(),
            'high_risk_ingredients' => AnalyticsSnapshot::where('snapshot_type', 'spoilage_risk')
                ->whereJsonContains('result_data->risk_level', 'critical')
                ->orWhereJsonContains('result_data->risk_level', 'high')
                ->count(),
        ];

        return [
            'businessHealth' => $businessHealth,
            'businessHealthHistory' => $this->businessHealthHistory(),
            'menuEngineering' => $menuEngineering,
            'demandForecast' => $demandForecast,
            'spoilageRisk' => $spoilageRisk,
            'kpis' => $kpis,
        ];
    }

    // Last 30 days of business_health snapshots, oldest first, for the trend

    // line chart. Unlike the other three snapshot types (which the
    // microservice overwrites in place),
    // 'business_health' is expected to accumulate one row per day, so this
    // reads a real date range instead of just the latest row.
    private function businessHealthHistory(){
        return AnalyticsSnapshot::where('snapshot_type', 'business_health')
            ->where('generated_at', '>=', now()->subDays(30))
            ->orderBy('generated_at')
            ->get()
            ->map(fn ($snapshot) => [
                'date' => $snapshot->generated_at,
                'score' => $snapshot->result_data['score'] ?? null,
            ])
            ->filter(fn ($point) => $point['score'] !== null)
            ->values();
    }

    // Fetch the most recent batch of snapshots for a given type, and attach it
    // to the actual named record each one refers to.
    // Deliberately starts from every CURRENT record in $modelClass (not from
    // whatever snapshots happen to exist) and left-joins the snapshot data
    // onto it in memory. A menu item added five minutes ago, or one the
    // microservice hasn't scored yet for lack of order history, still needs
    // to appear in the report, just flagged as "not yet available" rather than
    // silently missing (alternate flow: "the system labels that
    // item's forecast 'Insufficient Data' rather than displaying a misleading
    // figure"). The old snapshot-first approach would have dropped it
    // entirely instead.
    private function latestBatchWithNames(string $type, string $modelClass, string $keyColumn){
        $latestTimestamp = AnalyticsSnapshot::where('snapshot_type', $type)->max('generated_at');

        $snapshotsByRecordId = collect();

        if ($latestTimestamp) {
            $snapshotsByRecordId = AnalyticsSnapshot::where('snapshot_type', $type)
                ->where('generated_at', $latestTimestamp)
                ->get()
                ->keyBy('reference_id');
        }

        // Determine the correct name column based on the model class
        // We use a mapping to avoid hardcoding strings repeatedly
        $nameColumns = [
            \App\Models\Ingredient::class => 'ingredient_name',
            \App\Models\MenuItem::class => 'item_name',
        ];

        $nameColumn = $nameColumns[$modelClass] ?? 'name';

        return $modelClass::query()
            ->select($keyColumn, $nameColumn) // Optimize: Only select the ID and Name for the mapping
            ->get()
            ->map(function ($record) use ($snapshotsByRecordId, $keyColumn) {
                $snapshot = $snapshotsByRecordId->get($record->{$keyColumn});

                return [
                    'record' => $record,
                    'data' => $snapshot?->result_data,
                    'generated_at' => $snapshot?->generated_at,
                ];
            });
    }
}