<?php

namespace App\Services\Olap;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Repositories\FactSalesRepository;

/**
 * Handles query operations against OLAP warehouse
 * Includes caching for performance optimization
 */
class OlapQueryService
{
    public function __construct(
        private FactSalesRepository $factRepo
    ) {}

    /**
     * Get daily metrics for a time period
     *
     * @param int $businessId
     * @param string $startDate
     * @param string $endDate
     * @param string $metricType (revenue|cogs|margin|quantity)
     * @param bool $useCache
     * @return array
     */
    public function getDailyMetrics(int $businessId, string $startDate, string $endDate, string $metricType = 'revenue', bool $useCache = true): array
    {
        $cacheKey = "olap:daily:{$businessId}:{$metricType}:{$startDate}:{$endDate}";
        $ttl = 3600; // 1 hour cache

        if (!$useCache) {
            return $this->fetchDailyMetrics($businessId, $startDate, $endDate, $metricType);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $startDate, $endDate, $metricType) {
            return $this->fetchDailyMetrics($businessId, $startDate, $endDate, $metricType);
        });
    }

    /**
     * Fetch daily metrics from database
     */
    private function fetchDailyMetrics(int $businessId, string $startDate, string $endDate, string $metricType): array
    {
        $viewMap = [
            'revenue' => 'vw_sales_daily',
            'cogs' => 'vw_cogs_daily',
            'margin' => 'vw_margin_daily',
        ];

        $view = $viewMap[$metricType] ?? 'vw_sales_daily';

        $data = DB::table($view)
            ->where('business_id', $businessId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return [
            'dates' => $data->pluck('date')->map(fn($d) => $d->format('Y-m-d'))->toArray(),
            'values' => $data->pluck('value')->toArray(),
            'metric_type' => $metricType,
        ];
    }

    /**
     * Get customer lifetime value metrics
     *
     * @param int $businessId
     * @param int $limit
     * @param bool $useCache
     * @return array
     */
    public function getCustomerLTV(int $businessId, int $limit = 50, bool $useCache = true): array
    {
        $cacheKey = "olap:ltv:{$businessId}:{$limit}";
        $ttl = 7200; // 2 hours cache

        if (!$useCache) {
            return $this->fetchCustomerLTV($businessId, $limit);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $limit) {
            return $this->fetchCustomerLTV($businessId, $limit);
        });
    }

    /**
     * Fetch customer LTV from database
     */
    private function fetchCustomerLTV(int $businessId, int $limit): array
    {
        $data = DB::table('vw_customer_ltv')
            ->where('business_id', $businessId)
            ->orderByDesc('lifetime_value')
            ->limit($limit)
            ->get();

        return $data->map(function ($row) {
            return [
                'customer_name' => $row->customer_name,
                'lifetime_value' => (float)$row->lifetime_value,
                'total_orders' => (int)$row->total_orders,
                'avg_order_value' => (float)$row->avg_order_value,
                'first_purchase_date' => $row->first_purchase_date,
                'last_purchase_date' => $row->last_purchase_date,
            ];
        })->toArray();
    }

    /**
     * Get top products by revenue
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $limit
     * @param bool $useCache
     * @return array
     */
    public function getTopProducts(int $businessId, ?string $startDate = null, ?string $endDate = null, int $limit = 20, bool $useCache = true): array
    {
        $cacheKey = "olap:top_products:{$businessId}:" . ($startDate ?? 'all') . ":" . ($endDate ?? 'all') . ":{$limit}";
        $ttl = 3600;

        if (!$useCache) {
            return $this->fetchTopProducts($businessId, $startDate, $endDate, $limit);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $startDate, $endDate, $limit) {
            return $this->fetchTopProducts($businessId, $startDate, $endDate, $limit);
        });
    }

    /**
     * Fetch top products from database
     */
    private function fetchTopProducts(int $businessId, ?string $startDate, ?string $endDate, int $limit): array
    {
        $products = $this->factRepo->getSalesByProduct($businessId, $startDate, $endDate, $limit);

        return $products->map(function ($p) {
            return [
                'product_name' => $p->product_name,
                'category' => $p->category,
                'total_quantity' => (float)$p->total_quantity,
                'total_revenue' => (float)$p->total_revenue,
                'total_margin' => (float)$p->total_margin,
                'avg_margin_percent' => (float)$p->avg_margin_percent,
            ];
        })->toArray();
    }

    /**
     * Get channel performance
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param bool $useCache
     * @return array
     */
    public function getChannelPerformance(int $businessId, ?string $startDate = null, ?string $endDate = null, bool $useCache = true): array
    {
        $cacheKey = "olap:channels:{$businessId}:" . ($startDate ?? 'all') . ":" . ($endDate ?? 'all');
        $ttl = 3600;

        if (!$useCache) {
            return $this->fetchChannelPerformance($businessId, $startDate, $endDate);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $startDate, $endDate) {
            return $this->fetchChannelPerformance($businessId, $startDate, $endDate);
        });
    }

    /**
     * Fetch channel performance from database
     */
    private function fetchChannelPerformance(int $businessId, ?string $startDate, ?string $endDate): array
    {
        $channels = $this->factRepo->getSalesByChannel($businessId, $startDate, $endDate);

        return $channels->map(function ($c) {
            return [
                'channel_name' => $c->channel_name,
                'total_quantity' => (float)$c->total_quantity,
                'total_revenue' => (float)$c->total_revenue,
                'total_margin' => (float)$c->total_margin,
            ];
        })->toArray();
    }

    /**
     * Get monthly trends
     *
     * @param int $businessId
     * @param int $months Number of months to retrieve
     * @param bool $useCache
     * @return array
     */
    public function getMonthlyTrends(int $businessId, int $months = 12, bool $useCache = true): array
    {
        $cacheKey = "olap:monthly:{$businessId}:{$months}";
        $ttl = 7200;

        if (!$useCache) {
            return $this->fetchMonthlyTrends($businessId, $months);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $months) {
            return $this->fetchMonthlyTrends($businessId, $months);
        });
    }

    /**
     * Fetch monthly trends from database
     */
    private function fetchMonthlyTrends(int $businessId, int $months): array
    {
        $data = $this->factRepo->getMonthlySales($businessId, $months);

        return $data->map(function ($m) {
            return [
                'year' => (int)$m->year,
                'month' => (int)$m->month,
                'fiscal_period' => $m->fiscal_period,
                'total_quantity' => (float)$m->total_quantity,
                'total_revenue' => (float)$m->total_revenue,
                'total_cogs' => (float)$m->total_cogs,
                'total_margin' => (float)$m->total_margin,
                'avg_margin_percent' => (float)$m->avg_margin_percent,
            ];
        })->toArray();
    }

    /**
     * Get quarterly trends
     *
     * @param int $businessId
     * @param int $quarters Number of quarters to retrieve
     * @param bool $useCache
     * @return array
     */
    public function getQuarterlyTrends(int $businessId, int $quarters = 4, bool $useCache = true): array
    {
        $cacheKey = "olap:quarterly:{$businessId}:{$quarters}";
        $ttl = 7200;

        if (!$useCache) {
            return $this->fetchQuarterlyTrends($businessId, $quarters);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $quarters) {
            return $this->fetchQuarterlyTrends($businessId, $quarters);
        });
    }

    /**
     * Fetch quarterly trends from database
     */
    private function fetchQuarterlyTrends(int $businessId, int $quarters): array
    {
        $data = $this->factRepo->getQuarterlySales($businessId, $quarters);

        return $data->map(function ($q) {
            return [
                'year' => (int)$q->year,
                'quarter' => (int)$q->quarter,
                'total_quantity' => (float)$q->total_quantity,
                'total_revenue' => (float)$q->total_revenue,
                'total_cogs' => (float)$q->total_cogs,
                'total_margin' => (float)$q->total_margin,
                'avg_margin_percent' => (float)$q->avg_margin_percent,
            ];
        })->toArray();
    }

    /**
     * Get summary metrics for business
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param bool $useCache
     * @return array
     */
    public function getSummaryMetrics(int $businessId, ?string $startDate = null, ?string $endDate = null, bool $useCache = true): array
    {
        $cacheKey = "olap:summary:{$businessId}:" . ($startDate ?? 'all') . ":" . ($endDate ?? 'all');
        $ttl = 1800; // 30 minutes cache

        if (!$useCache) {
            return $this->factRepo->getSummaryMetrics($businessId, $startDate, $endDate);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $startDate, $endDate) {
            return $this->factRepo->getSummaryMetrics($businessId, $startDate, $endDate);
        });
    }

    /**
     * Get new vs returning customers
     *
     * @param int $businessId
     * @param string $startDate
     * @param string $endDate
     * @param bool $useCache
     * @return array
     */
    public function getCustomerSegmentation(int $businessId, string $startDate, string $endDate, bool $useCache = true): array
    {
        $cacheKey = "olap:customer_seg:{$businessId}:{$startDate}:{$endDate}";
        $ttl = 3600;

        if (!$useCache) {
            return $this->fetchCustomerSegmentation($businessId, $startDate, $endDate);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($businessId, $startDate, $endDate) {
            return $this->fetchCustomerSegmentation($businessId, $startDate, $endDate);
        });
    }

    /**
     * Fetch customer segmentation from database
     */
    private function fetchCustomerSegmentation(int $businessId, string $startDate, string $endDate): array
    {
        $newCustomers = DB::table('vw_new_customers_daily')
            ->where('business_id', $businessId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('value');

        $returningCustomers = DB::table('vw_returning_customers_daily')
            ->where('business_id', $businessId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('value');

        return [
            'new_customers' => (int)$newCustomers,
            'returning_customers' => (int)$returningCustomers,
            'total_customers' => (int)($newCustomers + $returningCustomers),
            'returning_rate' => $newCustomers > 0 
                ? round(($returningCustomers / ($newCustomers + $returningCustomers)) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Invalidate cache for a business
     *
     * @param int $businessId
     * @return void
     */
    public function invalidateCache(int $businessId): void
    {
        // Clear all cached queries for this business
        Cache::tags(["business:{$businessId}", 'olap'])->flush();
    }

    /**
     * Invalidate specific metric cache
     *
     * @param int $businessId
     * @param string $metricType
     * @return void
     */
    public function invalidateMetricCache(int $businessId, string $metricType): void
    {
        $pattern = "olap:{$metricType}:{$businessId}:*";
        // Note: Redis pattern matching would be needed for production
        // For now, invalidate the entire business cache
        $this->invalidateCache($businessId);
    }
}
