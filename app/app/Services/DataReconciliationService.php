<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service untuk cross-validation antara OLAP dan source data
 * Memastikan konsistensi dan akurasi data di seluruh sistem
 */
class DataReconciliationService
{
    private TimezoneAwareService $timezoneService;
    private SafeMathService $safeMath;
    private MetricValidatorService $validator;

    public function __construct(
        TimezoneAwareService $timezoneService,
        SafeMathService $safeMath,
        MetricValidatorService $validator
    ) {
        $this->timezoneService = $timezoneService;
        $this->safeMath = $safeMath;
        $this->validator = $validator;
    }

    /**
     * Reconcile sales data antara source dan OLAP
     */
    public function reconcileSalesData(int $businessId, $startDate = null, $endDate = null): array
    {
        $dateRange = $this->timezoneService->getBusinessDateRange($businessId, $startDate, $endDate);

        // Source data dari sales_transactions
        $sourceData = $this->getSourceSalesData($businessId, $dateRange['start_utc'], $dateRange['end_utc']);

        // OLAP data dari fact_sales
        $olapData = $this->getOlapSalesData($businessId, $dateRange['start_utc'], $dateRange['end_utc']);

        return $this->compareDataSets($sourceData, $olapData, 'sales', $businessId);
    }

    /**
     * Reconcile customer data
     */
    public function reconcileCustomerData(int $businessId, $startDate = null, $endDate = null): array
    {
        $dateRange = $this->timezoneService->getBusinessDateRange($businessId, $startDate, $endDate);

        // Source data dari customers table
        $sourceData = $this->getSourceCustomerData($businessId, $dateRange['start_utc'], $dateRange['end_utc']);

        // OLAP data dari dim_customer
        $olapData = $this->getOlapCustomerData($businessId, $dateRange['start_utc'], $dateRange['end_utc']);

        return $this->compareDataSets($sourceData, $olapData, 'customers', $businessId);
    }

