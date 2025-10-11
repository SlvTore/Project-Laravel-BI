<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OlapMetricAggregator
{
    public function summary(int $businessId, Carbon $start, Carbon $end): array
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $totals = DB::table('fact_sales as f')
            ->join('dim_date as d', 'd.id', '=', 'f.date_id')
            ->where('f.business_id', $businessId)
            ->whereBetween('d.date', [$startDate, $endDate])
            ->selectRaw('SUM(f.gross_revenue) as gross_revenue')
            ->selectRaw('SUM(f.total_amount) as net_revenue')
            ->selectRaw('SUM(f.discount) as total_discount')
            ->selectRaw('SUM(f.cogs_amount) as cogs_amount')
            ->selectRaw('SUM(f.gross_margin_amount) as gross_margin_amount')
            ->selectRaw('SUM(f.quantity) as total_quantity')
            ->selectRaw('COUNT(DISTINCT COALESCE(f.sales_transaction_id, f.id)) as order_count')
            ->first();

        if (!$totals || $totals->gross_revenue === null) {
            return [
                'gross_revenue' => 0.0,
                'net_revenue' => 0.0,
                'cogs_amount' => 0.0,
                'gross_margin_amount' => 0.0,
                'gross_margin_percent' => 0.0,
                'order_count' => 0,
                'average_order_value' => 0.0,
                'total_discount' => 0.0,
                'total_quantity' => 0.0,
            ];
        }

        $grossRevenue = (float) $totals->gross_revenue;
        $marginAmount = (float) $totals->gross_margin_amount;
        $orderCount = (int) $totals->order_count;
        $netRevenue = (float) $totals->net_revenue;

        return [
            'gross_revenue' => $grossRevenue,
            'net_revenue' => $netRevenue,
            'cogs_amount' => (float) $totals->cogs_amount,
            'gross_margin_amount' => $marginAmount,
            'gross_margin_percent' => $grossRevenue <= 0 ? 0 : ($marginAmount / $grossRevenue) * 100,
            'order_count' => $orderCount,
            'average_order_value' => $orderCount > 0 ? $netRevenue / $orderCount : 0.0,
            'total_discount' => (float) $totals->total_discount,
            'total_quantity' => (float) $totals->total_quantity,
        ];
    }

    public function topProductsForRange(int $businessId, Carbon $start, Carbon $end, int $limit = 5)
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        return DB::table('vw_sales_product_daily')
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->groupBy('product_name')
            ->selectRaw('product_name')
            ->selectRaw('SUM(total_revenue) as total_revenue')
            ->selectRaw('SUM(total_cogs) as total_cogs')
            ->selectRaw('SUM(total_margin) as total_margin')
            ->selectRaw('SUM(total_quantity) as total_quantity')
            ->orderByDesc(DB::raw('SUM(total_revenue)'))
            ->limit($limit)
            ->get();
    }

    public function trend(int $businessId, Carbon $start, Carbon $end, string $interval = 'day', ?int $productId = null): array
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $interval = in_array($interval, ['day', 'week', 'month'], true) ? $interval : 'day';

        $bucketExpression = match ($interval) {
            'week' => "CONCAT(YEAR(d.date), '-W', LPAD(WEEK(d.date, 3), 2, '0'))",
            'month' => "DATE_FORMAT(d.date, '%Y-%m')",
            default => "DATE_FORMAT(d.date, '%Y-%m-%d')",
        };

        $rowsQuery = DB::table('fact_sales as f')
            ->join('dim_date as d', 'd.id', '=', 'f.date_id')
            ->where('f.business_id', $businessId)
            ->whereBetween('d.date', [$startDate, $endDate])
            ->selectRaw("{$bucketExpression} as bucket")
            ->selectRaw('MIN(d.date) as bucket_start')
            ->selectRaw('MAX(d.date) as bucket_end')
            ->selectRaw('SUM(f.gross_revenue) as gross_revenue')
            ->selectRaw('SUM(f.cogs_amount) as cogs_amount')
            ->selectRaw('SUM(f.gross_margin_amount) as gross_margin_amount')
            ->selectRaw('SUM(f.total_amount) as net_revenue')
            ->groupBy(DB::raw($bucketExpression))
            ->orderBy('bucket_start');

        if ($productId) {
            $rowsQuery->where('f.product_id', $productId);
        }

        $rows = $rowsQuery->get();

        $labels = [];
        $grossSeries = [];
        $cogsSeries = [];
        $marginSeries = [];
        $netSeries = [];

        foreach ($rows as $row) {
            $labels[] = $this->formatBucketLabel($row->bucket_start, $row->bucket_end, $interval, $row->bucket);
            $grossSeries[] = (float) $row->gross_revenue;
            $cogsSeries[] = (float) $row->cogs_amount;
            $marginSeries[] = (float) $row->gross_margin_amount;
            $netSeries[] = (float) $row->net_revenue;
        }

        return [
            'labels' => $labels,
            'series' => [
                'gross_revenue' => $grossSeries,
                'cogs_amount' => $cogsSeries,
                'gross_margin_amount' => $marginSeries,
                'net_revenue' => $netSeries,
            ],
            'interval' => $interval,
            'rows' => $rows,
        ];
    }

    protected function formatBucketLabel($start, $end, string $interval, string $bucket): string
    {
        try {
            return match ($interval) {
                'month' => Carbon::parse($start)->format('M Y'),
                'week' => sprintf('%s (%s - %s)', $bucket, Carbon::parse($start)->format('d M'), Carbon::parse($end)->format('d M')),
                default => Carbon::parse($start)->format('d M'),
            };
        } catch (\Exception $e) {
            return $bucket;
        }
    }

    public function dailyRevenue(int $businessId, int $days = 30): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();
        // NOTE: View alias uses total_revenue not total_gross_revenue. Fixing mismatch.
        $rows = DB::table('vw_sales_daily')
            ->where('business_id', $businessId)
            ->where('sales_date', '>=', $from)
            ->orderBy('sales_date')
            ->get(['sales_date','total_revenue']);

        return [
            'labels' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('total_revenue')->map(fn($v) => (float)$v),
            'dates' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
        ];
    }

    public function topProducts(int $businessId, int $days = 30, int $limit = 10)
    {
        $from = Carbon::now()->subDays($days)->toDateString();
        return DB::table('vw_sales_product_daily')
            ->where('business_id', $businessId)
            ->where('sales_date', '>=', $from)
            ->groupBy('product_name')
            ->select('product_name', DB::raw('SUM(total_revenue) as total_revenue'), DB::raw('SUM(total_quantity) as total_qty'))
            ->orderByDesc(DB::raw('SUM(total_revenue)'))
            ->limit($limit)
            ->get();
    }

    public function dailyCogs(int $businessId, int $days = 30): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();
        $rows = DB::table('vw_cogs_daily')
            ->where('business_id', $businessId)
            ->where('sales_date', '>=', $from)
            ->orderBy('sales_date')
            ->get(['sales_date','total_cogs']);

        return [
            'labels' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('total_cogs')->map(fn($v) => (float)$v),
            'dates' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
        ];
    }

    public function dailyMargin(int $businessId, int $days = 30): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();

        // Join margin and sales data to calculate percentage: ((Revenue - COGS) / Revenue) × 100%
        $rows = DB::table('vw_margin_daily as m')
            ->join('vw_sales_daily as s', function($join) {
                $join->on('m.business_id', '=', 's.business_id')
                     ->on('m.sales_date', '=', 's.sales_date');
            })
            ->where('m.business_id', $businessId)
            ->where('m.sales_date', '>=', $from)
            ->orderBy('m.sales_date')
            ->selectRaw('
                m.sales_date,
                m.total_margin,
                s.total_gross_revenue,
                CASE
                    WHEN s.total_gross_revenue > 0
                    THEN (m.total_margin / s.total_gross_revenue) * 100
                    ELSE 0
                END as margin_percentage
            ')
            ->get();

        return [
            'labels' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('margin_percentage')->map(fn($v) => round((float)$v, 2)),
            'dates' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
        ];
    }

    public function dailyNewCustomers(int $businessId, int $days = 30): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();
        $rows = DB::table('vw_new_customers_daily')
            ->where('business_id', $businessId)
            ->where('sales_date', '>=', $from)
            ->orderBy('sales_date')
            ->get(['sales_date','new_customers']);

        return [
            'labels' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('new_customers')->map(fn($v) => (int)$v),
            'dates' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
        ];
    }

    public function dailyReturningCustomers(int $businessId, int $days = 30): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();
        $rows = DB::table('vw_returning_customers_daily')
            ->where('business_id', $businessId)
            ->where('sales_date', '>=', $from)
            ->orderBy('sales_date')
            ->get(['sales_date','returning_customers']);

        return [
            'labels' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('returning_customers')->map(fn($v) => (int)$v),
            'dates' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
        ];
    }
}
