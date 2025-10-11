<?php

namespace App\Services\Olap;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DataFeed;

/**
 * Handles ETL operations: staging to warehouse transformations
 * Implements batch processing for memory efficiency
 */
class OlapETLService
{
    public function __construct(
        private OlapDimensionService $dimensionService,
        private OlapFactService $factService
    ) {}

    /**
     * Load facts from staging tables with batch processing
     *
     * @param int $dataFeedId
     * @param int $batchSize Number of records to process per batch
     * @return array Statistics about the ETL process
     */
    public function loadFactsFromStaging(int $dataFeedId, int $batchSize = 500): array
    {
        $dataFeed = DataFeed::findOrFail($dataFeedId);
        $businessId = $dataFeed->business_id;

        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'errors' => 0,
            'start_time' => now(),
        ];

        // Ensure default dimensions exist before processing
        $this->dimensionService->ensureDefaultDimensions($businessId);

        try {
            // Process staging_sales_items in chunks
            DB::table('staging_sales_items')
                ->where('data_feed_id', $dataFeedId)
                ->orderBy('id')
                ->chunkById($batchSize, function ($items) use ($businessId, $dataFeedId, &$stats) {
                    $this->processStagingBatch($items, $businessId, $dataFeedId, $stats);
                });

            $stats['end_time'] = now();
            $stats['duration_seconds'] = $stats['start_time']->diffInSeconds($stats['end_time']);

            Log::info("ETL completed for data feed {$dataFeedId}", $stats);

        } catch (\Exception $e) {
            Log::error("ETL failed for data feed {$dataFeedId}: " . $e->getMessage(), [
                'exception' => $e,
                'stats' => $stats,
            ]);

            throw $e;
        }

