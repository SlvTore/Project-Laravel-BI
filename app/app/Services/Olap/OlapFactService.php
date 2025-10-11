<?php

namespace App\Services\Olap;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles fact table operations (inserts, updates, deletes)
 * Separated from ETL and query concerns for better maintainability
 */
class OlapFactService
{
    public function __construct(
        private OlapDimensionService $dimensionService
    ) {}

    /**
     * Insert a single fact record
     *
     * @param array $factData
     * @return int The inserted fact ID
     */
    public function insertFact(array $factData): int
    {
        // Validate required fields
        $required = ['business_id', 'date_id', 'product_id', 'quantity', 'gross_revenue'];
        foreach ($required as $field) {
            if (!isset($factData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Set defaults for optional dimension IDs
        $factData['customer_id'] = $factData['customer_id'] ?? 
            $this->dimensionService->getOrCreateCustomerDimension($factData['business_id'], null, 'Unknown', null, null)->id;
        
        $factData['channel_id'] = $factData['channel_id'] ?? 
            $this->dimensionService->getOrCreateChannelDimension($factData['business_id'], 'DIRECT', 'Direct Sales')->id;
        
        $factData['promotion_id'] = $factData['promotion_id'] ?? 
            $this->dimensionService->getOrCreatePromotionDimension($factData['business_id'], 'NONE', 'No Promotion')->id;

        // Calculate derived fields if not provided
        if (!isset($factData['gross_margin_amount']) && isset($factData['cogs_amount'])) {
            $factData['gross_margin_amount'] = $factData['gross_revenue'] - $factData['cogs_amount'];
        }

        if (!isset($factData['gross_margin_percent']) && isset($factData['cogs_amount']) && $factData['gross_revenue'] > 0) {
            $factData['gross_margin_percent'] = (($factData['gross_revenue'] - $factData['cogs_amount']) / $factData['gross_revenue']) * 100;
        }

        $factData['created_at'] = now();
        $factData['updated_at'] = now();

        return DB::table('fact_sales')->insertGetId($factData);
    }

    /**
     * Bulk insert fact records (more efficient than individual inserts)
     *
     * @param array $factsData Array of fact arrays
     * @return int Number of rows inserted
     */
    public function bulkInsertFacts(array $factsData): int
    {
        if (empty($factsData)) {
            return 0;
        }

        $timestamp = now();
        $prepared = [];

        foreach ($factsData as $fact) {
            // Set defaults for optional dimension IDs
            $fact['customer_id'] = $fact['customer_id'] ?? 
                $this->dimensionService->getOrCreateCustomerDimension($fact['business_id'], null, 'Unknown', null, null)->id;
            
            $fact['channel_id'] = $fact['channel_id'] ?? 
                $this->dimensionService->getOrCreateChannelDimension($fact['business_id'], 'DIRECT', 'Direct Sales')->id;
            
            $fact['promotion_id'] = $fact['promotion_id'] ?? 
                $this->dimensionService->getOrCreatePromotionDimension($fact['business_id'], 'NONE', 'No Promotion')->id;

            // Calculate derived fields
            if (!isset($fact['gross_margin_amount']) && isset($fact['cogs_amount'])) {
                $fact['gross_margin_amount'] = $fact['gross_revenue'] - $fact['cogs_amount'];
            }

            if (!isset($fact['gross_margin_percent']) && isset($fact['cogs_amount']) && $fact['gross_revenue'] > 0) {
                $fact['gross_margin_percent'] = (($fact['gross_revenue'] - $fact['cogs_amount']) / $fact['gross_revenue']) * 100;
            }

            $fact['created_at'] = $timestamp;
            $fact['updated_at'] = $timestamp;

            $prepared[] = $fact;
        }

        DB::table('fact_sales')->insert($prepared);

        return count($prepared);
    }

    /**
     * Update a fact record
     *
     * @param int $factId
     * @param array $updates
     * @return bool
     */
    public function updateFact(int $factId, array $updates): bool
    {
        // Recalculate derived fields if base fields are updated
        if (isset($updates['gross_revenue']) || isset($updates['cogs_amount'])) {
            $existing = DB::table('fact_sales')->find($factId);
            $revenue = $updates['gross_revenue'] ?? $existing->gross_revenue;
            $cogs = $updates['cogs_amount'] ?? $existing->cogs_amount;

            $updates['gross_margin_amount'] = $revenue - $cogs;
            if ($revenue > 0) {
                $updates['gross_margin_percent'] = (($revenue - $cogs) / $revenue) * 100;
            }
        }

        $updates['updated_at'] = now();

        return DB::table('fact_sales')
            ->where('id', $factId)
            ->update($updates) > 0;
    }

    /**
     * Delete a fact record
     *
     * @param int $factId
     * @return bool
     */
    public function deleteFact(int $factId): bool
    {
        return DB::table('fact_sales')
            ->where('id', $factId)
            ->delete() > 0;
    }

    /**
     * Delete all facts for a specific data feed
     * Used when reprocessing or deleting a data feed
     *
     * @param int $dataFeedId
     * @return int Number of rows deleted
     */
    public function deleteFactsByDataFeed(int $dataFeedId): int
    {
        $count = DB::table('fact_sales')
            ->where('data_feed_id', $dataFeedId)
            ->count();

        DB::table('fact_sales')
            ->where('data_feed_id', $dataFeedId)
            ->delete();

        Log::info("Deleted {$count} facts for data feed ID: {$dataFeedId}");

        return $count;
    }

    /**
     * Delete all facts for a business within a date range
     * Used for data corrections or reloads
     *
     * @param int $businessId
     * @param string $startDate
     * @param string $endDate
     * @return int Number of rows deleted
     */
    public function deleteFactsByDateRange(int $businessId, string $startDate, string $endDate): int
    {
        $dateIds = DB::table('dim_date')
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('id');

        $count = DB::table('fact_sales')
            ->where('business_id', $businessId)
            ->whereIn('date_id', $dateIds)
            ->count();

        DB::table('fact_sales')
            ->where('business_id', $businessId)
            ->whereIn('date_id', $dateIds)
            ->delete();

        Log::info("Deleted {$count} facts for business {$businessId} from {$startDate} to {$endDate}");

        return $count;
    }

    /**
     * Recalculate derived fields for all facts
     * Useful after fixing data quality issues
     *
     * @param int|null $businessId Optional: limit to specific business
     * @return int Number of rows updated
     */
    public function recalculateDerivedFields(?int $businessId = null): int
    {
        $query = DB::table('fact_sales');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        // Process in chunks to avoid memory issues
        $updated = 0;
        $query->chunkById(500, function ($facts) use (&$updated) {
            foreach ($facts as $fact) {
                $marginAmount = $fact->gross_revenue - $fact->cogs_amount;
                $marginPercent = $fact->gross_revenue > 0 
                    ? (($fact->gross_revenue - $fact->cogs_amount) / $fact->gross_revenue) * 100 
                    : 0;

                DB::table('fact_sales')
                    ->where('id', $fact->id)
                    ->update([
                        'gross_margin_amount' => $marginAmount,
                        'gross_margin_percent' => $marginPercent,
                        'updated_at' => now(),
                    ]);

                $updated++;
            }
        });

        Log::info("Recalculated derived fields for {$updated} facts");

        return $updated;
    }

    /**
     * Get fact by ID
     *
     * @param int $factId
     * @return object|null
     */
    public function getFactById(int $factId): ?object
    {
        return DB::table('fact_sales')->find($factId);
    }

    /**
     * Check if fact exists
     *
     * @param int $factId
     * @return bool
     */
    public function factExists(int $factId): bool
    {
        return DB::table('fact_sales')
            ->where('id', $factId)
            ->exists();
    }
}
