<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalesReportController extends Controller
{
    // payments.transaction_status values that count as real revenue.
    // Voided or refunded payments must NOT be listed here.
    private const COUNTED_PAYMENT_STATUSES = ['completed'];

    private const PERIODS = ['daily', 'weekly', 'monthly'];

    // Earliest hour shown on the Daily chart (it starts earlier if a sale happened earlier).
    private const DAILY_CHART_START_HOUR = 8;

    public function index(Request $request)
    {
        $period = $this->resolvePeriod($request->query('period'));

        return view('admin.sales-report.index', [
            'period' => $period,
            'report' => $this->buildReport($period),
        ]);
    }

    // JSON endpoint the page polls to stay up to date.
    public function data(Request $request)
    {
        return response()->json(
            $this->buildReport($this->resolvePeriod($request->query('period')))
        );
    }

    private function resolvePeriod(?string $period): string
    {
        return in_array($period, self::PERIODS, true) ? $period : 'daily';
    }

    private function buildReport(string $period): array
    {
        $now = now();

        $days = match ($period) {
            'weekly' => 7,
            'monthly' => 30,
            default => 1,
        };

        $start = $now->copy()->subDays($days - 1)->startOfDay();
        $end = $now->copy()->endOfDay();

        // Only orders with a completed payment count as sales.
        $orders = Order::query()
            ->whereHas('payment', fn ($query) => $query->whereIn('transaction_status', self::COUNTED_PAYMENT_STATUSES))
            ->whereBetween('created_at', [$start, $end])
            ->get(['order_id', 'created_at', 'total_amount']);

        $buckets = $period === 'daily'
            ? $this->hourlyBuckets($orders, $now)
            : $this->dailyBuckets($start, $days);

        foreach ($orders as $order) {
            $key = $period === 'daily'
                ? $order->created_at->format('H')
                : $order->created_at->format('Y-m-d');

            if (isset($buckets[$key])) {
                $buckets[$key]['total'] += (float) $order->total_amount;
            }
        }

        $buckets = array_values($buckets);
        $totalSales = $orders->sum(fn ($order) => (float) $order->total_amount);

        return [
            'period' => $period,
            'periodLabel' => $this->periodLabel($period, $start, $now),
            'totalSales' => number_format($totalSales, 2),
            'totalOrders' => $orders->count(),
            'hasSales' => $orders->isNotEmpty(),
            'chart' => [
                'labels' => array_column($buckets, 'label'),
                'values' => array_map(fn ($bucket) => round($bucket['total'], 2), $buckets),
            ],
            'updatedAt' => $now->format('g:i A'),
        ];
    }

    // One bucket per hour, from the opening hour up to the current hour.
    private function hourlyBuckets(Collection $orders, Carbon $now): array
    {
        $currentHour = (int) $now->format('G');
        $firstSaleHour = $orders->map(fn ($order) => (int) $order->created_at->format('G'))->min();
        $startHour = min(self::DAILY_CHART_START_HOUR, $currentHour, $firstSaleHour ?? self::DAILY_CHART_START_HOUR);

        $buckets = [];
        for ($hour = $startHour; $hour <= $currentHour; $hour++) {
            $buckets[sprintf('%02d', $hour)] = [
                'label' => $now->copy()->setTime($hour, 0)->format('g A'),
                'total' => 0.0,
            ];
        }

        return $buckets;
    }

    // One bucket per day across the selected range.
    private function dailyBuckets(Carbon $start, int $days): array
    {
        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $buckets[$day->format('Y-m-d')] = [
                'label' => $day->format('M j'),
                'total' => 0.0,
            ];
        }

        return $buckets;
    }

    private function periodLabel(string $period, Carbon $start, Carbon $now): string
    {
        return match ($period) {
            'weekly' => 'Last 7 days · ' . $start->format('M j') . ' – ' . $now->format('M j'),
            'monthly' => 'Last 30 days · ' . $start->format('M j') . ' – ' . $now->format('M j'),
            default => 'Today · ' . $now->format('M j, Y'),
        };
    }
}