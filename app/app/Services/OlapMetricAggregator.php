<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OlapMetricAggregator
{
    public function dailyRevenue(int $businessId, int $days = 30): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();
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
        $rows = DB::table('vw_margin_daily')
            ->where('business_id', $businessId)
            ->where('sales_date', '>=', $from)
            ->orderBy('sales_date')
            ->get(['sales_date','total_margin']);

        return [
            'labels' => $rows->pluck('sales_date')->map(fn($d) => Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('total_margin')->map(fn($v) => (float)$v),
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
