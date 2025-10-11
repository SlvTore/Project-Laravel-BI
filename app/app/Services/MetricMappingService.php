<?php

namespace App\Services;

/**
 * Centralized service for mapping metric names to OLAP views and columns
 * Eliminates duplicate mapping logic across controllers
 */
class MetricMappingService
{
    // Metric name constants
    const METRIC_REVENUE = 'Total Penjualan';
    const METRIC_COGS = 'Biaya Pokok Penjualan (COGS)';
    const METRIC_GROSS_MARGIN = 'Gross Margin';
    const METRIC_GROSS_MARGIN_PERCENT = 'Gross Margin Percentage';
    const METRIC_NEW_CUSTOMERS = 'Jumlah Pelanggan Baru';
    const METRIC_RETURNING_CUSTOMERS = 'Jumlah Pelanggan Setia';
    const METRIC_PRODUCT_SALES = 'Penjualan Per Produk';

    /**
     * Get OLAP view mapping for a metric
     *
     * @param string $metricName
     * @return array|null ['view' => string, 'column' => string, 'type' => 'sum'|'avg'|'count']
     */
    public static function getOlapMapping(string $metricName): ?array
    {
        return match($metricName) {
            self::METRIC_REVENUE => [
                'view' => 'vw_sales_daily',
                'column' => 'total_gross_revenue',
                'type' => 'sum',
                'aggregatable' => true,
            ],
            self::METRIC_COGS => [
                'view' => 'vw_cogs_daily',
                'column' => 'total_cogs',
                'type' => 'sum',
                'aggregatable' => true,
            ],
            self::METRIC_GROSS_MARGIN => [
                'view' => 'vw_margin_daily',
                'column' => 'total_gross_margin',
                'type' => 'sum',
                'aggregatable' => true,
            ],
            self::METRIC_GROSS_MARGIN_PERCENT => [
                'view' => 'vw_margin_daily',
                'column' => 'avg_margin_percent',
                'type' => 'avg',
                'aggregatable' => false, // Weighted average needed
            ],
            self::METRIC_NEW_CUSTOMERS => [
                'view' => 'vw_new_customers_daily',
                'column' => 'new_customers',
                'type' => 'sum',
                'aggregatable' => true,
            ],
            self::METRIC_RETURNING_CUSTOMERS => [
                'view' => 'vw_returning_customers_daily',
                'column' => 'returning_customers',
                'type' => 'sum',
                'aggregatable' => true,
            ],
            self::METRIC_PRODUCT_SALES => [
                'view' => 'vw_sales_product_daily',
                'column' => 'product_revenue',
                'type' => 'sum',
                'aggregatable' => true,
            ],
            default => null,
        };
    }

    /**
     * Get series name for chart rendering
     *
     * @param string $metricName
     * @return string
     */
    public static function getSeriesName(string $metricName): string
    {
        return match($metricName) {
            self::METRIC_REVENUE => 'revenue',
            self::METRIC_COGS => 'cogs',
            self::METRIC_GROSS_MARGIN => 'gross_margin',
            self::METRIC_GROSS_MARGIN_PERCENT => 'margin_percent',
            self::METRIC_NEW_CUSTOMERS => 'new_customers',
            self::METRIC_RETURNING_CUSTOMERS => 'returning_customers',
            self::METRIC_PRODUCT_SALES => 'product_sales',
            default => strtolower(str_replace([' ', '(', ')', '/'], ['_', '', '', '_'], $metricName)),
        };
    }

    /**
     * Get display format for metric value
     *
     * @param string $metricName
     * @return array ['type' => 'currency'|'number'|'percent', 'decimals' => int]
     */
    public static function getDisplayFormat(string $metricName): array
    {
        return match($metricName) {
            self::METRIC_REVENUE,
            self::METRIC_COGS,
            self::METRIC_GROSS_MARGIN => [
                'type' => 'currency',
                'decimals' => 0,
            ],
            self::METRIC_GROSS_MARGIN_PERCENT => [
                'type' => 'percent',
                'decimals' => 2,
            ],
            self::METRIC_NEW_CUSTOMERS,
            self::METRIC_RETURNING_CUSTOMERS => [
                'type' => 'number',
                'decimals' => 0,
            ],
            default => [
                'type' => 'number',
                'decimals' => 2,
            ],
        };
    }

    /**
     * Get all supported metrics
     *
     * @return array
     */
    public static function getAllMetrics(): array
    {
        return [
            self::METRIC_REVENUE,
            self::METRIC_COGS,
            self::METRIC_GROSS_MARGIN,
            self::METRIC_GROSS_MARGIN_PERCENT,
            self::METRIC_NEW_CUSTOMERS,
            self::METRIC_RETURNING_CUSTOMERS,
            self::METRIC_PRODUCT_SALES,
        ];
    }

    /**
     * Check if metric is supported in OLAP
     *
     * @param string $metricName
     * @return bool
     */
    public static function isOlapSupported(string $metricName): bool
    {
        return self::getOlapMapping($metricName) !== null;
    }

    /**
     * Get metric metadata
     *
     * @param string $metricName
     * @return array
     */
    public static function getMetricMetadata(string $metricName): array
    {
        $mapping = self::getOlapMapping($metricName);
        $format = self::getDisplayFormat($metricName);
        $series = self::getSeriesName($metricName);

        return [
            'name' => $metricName,
            'series_name' => $series,
            'olap_supported' => $mapping !== null,
            'view' => $mapping['view'] ?? null,
            'column' => $mapping['column'] ?? null,
            'aggregation_type' => $mapping['type'] ?? null,
            'display_format' => $format,
        ];
    }
}
