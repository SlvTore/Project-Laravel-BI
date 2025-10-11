<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service untuk tracking dan monitoring data freshness di sistem OLAP
 * Memberikan visibility tentang recency dan reliability data
 */
class DataFreshnessService
{
    private array $dataSourceTypes = [
        'fact_sales',
        'dim_customer',
        'dim_product',
        'dim_date',
        'customer_cohorts',
        'churn_snapshots',
        'olap_cubes',
    ];

    /**
     * Track data freshness untuk data source tertentu
     */
    public function trackDataFreshness(string $dataSource, int $businessId, string $operation = 'update'): void
    {
        DB::table('data_freshness_log')->updateOrInsert(
            [
                'data_source' => $dataSource,
                'business_id' => $businessId,
            ],
            [
                'data_source' => $dataSource,
                'business_id' => $businessId,
                'last_updated' => now(),
                'last_operation' => $operation,
                'record_count' => $this->getRecordCount($dataSource, $businessId),
                'updated_at' => now(),
            ]
        );

        // Clear freshness cache
        Cache::forget("data_freshness_{$dataSource}_{$businessId}");
    }

    /**
     * Ambil data freshness untuk semua data sources
     */
    public function getAllDataFreshness(int $businessId): array
    {
        return Cache::remember("all_data_freshness_{$businessId}", 300, function() use ($businessId) {
            $freshness = [];

            foreach ($this->dataSourceTypes as $dataSource) {
                $freshness[$dataSource] = $this->getDataFreshness($dataSource, $businessId);
            }

            return $freshness;
        });
    }

    /**
     * Ambil data freshness untuk specific data source
     */
    public function getDataFreshness(string $dataSource, int $businessId): array
    {
        return Cache::remember("data_freshness_{$dataSource}_{$businessId}", 300, function() use ($dataSource, $businessId) {
            $log = DB::table('data_freshness_log')
                ->where('data_source', $dataSource)
                ->where('business_id', $businessId)
                ->first();

            if (!$log) {
                return [
                    'status' => 'unknown',
                    'last_updated' => null,
                    'hours_since_update' => null,
                    'record_count' => 0,
                    'color' => 'gray',
                    'message' => 'No data available',
                ];
            }

            $lastUpdated = Carbon::parse($log->last_updated);
            $hoursSinceUpdate = $lastUpdated->diffInHours(now());

            return [
                'status' => $this->determineFreshnessStatus($dataSource, $hoursSinceUpdate),
                'last_updated' => $lastUpdated,
                'hours_since_update' => $hoursSinceUpdate,
                'record_count' => $log->record_count,
                'last_operation' => $log->last_operation,
                'color' => $this->getFreshnessColor($dataSource, $hoursSinceUpdate),
                'message' => $this->getFreshnessMessage($dataSource, $hoursSinceUpdate),
            ];
        });
    }

    /**
     * Determine freshness status berdasarkan data source dan time elapsed
     */
    private function determineFreshnessStatus(string $dataSource, int $hoursSinceUpdate): string
    {
        $thresholds = $this->getFreshnessThresholds($dataSource);

        if ($hoursSinceUpdate <= $thresholds['fresh']) {
            return 'fresh';
        } elseif ($hoursSinceUpdate <= $thresholds['acceptable']) {
            return 'acceptable';
        } elseif ($hoursSinceUpdate <= $thresholds['stale']) {
            return 'stale';
        } else {
            return 'very_stale';
        }
    }

    /**
     * Ambil color indicator untuk freshness status
     */
    private function getFreshnessColor(string $dataSource, int $hoursSinceUpdate): string
    {
        $status = $this->determineFreshnessStatus($dataSource, $hoursSinceUpdate);

        return match($status) {
            'fresh' => 'green',
            'acceptable' => 'blue',
            'stale' => 'yellow',
            'very_stale' => 'red',
            default => 'gray',
        };
    }

    /**
     * Generate freshness message
     */
    private function getFreshnessMessage(string $dataSource, int $hoursSinceUpdate): string
    {
        $status = $this->determineFreshnessStatus($dataSource, $hoursSinceUpdate);

        return match($status) {
            'fresh' => "Data is up-to-date ({$hoursSinceUpdate}h ago)",
            'acceptable' => "Data is recent ({$hoursSinceUpdate}h ago)",
            'stale' => "Data needs refresh ({$hoursSinceUpdate}h ago)",
            'very_stale' => "Data is very outdated ({$hoursSinceUpdate}h ago)",
            default => "Unknown data status",
        };
    }

    /**
     * Ambil freshness thresholds untuk setiap data source
     */
    private function getFreshnessThresholds(string $dataSource): array
    {
        $thresholds = [
            'fact_sales' => ['fresh' => 2, 'acceptable' => 6, 'stale' => 24],
            'dim_customer' => ['fresh' => 12, 'acceptable' => 24, 'stale' => 72],
            'dim_product' => ['fresh' => 24, 'acceptable' => 48, 'stale' => 168],
            'dim_date' => ['fresh' => 168, 'acceptable' => 336, 'stale' => 720], // Weekly, bi-weekly, monthly
            'customer_cohorts' => ['fresh' => 24, 'acceptable' => 48, 'stale' => 168],
            'churn_snapshots' => ['fresh' => 12, 'acceptable' => 24, 'stale' => 72],
            'olap_cubes' => ['fresh' => 6, 'acceptable' => 12, 'stale' => 48],
        ];

        return $thresholds[$dataSource] ?? ['fresh' => 6, 'acceptable' => 24, 'stale' => 72];
    }