    /**
     * Comprehensive reconciliation untuk semua data
     */
    public function comprehensiveReconciliation(int $businessId, $startDate = null, $endDate = null): array
    {
        $results = [
            'business_id' => $businessId,
            'reconciliation_timestamp' => Carbon::now(),
            'date_range' => $this->timezoneService->getBusinessDateRange($businessId, $startDate, $endDate),
            'reconciliations' => [],
            'overall_status' => 'unknown',
            'critical_discrepancies' => [],
            'recommendations' => [],
        ];

        // Reconcile different data types
        $reconciliationTypes = [
            'sales' => 'reconcileSalesData',
            'customers' => 'reconcileCustomerData',
            'products' => 'reconcileProductData',
            'metrics' => 'reconcileMetricData',
        ];

        $totalDiscrepancies = 0;
        $criticalDiscrepancies = 0;

        foreach ($reconciliationTypes as $type => $method) {
            try {
                $reconciliation = $this->$method($businessId, $startDate, $endDate);
                $results['reconciliations'][$type] = $reconciliation;

                $totalDiscrepancies += count($reconciliation['discrepancies']);

                // Check for critical discrepancies
                foreach ($reconciliation['discrepancies'] as $discrepancy) {
                    if ($discrepancy['severity'] === 'critical') {
                        $criticalDiscrepancies++;
                        $results['critical_discrepancies'][] = [
                            'type' => $type,
                            'discrepancy' => $discrepancy,
                        ];
                    }
                }

            } catch (\Exception $e) {
                $results['reconciliations'][$type] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'discrepancies' => [],
                ];

                Log::error("Reconciliation error for {$type}", [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Determine overall status
        $results['overall_status'] = $this->determineOverallStatus($criticalDiscrepancies, $totalDiscrepancies);
        $results['recommendations'] = $this->generateReconciliationRecommendations($results);

        // Log reconciliation results
        $this->logReconciliationResults($results);

        return $results;
    }

    /**
     * Get source sales data
     */
    private function getSourceSalesData(int $businessId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('sales_transactions as st')
            ->join('sales_transaction_items as sti', 'st.id', '=', 'sti.sales_transaction_id')
            ->where('st.business_id', $businessId)
            ->whereBetween('st.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                DATE(st.transaction_date) as date,
                COUNT(DISTINCT st.id) as transaction_count,
                COUNT(sti.id) as item_count,
                SUM(sti.quantity * sti.unit_price) as total_revenue,
                SUM(sti.quantity * COALESCE(sti.unit_cost, 0)) as total_cogs,
                COUNT(DISTINCT st.customer_id) as unique_customers
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->keyBy('date')->toArray();
    }

    /**
     * Get OLAP sales data
     */
    private function getOlapSalesData(int $businessId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('fact_sales as fs')
            ->join('dim_date as dd', 'fs.date_id', '=', 'dd.id')
            ->where('fs.business_id', $businessId)
            ->whereBetween('dd.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('
                dd.date,
                COUNT(DISTINCT fs.sales_transaction_id) as transaction_count,
                COUNT(fs.id) as item_count,
                SUM(fs.gross_revenue) as total_revenue,
                SUM(fs.cogs_amount) as total_cogs,
                COUNT(DISTINCT fs.customer_id) as unique_customers
            ')
            ->groupBy('dd.date')
            ->orderBy('dd.date')
            ->get();

        return $data->keyBy('date')->toArray();
    }

    /**
     * Get source customer data
     */
    private function getSourceCustomerData(int $businessId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('customers')
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as new_customers,
                SUM(CASE WHEN total_orders > 1 THEN 1 ELSE 0 END) as returning_customers
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->keyBy('date')->toArray();
    }

    /**
     * Get OLAP customer data
     */
    private function getOlapCustomerData(int $businessId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('dim_customer as dc')
            ->join('dim_date as dd', 'dc.first_order_date_id', '=', 'dd.id')
            ->where('dc.business_id', $businessId)
            ->whereBetween('dd.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('
                dd.date,
                COUNT(*) as new_customers,
                SUM(CASE WHEN dc.total_orders > 1 THEN 1 ELSE 0 END) as returning_customers
            ')
            ->groupBy('dd.date')
            ->orderBy('dd.date')
            ->get();

        return $data->keyBy('date')->toArray();
    }

    /**
     * Reconcile product data
     */
    private function reconcileProductData(int $businessId, $startDate = null, $endDate = null): array
    {
        // Source products
        $sourceCount = DB::table('products')
            ->where('business_id', $businessId)
            ->count();

        // OLAP products
        $olapCount = DB::table('dim_product')
            ->where('business_id', $businessId)
            ->count();

        $discrepancies = [];

        if ($sourceCount !== $olapCount) {
            $discrepancies[] = [
                'field' => 'product_count',
                'source_value' => $sourceCount,
                'olap_value' => $olapCount,
                'difference' => abs($sourceCount - $olapCount),
                'percentage_diff' => $this->safeMath::safePercentage(abs($sourceCount - $olapCount), max($sourceCount, 1)),
                'severity' => $this->determineSeverity(abs($sourceCount - $olapCount), max($sourceCount, 1)),
            ];
        }

        return [
            'status' => empty($discrepancies) ? 'match' : 'discrepancy',
            'discrepancies' => $discrepancies,
            'source_count' => $sourceCount,
            'olap_count' => $olapCount,
        ];
    }

    /**
     * Reconcile metric calculations
     */
    private function reconcileMetricData(int $businessId, $startDate = null, $endDate = null): array
    {
        $dateRange = $this->timezoneService->getBusinessDateRange($businessId, $startDate, $endDate);

        // Calculate metrics from both sources
        $sourceMetrics = $this->calculateSourceMetrics($businessId, $dateRange['start_utc'], $dateRange['end_utc']);
        $olapMetrics = $this->calculateOlapMetrics($businessId, $dateRange['start_utc'], $dateRange['end_utc']);

        return $this->compareMetrics($sourceMetrics, $olapMetrics, $businessId);
    }

    /**
     * Compare two datasets untuk discrepancies
     */
    private function compareDataSets(array $sourceData, array $olapData, string $dataType, int $businessId): array
    {
        $discrepancies = [];
        $allDates = array_unique(array_merge(array_keys($sourceData), array_keys($olapData)));

        foreach ($allDates as $date) {
            $source = (array) ($sourceData[$date] ?? []);
            $olap = (array) ($olapData[$date] ?? []);

            foreach (['transaction_count', 'total_revenue', 'total_cogs', 'unique_customers'] as $field) {
                $sourceValue = $this->safeMath::nullToZero($source[$field] ?? 0);
                $olapValue = $this->safeMath::nullToZero($olap[$field] ?? 0);

                if ($sourceValue != $olapValue) {
                    $difference = abs($sourceValue - $olapValue);
                    $percentageDiff = $this->safeMath::safePercentage($difference, max($sourceValue, 1));

                    $discrepancies[] = [
                        'date' => $date,
                        'field' => $field,
                        'source_value' => $sourceValue,
                        'olap_value' => $olapValue,
                        'difference' => $difference,
                        'percentage_diff' => $percentageDiff,
                        'severity' => $this->determineSeverity($difference, max($sourceValue, $olapValue)),
                    ];
                }
            }
        }

        return [
            'status' => empty($discrepancies) ? 'match' : 'discrepancy',
            'discrepancies' => $discrepancies,
            'total_discrepancies' => count($discrepancies),
            'data_type' => $dataType,
        ];
    }

    /**
     * Compare calculated metrics
     */
    private function compareMetrics(array $sourceMetrics, array $olapMetrics, int $businessId): array
    {
        $discrepancies = [];
        $allMetrics = array_unique(array_merge(array_keys($sourceMetrics), array_keys($olapMetrics)));

        foreach ($allMetrics as $metricName) {
            $sourceValue = $this->safeMath::nullToZero($sourceMetrics[$metricName] ?? 0);
            $olapValue = $this->safeMath::nullToZero($olapMetrics[$metricName] ?? 0);

            $tolerance = $this->getMetricTolerance($metricName);
            $difference = abs($sourceValue - $olapValue);

            if ($difference > $tolerance) {
                $percentageDiff = $this->safeMath::safePercentage($difference, max($sourceValue, 1));

                $discrepancies[] = [
                    'metric' => $metricName,
                    'source_value' => $sourceValue,
                    'olap_value' => $olapValue,
                    'difference' => $difference,
                    'percentage_diff' => $percentageDiff,
                    'tolerance' => $tolerance,
                    'severity' => $this->determineSeverity($difference, max($sourceValue, $olapValue)),
                ];
            }
        }

        return [
            'status' => empty($discrepancies) ? 'match' : 'discrepancy',
            'discrepancies' => $discrepancies,
            'total_discrepancies' => count($discrepancies),
        ];
    }

    /**
     * Calculate metrics dari source data
     */
    private function calculateSourceMetrics(int $businessId, Carbon $startDate, Carbon $endDate): array
    {
        $salesData = DB::table('sales_transactions as st')
            ->join('sales_transaction_items as sti', 'st.id', '=', 'sti.sales_transaction_id')
            ->where('st.business_id', $businessId)
            ->whereBetween('st.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(sti.quantity * sti.unit_price) as total_revenue,
                SUM(sti.quantity * COALESCE(sti.unit_cost, 0)) as total_cogs,
                COUNT(DISTINCT st.customer_id) as unique_customers,
                COUNT(DISTINCT st.id) as total_transactions
            ')
            ->first();

        $revenue = $this->safeMath::nullToZero($salesData->total_revenue);
        $cogs = $this->safeMath::nullToZero($salesData->total_cogs);

        return [
            'total_revenue' => $revenue,
            'total_cogs' => $cogs,
            'gross_margin' => $this->safeMath::safeMargin($revenue, $cogs),
            'unique_customers' => $this->safeMath::nullToZero($salesData->unique_customers),
            'total_transactions' => $this->safeMath::nullToZero($salesData->total_transactions),
            'avg_transaction_value' => $this->safeMath::safeDivision($revenue, $salesData->total_transactions),
        ];
    }

    /**
     * Calculate metrics dari OLAP data
     */
    private function calculateOlapMetrics(int $businessId, Carbon $startDate, Carbon $endDate): array
    {
        $olapData = DB::table('fact_sales as fs')
            ->join('dim_date as dd', 'fs.date_id', '=', 'dd.id')
            ->where('fs.business_id', $businessId)
            ->whereBetween('dd.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('
                SUM(fs.gross_revenue) as total_revenue,
                SUM(fs.cogs_amount) as total_cogs,
                COUNT(DISTINCT fs.customer_id) as unique_customers,
                COUNT(DISTINCT fs.sales_transaction_id) as total_transactions
            ')
            ->first();

        $revenue = $this->safeMath::nullToZero($olapData->total_revenue);
        $cogs = $this->safeMath::nullToZero($olapData->total_cogs);

        return [
            'total_revenue' => $revenue,
            'total_cogs' => $cogs,
            'gross_margin' => $this->safeMath::safeMargin($revenue, $cogs),
            'unique_customers' => $this->safeMath::nullToZero($olapData->unique_customers),
            'total_transactions' => $this->safeMath::nullToZero($olapData->total_transactions),
            'avg_transaction_value' => $this->safeMath::safeDivision($revenue, $olapData->total_transactions),
        ];
    }

    /**
     * Determine severity berdasarkan difference dan percentage
     */
    private function determineSeverity(float $difference, float $baseValue): string
    {
        $percentageDiff = $this->safeMath::safePercentage($difference, $baseValue);

        if ($percentageDiff >= 10) {
            return 'critical';
        } elseif ($percentageDiff >= 5) {
            return 'high';
        } elseif ($percentageDiff >= 1) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get tolerance untuk specific metric
     */
    private function getMetricTolerance(string $metricName): float
    {
        $tolerances = [
            'total_revenue' => 0.01, // 1 cent tolerance
            'total_cogs' => 0.01,
            'gross_margin' => 0.1, // 0.1% tolerance for percentages
            'unique_customers' => 0, // No tolerance for counts
            'total_transactions' => 0,
            'avg_transaction_value' => 0.01,
        ];

        return $tolerances[$metricName] ?? 0.01;
    }

    /**
     * Determine overall status dari reconciliation
     */
    private function determineOverallStatus(int $criticalDiscrepancies, int $totalDiscrepancies): string
    {
        if ($criticalDiscrepancies > 0) {
            return 'critical';
        } elseif ($totalDiscrepancies > 10) {
            return 'needs_attention';
        } elseif ($totalDiscrepancies > 0) {
            return 'minor_issues';
        } else {
            return 'healthy';
        }
    }

    /**
     * Generate recommendations berdasarkan reconciliation results
     */
    private function generateReconciliationRecommendations(array $results): array
    {
        $recommendations = [];

        if ($results['overall_status'] === 'critical') {
            $recommendations[] = 'Immediate data reconciliation required - critical discrepancies detected';
            $recommendations[] = 'Review data pipeline and ETL processes';
            $recommendations[] = 'Consider halting automated reporting until issues are resolved';
        }

        if (count($results['critical_discrepancies']) > 0) {
            $recommendations[] = 'Focus on resolving critical discrepancies first';
            $recommendations[] = 'Investigate root causes for data mismatches';
        }

        foreach ($results['reconciliations'] as $type => $reconciliation) {
            if ($reconciliation['status'] === 'discrepancy') {
                $recommendations[] = "Review {$type} data synchronization process";
            }
        }

        return $recommendations;
    }

    /**
     * Log reconciliation results
     */
    private function logReconciliationResults(array $results): void
    {
        $logLevel = match($results['overall_status']) {
            'critical' => 'error',
            'needs_attention' => 'warning',
            'minor_issues' => 'info',
            default => 'debug',
        };

        Log::log($logLevel, 'Data reconciliation completed', [
            'business_id' => $results['business_id'],
            'overall_status' => $results['overall_status'],
            'critical_discrepancies' => count($results['critical_discrepancies']),
            'total_reconciliations' => count($results['reconciliations']),
        ]);
    }

    /**
     * Schedule automated reconciliation
     */
    public function scheduleReconciliation(int $businessId, string $frequency = 'daily'): void
    {
        // Store reconciliation schedule
        DB::table('reconciliation_schedules')->updateOrInsert(
            ['business_id' => $businessId],
            [
                'business_id' => $businessId,
                'frequency' => $frequency,
                'is_active' => true,
                'last_run' => null,
                'next_run' => $this->calculateNextRun($frequency),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Calculate next run time berdasarkan frequency
     */
    private function calculateNextRun(string $frequency): Carbon
    {
        return match($frequency) {
            'hourly' => now()->addHour(),
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay(),
        };
    }
}
