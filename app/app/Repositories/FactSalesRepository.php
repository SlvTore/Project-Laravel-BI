<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for fact_sales table queries
 * Encapsulates complex query logic for better maintainability
 */
class FactSalesRepository
{
    /**
     * Get sales by date range
     *
     * @param int $businessId
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getSalesByDateRange(int $businessId, string $startDate, string $endDate): Collection
    {
        return DB::table('fact_sales as f')
            ->join('dim_date as d', 'd.id', '=', 'f.date_id')
            ->where('f.business_id', $businessId)
            ->whereBetween('d.date', [$startDate, $endDate])
            ->select(
                'd.date',
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_revenue) as total_revenue'),
                DB::raw('SUM(f.cogs_amount) as total_cogs'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin')
            )
            ->groupBy('d.date')
            ->orderBy('d.date')
            ->get();
    }

    /**
     * Get sales by product
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|null $limit
     * @return Collection
     */
    public function getSalesByProduct(int $businessId, ?string $startDate = null, ?string $endDate = null, ?int $limit = null): Collection
    {
        $query = DB::table('fact_sales as f')
            ->join('dim_product as p', 'p.id', '=', 'f.product_id')
            ->where('f.business_id', $businessId);

        if ($startDate && $endDate) {
            $query->join('dim_date as d', 'd.id', '=', 'f.date_id')
                ->whereBetween('d.date', [$startDate, $endDate]);
        }

        $query->select(
            'p.name as product_name',
            'p.category',
            DB::raw('SUM(f.quantity) as total_quantity'),
            DB::raw('SUM(f.gross_revenue) as total_revenue'),
            DB::raw('SUM(f.cogs_amount) as total_cogs'),
            DB::raw('SUM(f.gross_margin_amount) as total_margin'),
            DB::raw('AVG(f.gross_margin_percent) as avg_margin_percent')
        )
        ->groupBy('p.id', 'p.name', 'p.category')
        ->orderByDesc('total_revenue');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get sales by customer
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|null $limit
     * @return Collection
     */
    public function getSalesByCustomer(int $businessId, ?string $startDate = null, ?string $endDate = null, ?int $limit = null): Collection
    {
        $query = DB::table('fact_sales as f')
            ->join('dim_customer as c', 'c.id', '=', 'f.customer_id')
            ->where('f.business_id', $businessId)
            ->where('c.customer_nk', '!=', 'UNKNOWN'); // Exclude unknown customers

        if ($startDate && $endDate) {
            $query->join('dim_date as d', 'd.id', '=', 'f.date_id')
                ->whereBetween('d.date', [$startDate, $endDate]);
        }

        $query->select(
            'c.name as customer_name',
            'c.customer_type',
            DB::raw('COUNT(DISTINCT f.date_id) as transaction_count'),
            DB::raw('SUM(f.quantity) as total_quantity'),
            DB::raw('SUM(f.gross_revenue) as total_revenue'),
            DB::raw('SUM(f.gross_margin_amount) as total_margin')
        )
        ->groupBy('c.id', 'c.name', 'c.customer_type')
        ->orderByDesc('total_revenue');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get sales by channel
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getSalesByChannel(int $businessId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = DB::table('fact_sales as f')
            ->join('dim_channel as ch', 'ch.id', '=', 'f.channel_id')
            ->where('f.business_id', $businessId);

        if ($startDate && $endDate) {
            $query->join('dim_date as d', 'd.id', '=', 'f.date_id')
                ->whereBetween('d.date', [$startDate, $endDate]);
        }

        return $query->select(
            'ch.name as channel_name',
            DB::raw('SUM(f.quantity) as total_quantity'),
            DB::raw('SUM(f.gross_revenue) as total_revenue'),
            DB::raw('SUM(f.cogs_amount) as total_cogs'),
            DB::raw('SUM(f.gross_margin_amount) as total_margin')
        )
        ->groupBy('ch.id', 'ch.name')
        ->orderByDesc('total_revenue')
        ->get();
    }

    /**
     * Get summary metrics for business
     *
     * @param int $businessId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getSummaryMetrics(int $businessId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = DB::table('fact_sales as f')
            ->where('f.business_id', $businessId);

        if ($startDate && $endDate) {
            $query->join('dim_date as d', 'd.id', '=', 'f.date_id')
                ->whereBetween('d.date', [$startDate, $endDate]);
        }

        $result = $query->select(
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(f.quantity) as total_quantity'),
            DB::raw('SUM(f.gross_revenue) as total_revenue'),
            DB::raw('SUM(f.cogs_amount) as total_cogs'),
            DB::raw('SUM(f.gross_margin_amount) as total_margin'),
            DB::raw('AVG(f.gross_margin_percent) as avg_margin_percent'),
            DB::raw('COUNT(DISTINCT f.product_id) as unique_products'),
            DB::raw('COUNT(DISTINCT f.customer_id) as unique_customers')
        )->first();

        return [
            'transaction_count' => (int)$result->transaction_count,
            'total_quantity' => (float)$result->total_quantity,
            'total_revenue' => (float)$result->total_revenue,
            'total_cogs' => (float)$result->total_cogs,
            'total_margin' => (float)$result->total_margin,
            'avg_margin_percent' => (float)$result->avg_margin_percent,
            'unique_products' => (int)$result->unique_products,
            'unique_customers' => (int)$result->unique_customers,
        ];
    }

    /**
     * Get monthly aggregated sales
     *
     * @param int $businessId
     * @param int $months Number of months to retrieve
     * @return Collection
     */
    public function getMonthlySales(int $businessId, int $months = 12): Collection
    {
        return DB::table('fact_sales as f')
            ->join('dim_date as d', 'd.id', '=', 'f.date_id')
            ->where('f.business_id', $businessId)
            ->where('d.date', '>=', now()->subMonths($months)->startOfMonth())
            ->select(
                'd.year',
                'd.month',
                'd.fiscal_period',
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_revenue) as total_revenue'),
                DB::raw('SUM(f.cogs_amount) as total_cogs'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin'),
                DB::raw('AVG(f.gross_margin_percent) as avg_margin_percent')
            )
            ->groupBy('d.year', 'd.month', 'd.fiscal_period')
            ->orderBy('d.year')
            ->orderBy('d.month')
            ->get();
    }

    /**
     * Get quarterly aggregated sales
     *
     * @param int $businessId
     * @param int $quarters Number of quarters to retrieve
     * @return Collection
     */
    public function getQuarterlySales(int $businessId, int $quarters = 4): Collection
    {
        return DB::table('fact_sales as f')
            ->join('dim_date as d', 'd.id', '=', 'f.date_id')
            ->where('f.business_id', $businessId)
            ->where('d.date', '>=', now()->subMonths($quarters * 3)->startOfQuarter())
            ->select(
                'd.year',
                'd.quarter',
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_revenue) as total_revenue'),
                DB::raw('SUM(f.cogs_amount) as total_cogs'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin'),
                DB::raw('AVG(f.gross_margin_percent) as avg_margin_percent')
            )
            ->groupBy('d.year', 'd.quarter')
            ->orderBy('d.year')
            ->orderBy('d.quarter')
            ->get();
    }

    /**
     * Delete facts by data feed ID
     *
     * @param int $dataFeedId
     * @return int Number of rows deleted
     */
    public function deleteByDataFeedId(int $dataFeedId): int
    {
        return DB::table('fact_sales')
            ->where('data_feed_id', $dataFeedId)
            ->delete();
    }

    /**
     * Get count of facts by business
     *
     * @param int $businessId
     * @return int
     */
    public function countByBusiness(int $businessId): int
    {
        return DB::table('fact_sales')
            ->where('business_id', $businessId)
            ->count();
    }
}