    /**
     * Ambil record count untuk data source
     */
    private function getRecordCount(string $dataSource, int $businessId): int
    {
        try {
            switch ($dataSource) {
                case 'fact_sales':
                    return DB::table('fact_sales')->where('business_id', $businessId)->count();

                case 'dim_customer':
                    return DB::table('dim_customer')->where('business_id', $businessId)->count();

                case 'dim_product':
                    return DB::table('dim_product')->where('business_id', $businessId)->count();

                case 'dim_date':
                    return DB::table('dim_date')->count();

                case 'customer_cohorts':
                    return DB::table('customer_cohorts')->where('business_id', $businessId)->count();

                case 'churn_snapshots':
                    return DB::table('churn_snapshots')->where('business_id', $businessId)->count();

                case 'olap_cubes':
                    return DB::table('olap_cubes')->where('business_id', $businessId)->count();

                default:
                    return 0;
            }
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Generate overall data health score
     */
    public function getDataHealthScore(int $businessId): array
    {
        $allFreshness = $this->getAllDataFreshness($businessId);
        $totalSources = count($allFreshness);

        if ($totalSources === 0) {
            return [
                'score' => 0,
                'grade' => 'F',
                'color' => 'red',
                'message' => 'No data sources tracked',
            ];
        }

        $scores = [];
        foreach ($allFreshness as $source => $freshness) {
            $scores[] = match($freshness['status']) {
                'fresh' => 100,
                'acceptable' => 80,
                'stale' => 50,
                'very_stale' => 20,
                default => 0,
            };
        }

        $averageScore = array_sum($scores) / count($scores);

        return [
            'score' => round($averageScore, 1),
            'grade' => $this->getHealthGrade($averageScore),
            'color' => $this->getHealthColor($averageScore),
            'message' => $this->getHealthMessage($averageScore),
            'breakdown' => $allFreshness,
        ];
    }

    /**
     * Convert score to letter grade
     */
    private function getHealthGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Get color untuk health score
     */
    private function getHealthColor(float $score): string
    {
        if ($score >= 80) return 'green';
        if ($score >= 60) return 'yellow';
        return 'red';
    }

    /**
     * Generate health message
     */
    private function getHealthMessage(float $score): string
    {
        if ($score >= 90) return 'Excellent data freshness';
        if ($score >= 80) return 'Good data freshness';
        if ($score >= 70) return 'Acceptable data freshness';
        if ($score >= 60) return 'Data needs attention';
        return 'Critical data freshness issues';
    }

    /**
     * Trigger refresh untuk stale data sources
     */
    public function triggerStaleDataRefresh(int $businessId): array
    {
        $allFreshness = $this->getAllDataFreshness($businessId);
        $refreshedSources = [];

        foreach ($allFreshness as $source => $freshness) {
            if (in_array($freshness['status'], ['stale', 'very_stale'])) {
                $this->triggerDataSourceRefresh($source, $businessId);
                $refreshedSources[] = $source;
            }
        }

        return $refreshedSources;
    }

    /**
     * Trigger refresh untuk specific data source
     */
    private function triggerDataSourceRefresh(string $dataSource, int $businessId): void
    {
        // Queue refresh job berdasarkan data source type
        switch ($dataSource) {
            case 'fact_sales':
                // Trigger sales data refresh
                break;

            case 'customer_cohorts':
                // Trigger cohort analysis refresh
                break;

            case 'churn_snapshots':
                // Trigger churn analysis refresh
                break;

            case 'olap_cubes':
                // Trigger OLAP cube refresh
                break;
        }
    }

    /**
     * Generate freshness report untuk dashboard
     */
    public function generateFreshnessReport(int $businessId): array
    {
        $healthScore = $this->getDataHealthScore($businessId);
        $allFreshness = $this->getAllDataFreshness($businessId);

        $report = [
            'overall_health' => $healthScore,
            'data_sources' => [],
            'recommendations' => [],
            'last_updated' => now(),
        ];

        foreach ($allFreshness as $source => $freshness) {
            $report['data_sources'][] = [
                'name' => $this->getDataSourceDisplayName($source),
                'status' => $freshness['status'],
                'last_updated' => $freshness['last_updated'],
                'hours_ago' => $freshness['hours_since_update'],
                'color' => $freshness['color'],
                'message' => $freshness['message'],
                'record_count' => number_format($freshness['record_count']),
            ];

            // Generate recommendations
            if ($freshness['status'] === 'very_stale') {
                $report['recommendations'][] = "Immediate refresh needed for {$this->getDataSourceDisplayName($source)}";
            } elseif ($freshness['status'] === 'stale') {
                $report['recommendations'][] = "Consider refreshing {$this->getDataSourceDisplayName($source)} data";
            }
        }

        return $report;
    }

    /**
     * Get display name untuk data source
     */
    private function getDataSourceDisplayName(string $source): string
    {
        $displayNames = [
            'fact_sales' => 'Sales Data',
            'dim_customer' => 'Customer Dimensions',
            'dim_product' => 'Product Dimensions',
            'dim_date' => 'Date Dimensions',
            'customer_cohorts' => 'Customer Cohorts',
            'churn_snapshots' => 'Churn Analysis',
            'olap_cubes' => 'OLAP Cubes',
        ];

        return $displayNames[$source] ?? ucwords(str_replace('_', ' ', $source));
    }

    /**
     * Setup automated freshness monitoring
     */
    public function setupAutomatedMonitoring(int $businessId, array $alertThresholds = []): void
    {
        // Store monitoring configuration
        DB::table('data_freshness_monitoring')->updateOrInsert(
            ['business_id' => $businessId],
            [
                'business_id' => $businessId,
                'alert_thresholds' => json_encode($alertThresholds),
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
    }
}
