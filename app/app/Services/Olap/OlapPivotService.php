<?php

namespace App\Services\Olap;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OlapPivotService
{
    /**
     * Generate pivot data for warehouse section
     *
     * @param int $businessId
     * @param string $metric (sales, cogs, margin, etc.)
     * @param string $pivotBy (date, location, product, customer)
     * @param string $aggregation (sum, avg, count, max, min)
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array $filters
     * @return array
     */
    public function generatePivotData(
        int $businessId,
        string $metric = 'sales',
        string $pivotBy = 'date',
        string $aggregation = 'sum',
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $filters = []
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        switch ($metric) {
            case 'sales':
                return $this->pivotSalesData($businessId, $pivotBy, $aggregation, $startDate, $endDate, $filters);
            case 'cogs':
                return $this->pivotCogsData($businessId, $pivotBy, $aggregation, $startDate, $endDate, $filters);
            case 'margin':
                return $this->pivotMarginData($businessId, $pivotBy, $aggregation, $startDate, $endDate, $filters);
            default:
                return $this->pivotSalesData($businessId, $pivotBy, $aggregation, $startDate, $endDate, $filters);
        }
    }

    /**
     * Pivot sales data by specified dimension
     */
    private function pivotSalesData(int $businessId, string $pivotBy, string $aggregation, Carbon $startDate, Carbon $endDate, array $filters): array
    {
        $baseQuery = DB::table('fact_sales as f')
            ->join('dim_date as d', 'f.date_id', '=', 'd.id')
            ->leftJoin('dim_location as l', 'f.location_id', '=', 'l.id')
            ->leftJoin('dim_product as p', 'f.product_id', '=', 'p.id')
            ->leftJoin('dim_customer as c', 'f.customer_id', '=', 'c.id')
            ->where('f.business_id', $businessId)
            ->whereBetween('d.date', [$startDate->toDateString(), $endDate->toDateString()]);

        // Apply filters
        if (!empty($filters['provinsi'])) {
            $baseQuery->where('l.provinsi', $filters['provinsi']);
        }
        if (!empty($filters['kota'])) {
            $baseQuery->where('l.kota', $filters['kota']);
        }
        if (!empty($filters['kecamatan'])) {
            $baseQuery->where('l.kecamatan', $filters['kecamatan']);
        }
        if (!empty($filters['product_category'])) {
            $baseQuery->where('p.category', $filters['product_category']);
        }

        // Determine pivot columns and grouping
        switch ($pivotBy) {
            case 'location':
                return $this->pivotByLocation($baseQuery, $aggregation);
            case 'product':
                return $this->pivotByProduct($baseQuery, $aggregation);
            case 'customer':
                return $this->pivotByCustomer($baseQuery, $aggregation);
            case 'date':
            default:
                return $this->pivotByDate($baseQuery, $aggregation);
        }
    }

    /**
     * Pivot by location (Provinsi → Kota → Kecamatan)
     */
    private function pivotByLocation($baseQuery, string $aggregation): array
    {
        $aggregateFunction = $this->getAggregateFunction($aggregation, 'f.gross_revenue');

        // Get province-level data
        $provinsiData = (clone $baseQuery)
            ->select([
                'l.provinsi as pivot_key',
                DB::raw("{$aggregateFunction} as value"),
                DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin')
            ])
            ->whereNotNull('l.provinsi')
            ->groupBy('l.provinsi')
            ->orderByDesc('value')
            ->get();

        // Get city-level breakdown for each province
        $cityBreakdown = [];
        foreach ($provinsiData as $prov) {
            $cities = (clone $baseQuery)
                ->select([
                    'l.kota as pivot_key',
                    DB::raw("{$aggregateFunction} as value"),
                    DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                    DB::raw('SUM(f.quantity) as total_quantity'),
                    DB::raw('SUM(f.gross_margin_amount) as total_margin')
                ])
                ->where('l.provinsi', $prov->pivot_key)
                ->whereNotNull('l.kota')
                ->groupBy('l.kota')
                ->orderByDesc('value')
                ->limit(10)
                ->get();

            $cityBreakdown[$prov->pivot_key] = $cities;
        }

        return [
            'pivot_type' => 'location',
            'pivot_by' => 'provinsi',
            'aggregation' => $aggregation,
            'data' => $provinsiData,
            'drill_down' => $cityBreakdown,
            'summary' => [
                'total_provinces' => $provinsiData->count(),
                'total_revenue' => $provinsiData->sum('value'),
                'total_transactions' => $provinsiData->sum('transaction_count'),
                'total_quantity' => $provinsiData->sum('total_quantity'),
                'total_margin' => $provinsiData->sum('total_margin')
            ]
        ];
    }

