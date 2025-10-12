<?php

namespace App\Services\Olap;

use App\Models\DimLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OlapLocationService
{
    /**
     * Ensure location dimension exists, create if not found.
     * Returns location_id for use in fact tables.
     */
    public function ensureLocationDim(
        int $businessId,
        ?string $provinsi = null,
        ?string $kota = null,
        ?string $kecamatan = null,
        ?string $locationNk = null
    ): int {
        // Normalize input
        $provinsi = $provinsi ? trim($provinsi) : 'Unknown';
        $kota = $kota ? trim($kota) : 'Unknown';
        $kecamatan = $kecamatan ? trim($kecamatan) : 'Unknown';

        // Try to find existing location
        $location = DimLocation::where('business_id', $businessId)
            ->where('provinsi', $provinsi)
            ->where('kota', $kota)
            ->where('kecamatan', $kecamatan)
            ->first();

        if ($location) {
            return $location->id;
        }

        // Check if global reference location exists
        $globalLocation = DimLocation::whereNull('business_id')
            ->where('provinsi', $provinsi)
            ->where('kota', $kota)
            ->where('kecamatan', $kecamatan)
            ->first();

        if ($globalLocation) {
            // Clone to business-specific location
            $location = DimLocation::create([
                'business_id' => $businessId,
                'location_nk' => $locationNk,
                'provinsi' => $provinsi,
                'kota' => $kota,
                'kecamatan' => $kecamatan,
            ]);
            return $location->id;
        }

        // Create new location
        $location = DimLocation::create([
            'business_id' => $businessId,
            'location_nk' => $locationNk,
            'provinsi' => $provinsi,
            'kota' => $kota,
            'kecamatan' => $kecamatan,
        ]);

        return $location->id;
    }

    /**
     * Get location hierarchy for drill-down navigation.
     */
    public function getLocationHierarchy(int $businessId, ?string $cacheKey = null): array
    {
        $key = $cacheKey ?? "location_hierarchy_{$businessId}";

        return Cache::remember($key, 3600, function () use ($businessId) {
            $hierarchy = [];

            // Get all provinces
            $provinces = DimLocation::forBusiness($businessId)
                ->whereNotNull('provinsi')
                ->where('provinsi', '!=', 'Unknown')
                ->distinct()
                ->pluck('provinsi')
                ->sort()
                ->values();

            foreach ($provinces as $provinsi) {
                $cities = DimLocation::forBusiness($businessId)
                    ->where('provinsi', $provinsi)
                    ->whereNotNull('kota')
                    ->distinct()
                    ->pluck('kota')
                    ->sort()
                    ->values();

                $hierarchy[$provinsi] = [];

                foreach ($cities as $kota) {
                    $districts = DimLocation::forBusiness($businessId)
                        ->where('provinsi', $provinsi)
                        ->where('kota', $kota)
                        ->whereNotNull('kecamatan')
                        ->distinct()
                        ->pluck('kecamatan')
                        ->sort()
                        ->values();

                    $hierarchy[$provinsi][$kota] = $districts->toArray();
                }
            }

            return $hierarchy;
        });
    }

    /**
     * Drill down sales by location level.
     *
     * @param int $businessId
     * @param string $level 'provinsi', 'kota', or 'kecamatan'
     * @param string|null $parentFilter Parent level value (e.g., provinsi when drilling to kota)
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function drillDownSalesByLocation(
        int $businessId,
        string $level = 'provinsi',
        ?string $parentFilter = null,
        ?string $parentLevel = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = DB::table('fact_sales as fs')
            ->join('dim_location as dl', 'fs.location_id', '=', 'dl.id')
            ->join('dim_date as dd', 'fs.date_id', '=', 'dd.id')
            ->where('fs.business_id', $businessId);

        // Apply parent filter for drill-down
        if ($parentFilter && $parentLevel) {
            $query->where("dl.{$parentLevel}", $parentFilter);
        }

        // Apply date range
        if ($startDate) {
            $query->where('dd.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('dd.date', '<=', $endDate);
        }

        // Group by level
        $results = $query->select(
            "dl.{$level} as location",
            DB::raw('SUM(fs.gross_revenue) as total_revenue'),
            DB::raw('SUM(fs.net_revenue) as net_revenue'),
            DB::raw('SUM(fs.cogs_amount) as total_cogs'),
            DB::raw('SUM(fs.gross_margin) as total_margin'),
            DB::raw('COUNT(DISTINCT fs.transaction_id) as transaction_count'),
            DB::raw('COUNT(*) as item_count'),
            DB::raw('ROUND(AVG(fs.margin_percentage), 2) as avg_margin_pct')
        )
        ->groupBy("dl.{$level}")
        ->orderByDesc('total_revenue')
        ->get()
        ->toArray();

        return array_map(function ($row) {
            return (array) $row;
        }, $results);
    }

    /**
     * Get sales trend by location and time.
     */
    public function getSalesTrendByLocation(
        int $businessId,
        string $locationLevel,
        string $locationValue,
        string $timeGranularity = 'daily', // daily, monthly
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = DB::table('fact_sales as fs')
            ->join('dim_location as dl', 'fs.location_id', '=', 'dl.id')
            ->join('dim_date as dd', 'fs.date_id', '=', 'dd.id')
            ->where('fs.business_id', $businessId)
            ->where("dl.{$locationLevel}", $locationValue);

        if ($startDate) {
            $query->where('dd.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('dd.date', '<=', $endDate);
        }

        // Determine grouping
        $dateGrouping = $timeGranularity === 'monthly'
            ? [DB::raw('dd.year'), DB::raw('dd.month')]
            : ['dd.date'];

        $dateSelection = $timeGranularity === 'monthly'
            ? [
                DB::raw('dd.year as year'),
                DB::raw('dd.month as month'),
                DB::raw("CONCAT(dd.year, '-', LPAD(dd.month, 2, '0')) as period")
            ]
            : ['dd.date as period'];

        $results = $query->select(array_merge($dateSelection, [
            DB::raw('SUM(fs.gross_revenue) as revenue'),
            DB::raw('SUM(fs.gross_margin) as margin'),
            DB::raw('COUNT(DISTINCT fs.transaction_id) as transactions')
        ]))
        ->groupBy(...$dateGrouping)
        ->orderBy($timeGranularity === 'monthly' ? 'year' : 'period')
        ->when($timeGranularity === 'monthly', fn($q) => $q->orderBy('month'))
        ->get()
        ->toArray();

        return array_map(function ($row) {
            return (array) $row;
        }, $results);
    }

    /**
     * Invalidate location hierarchy cache.
     */
    public function invalidateCache(int $businessId): void
    {
        Cache::forget("location_hierarchy_{$businessId}");
    }

    /**
     * Get top performing locations by level.
     */
    public function getTopLocationsByLevel(
        int $businessId,
        string $level = 'provinsi',
        int $limit = 10,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        return $this->drillDownSalesByLocation(
            $businessId,
            $level,
            null,
            null,
            $startDate,
            $endDate
        );
    }
}
