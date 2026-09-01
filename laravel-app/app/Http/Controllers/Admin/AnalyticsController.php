<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSnapshot;
use App\Models\Ingredient;
use App\Models\MenuItem;

class AnalyticsController extends Controller{
    // Display the analytics report, each section pulled from the most recent
    // batch the analytics microservice generated.
    public function index(){
        $businessHealth = AnalyticsSnapshot::latestOfType('business_health')->first();
        $businessHealthHistory = $this->businessHealthHistory();

        $menuEngineering = $this->latestBatchWithNames('menu_engineering', MenuItem::class, 'menu_item_id');
        $demandForecast = $this->latestBatchWithNames('demand_forecast', Ingredient::class, 'ingredient_id');
        $spoilageRisk = $this->latestBatchWithNames('spoilage_risk', Ingredient::class, 'ingredient_id');

        return view('admin.analytics.index', [
            'businessHealth' => $businessHealth,
            'businessHealthHistory' => $businessHealthHistory,
            'menuEngineering' => $menuEngineering,
            'demandForecast' => $demandForecast,
            'spoilageRisk' => $spoilageRisk,
        ]);
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

        return $modelClass::query()
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