        return $stats;
    }

    /**
     * Process a single batch of staging items
     *
     * @param \Illuminate\Support\Collection $items
     * @param int $businessId
     * @param int $dataFeedId
     * @param array &$stats
     */
    private function processStagingBatch($items, int $businessId, int $dataFeedId, array &$stats): void
    {
        $factsToInsert = [];

        foreach ($items as $item) {
            $stats['processed']++;

            try {
                // Get or create dimensions
                $dateId = $this->dimensionService->getOrCreateDateDimension($item->transaction_date)->id;

                $productId = $this->dimensionService->getOrCreateProductDimension(
                    $businessId,
                    $item->product_id,
                    $item->product_name,
                    $item->product_category
                )->id;

                $customerId = $this->dimensionService->getOrCreateCustomerDimension(
                    $businessId,
                    $item->customer_id,
                    $item->customer_name,
                    $item->customer_type,
                    $item->customer_phone
                )->id;

                $channelId = $this->dimensionService->getOrCreateChannelDimension(
                    $businessId,
                    $item->channel ?? 'DIRECT',
                    $item->channel_name ?? 'Direct Sales'
                )->id;

                $promotionId = $this->dimensionService->getOrCreatePromotionDimension(
                    $businessId,
                    $item->promotion_code ?? 'NONE',
                    $item->promotion_name ?? 'No Promotion',
                    $item->promotion_discount_percent ?? 0
                )->id;

                // Calculate COGS and margin
                $cogsAmount = $item->unit_cost * $item->quantity;
                $marginAmount = $item->total_amount - $cogsAmount;
                $marginPercent = $item->total_amount > 0
                    ? ($marginAmount / $item->total_amount) * 100
                    : 0;

                // Prepare fact record
                $factsToInsert[] = [
                    'business_id' => $businessId,
                    'data_feed_id' => $dataFeedId,
                    'date_id' => $dateId,
                    'product_id' => $productId,
                    'customer_id' => $customerId,
                    'channel_id' => $channelId,
                    'promotion_id' => $promotionId,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'gross_revenue' => $item->total_amount,
                    'discount_amount' => $item->discount_amount ?? 0,
                    'net_revenue' => $item->total_amount - ($item->discount_amount ?? 0),
                    'unit_cost' => $item->unit_cost,
                    'cogs_amount' => $cogsAmount,
                    'gross_margin_amount' => $marginAmount,
                    'gross_margin_percent' => $marginPercent,
                ];

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::warning("Failed to process staging item {$item->id}: " . $e->getMessage(), [
                    'item' => $item,
                    'exception' => $e,
                ]);
            }
        }

        // Bulk insert facts
        if (!empty($factsToInsert)) {
            $inserted = $this->factService->bulkInsertFacts($factsToInsert);
            $stats['inserted'] += $inserted;
        }
    }

    /**
     * Clear staging tables after successful ETL
     *
     * @param int $dataFeedId
     * @return int Number of rows deleted
     */
    public function clearStaging(int $dataFeedId): int
    {
        $deleted = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->delete();

        Log::info("Cleared {$deleted} staging records for data feed {$dataFeedId}");

        return $deleted;
    }

    /**
     * Rollback ETL: delete facts and optionally restore staging
     *
     * @param int $dataFeedId
     * @param bool $keepStaging
     * @return array Statistics about the rollback
     */
    public function rollbackETL(int $dataFeedId, bool $keepStaging = false): array
    {
        DB::beginTransaction();

        try {
            $factsDeleted = $this->factService->deleteFactsByDataFeed($dataFeedId);

            $stagingDeleted = 0;
            if (!$keepStaging) {
                $stagingDeleted = $this->clearStaging($dataFeedId);
            }

            DB::commit();

            $stats = [
                'data_feed_id' => $dataFeedId,
                'facts_deleted' => $factsDeleted,
                'staging_deleted' => $stagingDeleted,
                'timestamp' => now(),
            ];

            Log::info("ETL rollback completed for data feed {$dataFeedId}", $stats);

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ETL rollback failed for data feed {$dataFeedId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate staging data before ETL
     *
     * @param int $dataFeedId
     * @return array Validation results
     */
    public function validateStagingData(int $dataFeedId): array
    {
        $issues = [];

        // Check for required fields
        $missingData = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->where(function ($q) {
                $q->whereNull('transaction_date')
                    ->orWhereNull('product_id')
                    ->orWhereNull('quantity')
                    ->orWhereNull('total_amount');
            })
            ->count();

        if ($missingData > 0) {
            $issues[] = "Found {$missingData} records with missing required fields";
        }

        // Check for negative values
        $negativeValues = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->where(function ($q) {
                $q->where('quantity', '<', 0)
                    ->orWhere('total_amount', '<', 0)
                    ->orWhere('unit_price', '<', 0)
                    ->orWhere('unit_cost', '<', 0);
            })
            ->count();

        if ($negativeValues > 0) {
            $issues[] = "Found {$negativeValues} records with negative values";
        }

        // Check for invalid dates
        $invalidDates = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->where('transaction_date', '>', now())
            ->count();

        if ($invalidDates > 0) {
            $issues[] = "Found {$invalidDates} records with future dates";
        }

        // Check for zero-cost items
        $zeroCost = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->where('unit_cost', 0)
            ->count();

        if ($zeroCost > 0) {
            $issues[] = "Found {$zeroCost} records with zero unit cost (margin calculation may be inaccurate)";
        }

        $totalRecords = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->count();

        return [
            'valid' => empty($issues),
            'total_records' => $totalRecords,
            'issues' => $issues,
            'timestamp' => now(),
        ];
    }

    /**
     * Get staging data statistics
     *
     * @param int $dataFeedId
     * @return array
     */
    public function getStagingStats(int $dataFeedId): array
    {
        $stats = DB::table('staging_sales_items')
            ->where('data_feed_id', $dataFeedId)
            ->select(
                DB::raw('COUNT(*) as total_records'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT product_id) as unique_products'),
                DB::raw('COUNT(DISTINCT customer_id) as unique_customers'),
                DB::raw('MIN(transaction_date) as earliest_date'),
                DB::raw('MAX(transaction_date) as latest_date')
            )
            ->first();

        return [
            'total_records' => (int)$stats->total_records,
            'total_quantity' => (float)$stats->total_quantity,
            'total_revenue' => (float)$stats->total_revenue,
            'unique_products' => (int)$stats->unique_products,
            'unique_customers' => (int)$stats->unique_customers,
            'earliest_date' => $stats->earliest_date,
            'latest_date' => $stats->latest_date,
        ];
    }
}
