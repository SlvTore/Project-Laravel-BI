<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service untuk validasi metrik dengan deteksi outliers dan anomali data
 * Memastikan akurasi dan konsistensi data metrics/KPI
 */
class MetricValidatorService
{
    private array $validationRules = [];
    private array $businessTypeRules = [];

    public function __construct()
    {
        $this->initializeValidationRules();
        $this->initializeBusinessTypeRules();
    }

    /**
     * Validasi metrik berdasarkan business rules dan deteksi anomali
     */
    public function validateMetrics(array $metrics, string $businessType = 'general'): array
    {
        $validatedMetrics = [];
        $warnings = [];
        $errors = [];

        foreach ($metrics as $metricName => $value) {
            $validation = $this->validateSingleMetric($metricName, $value, $businessType);

            $validatedMetrics[$metricName] = $validation['value'];

            if (!empty($validation['warnings'])) {
                $warnings[$metricName] = $validation['warnings'];
            }

            if (!empty($validation['errors'])) {
                $errors[$metricName] = $validation['errors'];
            }
        }

        return [
            'metrics' => $validatedMetrics,
            'warnings' => $warnings,
            'errors' => $errors,
            'validation_timestamp' => Carbon::now(),
        ];
    }

    /**
     * Validasi satu metrik terhadap business rules
     */
    private function validateSingleMetric(string $metricName, $value, string $businessType): array
    {
        $warnings = [];
        $errors = [];
        $validatedValue = $value;

        // Handle NULL dan nilai kosong
        if (is_null($value) || $value === '') {
            $warnings[] = "Null or empty value detected for {$metricName}";
            $validatedValue = 0;
        }

        // Validasi numerik
        if (!is_numeric($validatedValue)) {
            $errors[] = "Non-numeric value for {$metricName}: {$value}";
            $validatedValue = 0;
        }

        // Validasi range berdasarkan jenis metrik
        $rangeValidation = $this->validateRange($metricName, $validatedValue, $businessType);
        if (!$rangeValidation['valid']) {
            $warnings = array_merge($warnings, $rangeValidation['warnings']);
            $errors = array_merge($errors, $rangeValidation['errors']);
        }

        // Deteksi outliers
        $outlierValidation = $this->detectOutliers($metricName, $validatedValue, $businessType);
        if (!$outlierValidation['valid']) {
            $warnings = array_merge($warnings, $outlierValidation['warnings']);
        }

        return [
            'value' => $validatedValue,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * Validasi range nilai berdasarkan jenis metrik dan business type
     */
    private function validateRange(string $metricName, $value, string $businessType): array
    {
        $warnings = [];
        $errors = [];
        $valid = true;

        $rules = $this->getMetricRules($metricName, $businessType);

        if (isset($rules['min']) && $value < $rules['min']) {
            $errors[] = "{$metricName} value {$value} is below minimum {$rules['min']}";
            $valid = false;
        }

        if (isset($rules['max']) && $value > $rules['max']) {
            $warnings[] = "{$metricName} value {$value} is above expected maximum {$rules['max']}";
        }

        // Validasi khusus untuk persentase
        if (strpos(strtolower($metricName), 'rate') !== false ||
            strpos(strtolower($metricName), 'percentage') !== false ||
            strpos(strtolower($metricName), 'margin') !== false) {

            if ($value < 0) {
                $warnings[] = "Negative percentage/rate detected for {$metricName}: {$value}%";
            }

            if ($value > 100 && !in_array($metricName, ['growth_rate', 'roi'])) {
                $warnings[] = "Percentage above 100% for {$metricName}: {$value}%";
            }
        }

        return [
            'valid' => $valid,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * Deteksi outliers menggunakan statistical methods
     */
    private function detectOutliers(string $metricName, $value, string $businessType): array
    {
        $warnings = [];
        $valid = true;

        // Ambil historical data untuk perbandingan
        $historicalData = $this->getHistoricalData($metricName, $businessType);

        if (count($historicalData) >= 5) {
            $stats = $this->calculateStatistics($historicalData);

            // Z-Score method untuk deteksi outliers
            $zScore = abs(($value - $stats['mean']) / $stats['stdDev']);

            if ($zScore > 3) {
                $warnings[] = "Potential outlier detected for {$metricName}. Value: {$value}, Z-Score: " . round($zScore, 2);
            }

            // IQR method
            $iqr = $stats['q3'] - $stats['q1'];
            $lowerBound = $stats['q1'] - (1.5 * $iqr);
            $upperBound = $stats['q3'] + (1.5 * $iqr);

            if ($value < $lowerBound || $value > $upperBound) {
                $warnings[] = "Value outside IQR bounds for {$metricName}. Expected range: {$lowerBound} - {$upperBound}";
            }
        }

        return [
            'valid' => $valid,
            'warnings' => $warnings,
        ];
    }

    /**
     * Kalkulasi statistik untuk historical data
     */
    private function calculateStatistics(array $data): array
    {
        sort($data);
        $count = count($data);

        $mean = array_sum($data) / $count;

        $variance = 0;
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance = $variance / $count;
        $stdDev = sqrt($variance);

        $q1Index = floor($count * 0.25);
        $q3Index = floor($count * 0.75);

        return [
            'mean' => $mean,
            'stdDev' => $stdDev,
            'q1' => $data[$q1Index],
            'q3' => $data[$q3Index],
            'min' => min($data),
            'max' => max($data),
        ];
    }

    /**
     * Ambil historical data untuk metrik tertentu
     */
    private function getHistoricalData(string $metricName, string $businessType): array
    {
        // Implementasi query untuk ambil data historis
        // Untuk sekarang return sample data
        return [];
    }

    /**
     * Ambil rules untuk metrik berdasarkan business type
     */
    private function getMetricRules(string $metricName, string $businessType): array
    {
        $generalRules = $this->validationRules[$metricName] ?? [];
        $businessRules = $this->businessTypeRules[$businessType][$metricName] ?? [];

        return array_merge($generalRules, $businessRules);
    }

    /**
     * Initialize validation rules untuk setiap metrik
     */
    private function initializeValidationRules(): void
    {
        $this->validationRules = [
            'total_revenue' => [
                'min' => 0,
                'type' => 'currency',
            ],
            'total_cogs' => [
                'min' => 0,
                'type' => 'currency',
            ],
            'gross_margin' => [
                'min' => -100,
                'max' => 100,
                'type' => 'percentage',
            ],
            'customer_retention_rate' => [
                'min' => 0,
                'max' => 100,
                'type' => 'percentage',
            ],
            'churn_rate' => [
                'min' => 0,
                'max' => 100,
                'type' => 'percentage',
            ],
            'growth_rate' => [
                'min' => -100,
                'type' => 'percentage',
            ],
            'customer_count' => [
                'min' => 0,
                'type' => 'integer',
            ],
        ];
    }

    /**
     * Initialize business type specific rules
     */
    private function initializeBusinessTypeRules(): void
    {
        $this->businessTypeRules = [
            'ecommerce' => [
                'churn_rate' => ['max' => 30], // E-commerce typically has lower churn
                'gross_margin' => ['min' => 10, 'max' => 80],
            ],
            'saas' => [
                'churn_rate' => ['max' => 10], // SaaS should have very low churn
                'customer_retention_rate' => ['min' => 85],
            ],
            'retail' => [
                'gross_margin' => ['min' => 20, 'max' => 60],
                'churn_rate' => ['max' => 50],
            ],
            'manufacturing' => [
                'gross_margin' => ['min' => 15, 'max' => 45],
            ],
        ];
    }

    /**
     * Cross-validate metrics untuk konsistensi logis
     */
    public function crossValidateMetrics(array $metrics): array
    {
        $warnings = [];

        // Revenue vs COGS relationship
        if (isset($metrics['total_revenue']) && isset($metrics['total_cogs'])) {
            if ($metrics['total_cogs'] > $metrics['total_revenue']) {
                $warnings[] = "COGS ({$metrics['total_cogs']}) exceeds Revenue ({$metrics['total_revenue']})";
            }
        }

        // Margin consistency check
        if (isset($metrics['total_revenue']) && isset($metrics['total_cogs']) && isset($metrics['gross_margin'])) {
            $calculatedMargin = $metrics['total_revenue'] > 0
                ? (($metrics['total_revenue'] - $metrics['total_cogs']) / $metrics['total_revenue']) * 100
                : 0;

            $marginDifference = abs($calculatedMargin - $metrics['gross_margin']);
            if ($marginDifference > 1) { // Allow 1% tolerance
                $warnings[] = "Margin calculation mismatch. Calculated: {$calculatedMargin}%, Reported: {$metrics['gross_margin']}%";
            }
        }

        // Customer metrics relationship
        if (isset($metrics['new_customers']) && isset($metrics['total_customers'])) {
            if ($metrics['new_customers'] > $metrics['total_customers']) {
                $warnings[] = "New customers ({$metrics['new_customers']}) exceeds total customers ({$metrics['total_customers']})";
            }
        }

        return $warnings;
    }

    /**
     * Generate validation report
     */
    public function generateValidationReport(array $validationResult): string
    {
        $report = "=== METRIC VALIDATION REPORT ===\n";
        $report .= "Timestamp: " . $validationResult['validation_timestamp'] . "\n\n";

        if (!empty($validationResult['errors'])) {
            $report .= "ERRORS:\n";
            foreach ($validationResult['errors'] as $metric => $errors) {
                $report .= "- {$metric}: " . implode(', ', $errors) . "\n";
            }
            $report .= "\n";
        }

        if (!empty($validationResult['warnings'])) {
            $report .= "WARNINGS:\n";
            foreach ($validationResult['warnings'] as $metric => $warnings) {
                $report .= "- {$metric}: " . implode(', ', $warnings) . "\n";
            }
            $report .= "\n";
        }

        $report .= "VALIDATED METRICS:\n";
        foreach ($validationResult['metrics'] as $metric => $value) {
            $report .= "- {$metric}: {$value}\n";
        }

        return $report;
    }

    /**
     * Log validation results
     */
    public function logValidationResults(array $validationResult, string $context = ''): void
    {
        if (!empty($validationResult['errors'])) {
            Log::error('Metric validation errors', [
                'context' => $context,
                'errors' => $validationResult['errors'],
                'timestamp' => $validationResult['validation_timestamp'],
            ]);
        }

        if (!empty($validationResult['warnings'])) {
            Log::warning('Metric validation warnings', [
                'context' => $context,
                'warnings' => $validationResult['warnings'],
                'timestamp' => $validationResult['validation_timestamp'],
            ]);
        }
    }
}
