<?php

namespace App\Services;

use App\Models\BusinessMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataWarehouseSyncService
{
    /**
     * Re-enrich all active metrics for a business after warehouse changes.
     * Returns array of refreshed metrics (id => [current, previous]).
     */
    public function refreshBusinessMetrics(int $businessId): array
    {
        $results = [];
        $metrics = BusinessMetric::where('business_id', $businessId)->where('is_active', true)->get();
        foreach ($metrics as $metric) {
            $before = [$metric->current_value, $metric->previous_value];
            $metric = \App\Services\MetricFormattingService::enrichMetricWithOlapData($metric);
            $after = [$metric->current_value, $metric->previous_value];
            $results[$metric->id] = [
                'name' => $metric->metric_name,
                'before' => $before,
                'after' => $after,
            ];
        }
        return $results;
    }

    /**
     * Basic consistency snapshot for dashboard (single source of truth) spanning last 30 days.
     */
    public function summaryWindow(int $businessId, int $days = 30): array
    {
        $from = now()->subDays($days)->toDateString();
        $to = now()->toDateString();
        $base = [
            'revenue' => 0.0,
            'cogs' => 0.0,
            'margin' => 0.0,
            'orders' => 0,
            'quantity' => 0.0,
        ];
        try {
            $row = DB::table('vw_sales_daily')
                ->where('business_id', $businessId)
                ->whereBetween('sales_date', [$from, $to])
                ->selectRaw('SUM(total_revenue) as revenue, SUM(total_quantity) as quantity, COUNT(*) as days')
                ->first();
            $cogs = DB::table('vw_cogs_daily')
                ->where('business_id', $businessId)
                ->whereBetween('sales_date', [$from, $to])
                ->sum('total_cogs');
            $margin = DB::table('vw_margin_daily')
                ->where('business_id', $businessId)
                ->whereBetween('sales_date', [$from, $to])
                ->sum('total_margin');
            $base['revenue'] = (float) ($row->revenue ?? 0);
            $base['quantity'] = (float) ($row->quantity ?? 0);
            $base['cogs'] = (float) $cogs;
            $base['margin'] = (float) $margin;
            $base['orders'] = DB::table('fact_sales as f')
                ->join('dim_date as d','d.id','=','f.date_id')
                ->where('f.business_id', $businessId)
                ->whereBetween('d.date', [$from, $to])
                ->distinct('f.sales_transaction_id')
                ->count('f.sales_transaction_id');
        } catch (\Throwable $e) {
            Log::warning('summaryWindow failed: '.$e->getMessage());
        }
        return $base;
    }
}
