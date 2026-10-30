<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSnapshot;
use App\Models\Ingredient;
use App\Models\Order;

class DashboardController extends Controller
{
  
    private const ACTIVE_ORDER_STATUSES = ['pending', 'preparing'];

  
    private const COUNTED_PAYMENT_STATUSES = ['completed'];

    private const LOW_STOCK_LIMIT = 8;

    public function index()
    {
        return view('admin.dashboard.index', [
            'dashboard' => $this->getDashboardData(),
        ]);
    }

    public function data()
    {
        return response()->json($this->getDashboardData());
    }

    private function getDashboardData(): array
    {
        return [
            'health' => $this->businessHealth(),
            'sales' => $this->todaysSales(),
            'activeOrders' => $this->activeOrders(),
            'lowStock' => $this->lowStockAlerts(),
            'updatedAt' => now()->format('g:i A'),
        ];
    }

    private function businessHealth(): array
    {
        $snapshots = AnalyticsSnapshot::where('snapshot_type', 'business_health')
            ->orderByDesc('generated_at')
            ->limit(2)
            ->get();

        $latest = $snapshots->first();
        $score = $latest?->result_data['score'] ?? null;

        if ($score === null) {
            return ['available' => false, 'score' => null, 'band' => 'none', 'summary' => null];
        }

        $previous = $snapshots->get(1)?->result_data['score'] ?? null;
        $change = $previous !== null ? round($score - $previous, 1) : null;

        $summary = null;
        foreach (['summary', 'explanation', 'message', 'narrative'] as $key) {
            $value = $latest->result_data[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $summary = $value;
                break;
            }
        }

        $summary ??= match (true) {
            $change === null => 'First score recorded. The day-over-day change will show once there is a previous score to compare against.',
            $change > 0 => 'Up ' . $change . ' points from the previous score.',
            $change < 0 => 'Down ' . abs($change) . ' points from the previous score.',
            default => 'No change from the previous score.',
        };

        return [
            'available' => true,
            'score' => (int) round($score),
            'band' => $score >= 80 ? 'good' : ($score >= 60 ? 'fair' : 'poor'),
            'summary' => $summary,
        ];
    }

    private function todaysSales(): array
    {
        $orders = Order::whereHas('payment', fn ($query) => $query->whereIn('transaction_status', self::COUNTED_PAYMENT_STATUSES))
            ->whereDate('created_at', today());

        return [
            'total' => number_format((float) $orders->sum('total_amount'), 2),
            'count' => $orders->count(),
        ];
    }

    private function activeOrders(): array
    {
        return [
            'count' => Order::whereIn('status', self::ACTIVE_ORDER_STATUSES)->count(),
        ];
    }

    private function lowStockAlerts(): array
    {
        $low = Ingredient::withCurrentStock()->get()
            ->filter(fn ($ingredient) => $ingredient->isLowStock())
            ->sortBy(function ($ingredient) {
                $threshold = (float) $ingredient->restock_threshold;
                return $threshold > 0 ? ((float) ($ingredient->current_stock ?? 0)) / $threshold : 0;
            })
            ->values();

        return [
            'count' => $low->count(),
            'items' => $low->take(self::LOW_STOCK_LIMIT)->map(fn ($ingredient) => [
                'name' => $ingredient->ingredient_name,
                'current' => number_format((float) ($ingredient->current_stock ?? 0), 2),
                'threshold' => number_format((float) $ingredient->restock_threshold, 2),
                'unit' => $ingredient->unit_of_measurement,
                'out' => (float) ($ingredient->current_stock ?? 0) <= 0,
            ])->all(),
        ];
    }
}