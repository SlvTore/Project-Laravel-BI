<?php

namespace App\Services;

use App\Models\BusinessMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MetricFormattingService
{
    /**
     * Format data OLAP untuk BusinessMetric dengan konsistensi tinggi
     */
    public static function enrichMetricWithOlapData(BusinessMetric $metric): BusinessMetric
    {
        $config = self::mapMetricToOlap($metric->metric_name);
        if (!$config) {
            return $metric;
        }

        try {
            if ($config['type'] === 'top_products') {
                [$current, $previous] = self::getTopProductMonthlyTotals($metric->business_id);
            } elseif ($config['type'] === 'margin') {
                [$current, $previous] = self::getMonthlyAggregate($config['view'], $config['column'], $metric->business_id, 'avg');
            } else {
                [$current, $previous] = self::getMonthlyAggregate($config['view'], $config['column'], $metric->business_id, 'sum');
            }

            // Override values with OLAP data - ensure we have actual numbers
            $metric->current_value = (float)($current ?? 0);
            $metric->previous_value = (float)($previous ?? 0);

            // Force Laravel to recognize these as changed attributes
            $metric->syncOriginal();

        } catch (\Throwable $e) {
            Log::error("OLAP enrichment failed for metric {$metric->metric_name}: " . $e->getMessage(), [
                'business_id' => $metric->business_id,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $metric;
    }

    /**
     * Mapping metric name ke konfigurasi OLAP
     */
    public static function mapMetricToOlap(string $name): ?array
    {
        return match($name) {
            'Total Penjualan' => ['view' => 'vw_sales_daily', 'column' => 'total_gross_revenue', 'type' => 'sum'],
            'Biaya Pokok Penjualan (COGS)' => ['view' => 'vw_cogs_daily', 'column' => 'total_cogs', 'type' => 'sum'],
            'Margin Keuntungan (Profit Margin)' => ['view' => 'vw_margin_daily', 'column' => 'total_margin', 'type' => 'margin'],
            'Penjualan Produk Terlaris' => ['view' => 'vw_sales_product_daily', 'column' => 'total_quantity', 'type' => 'top_products'],
            'Jumlah Pelanggan Baru' => ['view' => 'vw_new_customers_daily', 'column' => 'new_customers', 'type' => 'sum'],
            'Jumlah Pelanggan Setia' => ['view' => 'vw_returning_customers_daily', 'column' => 'returning_customers', 'type' => 'sum'],
            default => null,
        };
    }

    /**
     * Get aggregated data from OLAP views (consistent dengan MetricsController)
     */
    public static function getMonthlyAggregate(string $view, string $column, int $businessId, string $agg = 'sum'): array
    {
        // Get latest available data instead of current month
        $latestDate = DB::table($view)
            ->where('business_id', $businessId)
            ->max('sales_date');

        if (!$latestDate) {
            return [0.0, 0.0];
        }

        $latest = Carbon::parse($latestDate);

        // Current period: last 30 days from latest date
        $currentStart = $latest->copy()->subDays(29);

        $currentQuery = DB::table($view)
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$currentStart->format('Y-m-d'), $latest->format('Y-m-d')]);

        $current = $agg === 'avg' ? $currentQuery->avg($column) : $currentQuery->sum($column);

        // Previous period: 30 days before current period
        $previousStart = $currentStart->copy()->subDays(30);
        $previousEnd = $currentStart->copy()->subDay();

        $previousQuery = DB::table($view)
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')]);

        $previous = $agg === 'avg' ? $previousQuery->avg($column) : $previousQuery->sum($column);

        return [(float)($current ?? 0), (float)($previous ?? 0)];
    }

    /**
     * Get top products monthly totals (consistent dengan MetricsController)
     */
    public static function getTopProductMonthlyTotals(int $businessId): array
    {
        $latestDate = DB::table('vw_sales_product_daily')
            ->where('business_id', $businessId)
            ->max('sales_date');

        if (!$latestDate) {
            return [0.0, 0.0];
        }

        $latest = Carbon::parse($latestDate);
        $currentStart = $latest->copy()->subDays(29);
        $previousStart = $currentStart->copy()->subDays(30);
        $previousEnd = $currentStart->copy()->subDay();

        // Get top product for current period
        $topProductCurrent = DB::table('vw_sales_product_daily')
            ->select('product_name', DB::raw('SUM(total_quantity) as total_qty'))
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$currentStart->format('Y-m-d'), $latest->format('Y-m-d')])
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->first();

        // Get same product's data for previous period
        $productName = $topProductCurrent->product_name ?? null;
        $current = $topProductCurrent->total_qty ?? 0;

        $previous = 0;
        if ($productName) {
            $previousData = DB::table('vw_sales_product_daily')
                ->where('business_id', $businessId)
                ->where('product_name', $productName)
                ->whereBetween('sales_date', [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')])
                ->sum('total_quantity');
            $previous = $previousData ?? 0;
        }

        return [(float)$current, (float)$previous];
    }

    /**
     * Format currency untuk tampilan yang konsisten
     */
    public static function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Format percentage untuk tampilan yang konsisten
     */
    public static function formatPercentage(float $percentage, int $decimals = 2): string
    {
        return number_format($percentage, $decimals, ',', '.') . '%';
    }

    /**
     * Format number untuk tampilan yang konsisten
     */
    public static function formatNumber(float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, ',', '.');
    }

    /**
     * Format change percentage dengan tanda + atau -
     */
    public static function formatChange(float $change, int $decimals = 2): string
    {
        $sign = $change >= 0 ? '+' : '';
        return $sign . number_format($change, $decimals, ',', '.') . '%';
    }
}