    /**
     * Pivot by product
     */
    private function pivotByProduct($baseQuery, string $aggregation): array
    {
        $aggregateFunction = $this->getAggregateFunction($aggregation, 'f.gross_revenue');

        $productData = (clone $baseQuery)
            ->select([
                'p.name as pivot_key',
                'p.category as product_category',
                DB::raw("{$aggregateFunction} as value"),
                DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin')
            ])
            ->whereNotNull('p.name')
            ->groupBy('p.name', 'p.category')
            ->orderByDesc('value')
            ->limit(50)
            ->get();

        // Group by category
        $categoryBreakdown = $productData->groupBy('product_category');

        return [
            'pivot_type' => 'product',
            'pivot_by' => 'product_name',
            'aggregation' => $aggregation,
            'data' => $productData,
            'drill_down' => $categoryBreakdown,
            'summary' => [
                'total_products' => $productData->count(),
                'total_revenue' => $productData->sum('value'),
                'total_transactions' => $productData->sum('transaction_count'),
                'total_quantity' => $productData->sum('total_quantity'),
                'total_margin' => $productData->sum('total_margin')
            ]
        ];
    }

    /**
     * Pivot by customer
     */
    private function pivotByCustomer($baseQuery, string $aggregation): array
    {
        $aggregateFunction = $this->getAggregateFunction($aggregation, 'f.gross_revenue');

        $customerData = (clone $baseQuery)
            ->select([
                'c.customer_name as pivot_key',
                'c.email as customer_email',
                DB::raw("{$aggregateFunction} as value"),
                DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin')
            ])
            ->whereNotNull('c.customer_name')
            ->groupBy('c.customer_name', 'c.email')
            ->orderByDesc('value')
            ->limit(50)
            ->get();

        return [
            'pivot_type' => 'customer',
            'pivot_by' => 'customer_name',
            'aggregation' => $aggregation,
            'data' => $customerData,
            'summary' => [
                'total_customers' => $customerData->count(),
                'total_revenue' => $customerData->sum('value'),
                'total_transactions' => $customerData->sum('transaction_count'),
                'total_quantity' => $customerData->sum('total_quantity'),
                'total_margin' => $customerData->sum('total_margin')
            ]
        ];
    }

    /**
     * Pivot by date
     */
    private function pivotByDate($baseQuery, string $aggregation): array
    {
        $aggregateFunction = $this->getAggregateFunction($aggregation, 'f.gross_revenue');

        $dateData = (clone $baseQuery)
            ->select([
                'd.date as pivot_key',
                DB::raw("{$aggregateFunction} as value"),
                DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                DB::raw('SUM(f.quantity) as total_quantity'),
                DB::raw('SUM(f.gross_margin_amount) as total_margin')
            ])
            ->groupBy('d.date')
            ->orderBy('d.date')
            ->get();

        return [
            'pivot_type' => 'date',
            'pivot_by' => 'date',
            'aggregation' => $aggregation,
            'data' => $dateData,
            'summary' => [
                'total_days' => $dateData->count(),
                'total_revenue' => $dateData->sum('value'),
                'total_transactions' => $dateData->sum('transaction_count'),
                'total_quantity' => $dateData->sum('total_quantity'),
                'total_margin' => $dateData->sum('total_margin')
            ]
        ];
    }

