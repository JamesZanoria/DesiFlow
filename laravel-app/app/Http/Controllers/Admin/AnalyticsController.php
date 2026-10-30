<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\AnalyticsSnapshot;
use App\\Models\\Ingredient;
use App\\Models\\MenuItem;
use App\\Models\\Order;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('admin.analytics.index', $this->getAnalyticsData());
    }

    public function getData()
    {
        return response()->json($this->getAnalyticsData());
    }

    private function getAnalyticsData(): array
    {
        $businessHealth = AnalyticsSnapshot::latestOfType('business_health')->first();

        return [
            'businessHealth' => $businessHealth,
            'businessHealthHistory' => $this->businessHealthHistory(),
            'menuEngineering' => $this->latestBatchWithNames('menu_engineering', MenuItem::class, 'menu_item_id'),
            'demandForecast' => $this->latestBatchWithNames('demand_forecast', Ingredient::class, 'ingredient_id'),
            'spoilageRisk' => $this->latestBatchWithNames('spoilage_risk', Ingredient::class, 'ingredient_id'),
            'kpis' => $this->operationalKpis(),
        ];
    }

    /**
     * Keep the analytics overview consistent with the sales report:
     * only completed payments count as revenue or completed orders.
     */
    private function operationalKpis(): array
    {
        $completedOrders = Order::query()
            ->whereHas('payment', fn ($query) => $query->where('transaction_status', 'completed'));

        $latestSpoilageBatch = AnalyticsSnapshot::query()
            ->where('snapshot_type', 'spoilage_risk')
            ->max('generated_at');

        $highRiskIngredients = 0;

        if ($latestSpoilageBatch !== null) {
            $highRiskIngredients = AnalyticsSnapshot::query()
                ->where('snapshot_type', 'spoilage_risk')
                ->where('generated_at', $latestSpoilageBatch)
                ->where(function ($query) {
                    $query->whereJsonContains('result_data->risk_level', 'critical')
                        ->orWhereJsonContains('result_data->risk_level', 'high');
                })
                ->count();
        }

        return [
            'total_revenue' => (float) (clone $completedOrders)->sum('total_amount'),
            'total_orders' => (clone $completedOrders)->count(),
            'active_menu_items' => MenuItem::query()->where('is_available', true)->count(),
            'high_risk_ingredients' => $highRiskIngredients,
        ];
    }

    private function businessHealthHistory()
    {
        return AnalyticsSnapshot::query()
            ->where('snapshot_type', 'business_health')
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

    /**
     * Attach the latest analytics batch to every current record. Records that
     * have not yet received a score remain present with null data so the UI
     * can display an explicit insufficient-data state.
     */
    private function latestBatchWithNames(string $type, string $modelClass, string $keyColumn)
    {
        $latestTimestamp = AnalyticsSnapshot::query()
            ->where('snapshot_type', $type)
            ->max('generated_at');

        $snapshotsByRecordId = collect();

        if ($latestTimestamp !== null) {
            $snapshotsByRecordId = AnalyticsSnapshot::query()
                ->where('snapshot_type', $type)
                ->where('generated_at', $latestTimestamp)
                ->get()
                ->keyBy('reference_id');
        }

        $nameColumns = [
            Ingredient::class => 'ingredient_name',
            MenuItem::class => 'item_name',
        ];

        $nameColumn = $nameColumns[$modelClass] ?? 'name';

        return $modelClass::query()
            ->select($keyColumn, $nameColumn)
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
