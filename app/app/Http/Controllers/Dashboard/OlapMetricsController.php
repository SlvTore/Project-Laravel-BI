<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\OlapMetricAggregator;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OlapMetricsController extends Controller
{
    public function __construct(private readonly OlapMetricAggregator $aggregator)
    {
    }

    public function dailySales(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $days = max((int) $request->input('days', 30), 1);
        $end = Carbon::today();
        $start = (clone $end)->subDays($days - 1);

        $trend = $this->aggregator->trend($business->id, $start, $end, 'day');

        return response()->json([
            'success' => true,
            'labels' => $trend['labels'],
            'values' => $trend['series']['gross_revenue'] ?? [],
            'transactions' => [],
        ]);
    }

    public function kpi(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        [$start, $end] = $this->resolveDateRange($request);

        $summary = $this->aggregator->summary($business->id, $start, $end);

        return response()->json([
            'success' => true,
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'data' => $summary,
        ]);
    }

    public function topProducts(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        [$start, $end] = $this->resolveDateRange($request);
        $limit = max((int) $request->query('limit', 5), 1);

        $products = $this->aggregator->topProductsForRange($business->id, $start, $end, $limit);

        return response()->json([
            'success' => true,
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'data' => $products,
        ]);
    }

    public function trend(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        [$start, $end] = $this->resolveDateRange($request);
        $group = $this->resolveGroupBy($request);
        $productId = $request->query('product_id');

        $trend = $this->aggregator->trend(
            $business->id,
            $start,
            $end,
            $group,
            $productId ? (int) $productId : null
        );

        return response()->json([
            'success' => true,
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'interval' => $trend['interval'],
            'labels' => $trend['labels'],
            'series' => $trend['series'],
        ]);
    }

    protected function resolveDateRange(Request $request): array
    {
        $range = $request->query('range', 'last_30_days');
        $defaultEnd = Carbon::today();
        $endInput = $request->query('end_date');
        $end = $endInput ? Carbon::parse($endInput) : $defaultEnd;

        return match ($range) {
            'last_7_days' => [(clone $end)->subDays(6)->startOfDay(), (clone $end)->endOfDay()],
            'this_quarter' => [Carbon::now()->startOfQuarter(), (clone $end)->endOfDay()],
            'custom' => [
                Carbon::parse($request->query('start_date', $end->copy()->subDays(29)->toDateString()))->startOfDay(),
                Carbon::parse($request->query('end_date', $end->toDateString()))->endOfDay(),
            ],
            default => [(clone $end)->subDays(29)->startOfDay(), (clone $end)->endOfDay()],
        };
    }

    protected function resolveGroupBy(Request $request): string
    {
        $group = $request->query('group_by', 'day');
        return in_array($group, ['day', 'week', 'month'], true) ? $group : 'day';
    }
}