    /**
     * Pivot COGS data
     */
    private function pivotCogsData(int $businessId, string $pivotBy, string $aggregation, Carbon $startDate, Carbon $endDate, array $filters): array
    {
        $baseQuery = DB::table('fact_sales as f')
            ->join('dim_date as d', 'f.date_id', '=', 'd.id')
            ->leftJoin('dim_location as l', 'f.location_id', '=', 'l.id')
            ->leftJoin('dim_product as p', 'f.product_id', '=', 'p.id')
            ->where('f.business_id', $businessId)
            ->whereBetween('d.date', [$startDate->toDateString(), $endDate->toDateString()]);

        // Apply filters
        $this->applyFilters($baseQuery, $filters);

        $aggregateFunction = $this->getAggregateFunction($aggregation, 'f.cogs_amount');

        switch ($pivotBy) {
            case 'location':
                $data = (clone $baseQuery)
                    ->select([
                        'l.provinsi as pivot_key',
                        DB::raw("{$aggregateFunction} as value")
                    ])
                    ->whereNotNull('l.provinsi')
                    ->groupBy('l.provinsi')
                    ->orderByDesc('value')
                    ->get();
                break;
            case 'product':
                $data = (clone $baseQuery)
                    ->select([
                        'p.name as pivot_key',
                        DB::raw("{$aggregateFunction} as value")
                    ])
                    ->whereNotNull('p.name')
                    ->groupBy('p.name')
                    ->orderByDesc('value')
                    ->limit(50)
                    ->get();
                break;
            default:
                $data = (clone $baseQuery)
                    ->select([
                        'd.date as pivot_key',
                        DB::raw("{$aggregateFunction} as value")
                    ])
                    ->groupBy('d.date')
                    ->orderBy('d.date')
                    ->get();
        }

        return [
            'pivot_type' => 'cogs',
            'pivot_by' => $pivotBy,
            'aggregation' => $aggregation,
            'data' => $data,
            'summary' => [
                'total_cogs' => $data->sum('value')
            ]
        ];
    }

    /**
     * Pivot margin data
     */
    private function pivotMarginData(int $businessId, string $pivotBy, string $aggregation, Carbon $startDate, Carbon $endDate, array $filters): array
    {
        $baseQuery = DB::table('fact_sales as f')
            ->join('dim_date as d', 'f.date_id', '=', 'd.id')
            ->leftJoin('dim_location as l', 'f.location_id', '=', 'l.id')
            ->leftJoin('dim_product as p', 'f.product_id', '=', 'p.id')
            ->where('f.business_id', $businessId)
            ->whereBetween('d.date', [$startDate->toDateString(), $endDate->toDateString()]);

        $this->applyFilters($baseQuery, $filters);

        $aggregateFunction = $this->getAggregateFunction($aggregation, 'f.gross_margin_amount');

        switch ($pivotBy) {
            case 'location':
                $data = (clone $baseQuery)
                    ->select([
                        'l.provinsi as pivot_key',
                        DB::raw("{$aggregateFunction} as value"),
                        DB::raw('SUM(f.gross_revenue) as total_revenue'),
                        DB::raw('ROUND((SUM(f.gross_margin_amount) / SUM(f.gross_revenue)) * 100, 2) as margin_percentage')
                    ])
                    ->whereNotNull('l.provinsi')
                    ->groupBy('l.provinsi')
                    ->orderByDesc('value')
                    ->get();
                break;
            case 'product':
                $data = (clone $baseQuery)
                    ->select([
                        'p.name as pivot_key',
                        DB::raw("{$aggregateFunction} as value"),
                        DB::raw('SUM(f.gross_revenue) as total_revenue'),
                        DB::raw('ROUND((SUM(f.gross_margin_amount) / SUM(f.gross_revenue)) * 100, 2) as margin_percentage')
                    ])
                    ->whereNotNull('p.name')
                    ->groupBy('p.name')
                    ->orderByDesc('value')
                    ->limit(50)
                    ->get();
                break;
            default:
                $data = (clone $baseQuery)
                    ->select([
                        'd.date as pivot_key',
                        DB::raw("{$aggregateFunction} as value"),
                        DB::raw('SUM(f.gross_revenue) as total_revenue'),
                        DB::raw('ROUND((SUM(f.gross_margin_amount) / SUM(f.gross_revenue)) * 100, 2) as margin_percentage')
                    ])
                    ->groupBy('d.date')
                    ->orderBy('d.date')
                    ->get();
        }

        return [
            'pivot_type' => 'margin',
            'pivot_by' => $pivotBy,
            'aggregation' => $aggregation,
            'data' => $data,
            'summary' => [
                'total_margin' => $data->sum('value'),
                'total_revenue' => $data->sum('total_revenue'),
                'avg_margin_percentage' => $data->avg('margin_percentage')
            ]
        ];
    }

    /**
     * Get drill-down data for location
     */
    public function getLocationDrillDown(int $businessId, string $provinsi, ?string $kota = null): array
    {
        $baseQuery = DB::table('fact_sales as f')
            ->join('dim_date as d', 'f.date_id', '=', 'd.id')
            ->join('dim_location as l', 'f.location_id', '=', 'l.id')
            ->where('f.business_id', $businessId)
            ->where('l.provinsi', $provinsi);

        if ($kota) {
            // Get kecamatan breakdown
            $data = (clone $baseQuery)
                ->select([
                    'l.kecamatan as pivot_key',
                    DB::raw('SUM(f.gross_revenue) as value'),
                    DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                    DB::raw('SUM(f.quantity) as total_quantity'),
                    DB::raw('SUM(f.gross_margin_amount) as total_margin')
                ])
                ->where('l.kota', $kota)
                ->whereNotNull('l.kecamatan')
                ->groupBy('l.kecamatan')
                ->orderByDesc('value')
                ->get();

            return [
                'level' => 'kecamatan',
                'parent' => $kota,
                'data' => $data
            ];
        } else {
            // Get kota breakdown
            $data = (clone $baseQuery)
                ->select([
                    'l.kota as pivot_key',
                    DB::raw('SUM(f.gross_revenue) as value'),
                    DB::raw('COUNT(DISTINCT f.sales_transaction_id) as transaction_count'),
                    DB::raw('SUM(f.quantity) as total_quantity'),
                    DB::raw('SUM(f.gross_margin_amount) as total_margin')
                ])
                ->whereNotNull('l.kota')
                ->groupBy('l.kota')
                ->orderByDesc('value')
                ->get();

            return [
                'level' => 'kota',
                'parent' => $provinsi,
                'data' => $data
            ];
        }
    }

    /**
     * Apply filters to base query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['provinsi'])) {
            $query->where('l.provinsi', $filters['provinsi']);
        }
        if (!empty($filters['kota'])) {
            $query->where('l.kota', $filters['kota']);
        }
        if (!empty($filters['kecamatan'])) {
            $query->where('l.kecamatan', $filters['kecamatan']);
        }
        if (!empty($filters['product_category'])) {
            $query->where('p.category', $filters['product_category']);
        }
    }

    /**
     * Get SQL aggregate function based on aggregation type
     */
    private function getAggregateFunction(string $aggregation, string $column): string
    {
        switch ($aggregation) {
            case 'avg':
                return "AVG({$column})";
            case 'count':
                return "COUNT({$column})";
            case 'max':
                return "MAX({$column})";
            case 'min':
                return "MIN({$column})";
            case 'sum':
            default:
                return "SUM({$column})";
        }
    }

    /**
     * Get available pivot dimensions for a metric
     */
    public function getAvailablePivotDimensions(string $metric): array
    {
        $baseDimensions = ['date', 'location', 'product'];

        switch ($metric) {
            case 'sales':
            case 'Total Penjualan':
                return array_merge($baseDimensions, ['customer']);
            case 'cogs':
            case 'Biaya Pokok Penjualan (COGS)':
                return $baseDimensions;
            case 'margin':
            case 'Margin Keuntungan (Profit Margin)':
                return $baseDimensions;
            default:
                return $baseDimensions;
        }
    }

    /**
     * Get available aggregation functions for a metric
     */
    public function getAvailableAggregations(string $metric): array
    {
        return ['sum', 'avg', 'count', 'max', 'min'];
    }
}
