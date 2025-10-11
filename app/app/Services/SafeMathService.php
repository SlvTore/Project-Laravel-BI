<?php

namespace App\Services;

/**
 * Service untuk standardisasi penanganan NULL dan pembagian dengan nol
 * Memastikan konsistensi kalkulasi di seluruh sistem
 */
class SafeMathService
{
    /**
     * Safe division dengan penanganan null dan zero
     */
    public static function safeDivision($numerator, $denominator, $defaultValue = 0): float
    {
        // Handle null values
        $numerator = static::nullToZero($numerator);
        $denominator = static::nullToZero($denominator);

        // Handle division by zero
        if ($denominator == 0) {
            return (float) $defaultValue;
        }

        return (float) ($numerator / $denominator);
    }

    /**
     * Safe percentage calculation
     */
    public static function safePercentage($part, $total, $defaultValue = 0): float
    {
        $result = static::safeDivision($part, $total, $defaultValue) * 100;
        return round($result, 2);
    }

    /**
     * Safe growth rate calculation
     */
    public static function safeGrowthRate($current, $previous, $defaultValue = 0): float
    {
        $current = static::nullToZero($current);
        $previous = static::nullToZero($previous);

        if ($previous == 0) {
            // Jika previous adalah 0 dan current > 0, growth adalah 100%
            // Jika previous adalah 0 dan current = 0, growth adalah 0%
            return $current > 0 ? 100.0 : (float) $defaultValue;
        }

        $growthRate = (($current - $previous) / $previous) * 100;
        return round($growthRate, 2);
    }

    /**
     * Safe average calculation
     */
    public static function safeAverage(array $values, $defaultValue = 0): float
    {
        // Filter out null values
        $filteredValues = array_filter($values, function($value) {
            return !is_null($value);
        });

        // Convert nulls to zeros for remaining values
        $filteredValues = array_map([static::class, 'nullToZero'], $filteredValues);

        if (empty($filteredValues)) {
            return (float) $defaultValue;
        }

        $sum = array_sum($filteredValues);
        $count = count($filteredValues);

        return static::safeDivision($sum, $count, $defaultValue);
    }

    /**
     * Safe median calculation
     */
    public static function safeMedian(array $values, $defaultValue = 0): float
    {
        // Filter and clean values
        $filteredValues = array_filter($values, function($value) {
            return !is_null($value);
        });

        $filteredValues = array_map([static::class, 'nullToZero'], $filteredValues);

        if (empty($filteredValues)) {
            return (float) $defaultValue;
        }

        sort($filteredValues);
        $count = count($filteredValues);

        if ($count % 2 == 0) {
            // Even number of values
            $mid1 = $filteredValues[$count / 2 - 1];
            $mid2 = $filteredValues[$count / 2];
            return ($mid1 + $mid2) / 2;
        } else {
            // Odd number of values
            return $filteredValues[floor($count / 2)];
        }
    }

    /**
     * Safe margin calculation
     */
    public static function safeMargin($revenue, $cost, $defaultValue = 0): float
    {
        $revenue = static::nullToZero($revenue);
        $cost = static::nullToZero($cost);

        if ($revenue == 0) {
            return (float) $defaultValue;
        }

        $margin = (($revenue - $cost) / $revenue) * 100;
        return round($margin, 2);
    }

    /**
     * Safe ROI calculation
     */
    public static function safeROI($gain, $investment, $defaultValue = 0): float
    {
        $gain = static::nullToZero($gain);
        $investment = static::nullToZero($investment);

        if ($investment == 0) {
            return (float) $defaultValue;
        }

        $roi = ($gain / $investment) * 100;
        return round($roi, 2);
    }

    /**
     * Convert null to zero
     */
    public static function nullToZero($value): float
    {
        if (is_null($value) || $value === '' || $value === false) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Try to parse string numbers
        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /**
     * Safe min calculation dengan null handling
     */
    public static function safeMin(array $values, $defaultValue = 0): float
    {
        $filteredValues = array_filter($values, function($value) {
            return !is_null($value);
        });

        $filteredValues = array_map([static::class, 'nullToZero'], $filteredValues);

        if (empty($filteredValues)) {
            return (float) $defaultValue;
        }

        return (float) min($filteredValues);
    }

    /**
     * Safe max calculation dengan null handling
     */
    public static function safeMax(array $values, $defaultValue = 0): float
    {
        $filteredValues = array_filter($values, function($value) {
            return !is_null($value);
        });

        $filteredValues = array_map([static::class, 'nullToZero'], $filteredValues);

        if (empty($filteredValues)) {
            return (float) $defaultValue;
        }

        return (float) max($filteredValues);
    }

    /**
     * Safe sum calculation dengan null handling
     */
    public static function safeSum(array $values): float
    {
        $filteredValues = array_map([static::class, 'nullToZero'], $values);
        return (float) array_sum($filteredValues);
    }

    /**
     * Safe count dengan null filtering
     */
    public static function safeCount(array $values, bool $includeZeros = true): int
    {
        if ($includeZeros) {
            // Count non-null values
            return count(array_filter($values, function($value) {
                return !is_null($value);
            }));
        } else {
            // Count non-null, non-zero values
            return count(array_filter($values, function($value) {
                return !is_null($value) && static::nullToZero($value) != 0;
            }));
        }
    }

    /**
     * Safe variance calculation
     */
    public static function safeVariance(array $values, $defaultValue = 0): float
    {
        $filteredValues = array_filter($values, function($value) {
            return !is_null($value);
        });

        $filteredValues = array_map([static::class, 'nullToZero'], $filteredValues);

        if (count($filteredValues) < 2) {
            return (float) $defaultValue;
        }

        $mean = static::safeAverage($filteredValues);
        $sumSquaredDiffs = 0;

        foreach ($filteredValues as $value) {
            $sumSquaredDiffs += pow($value - $mean, 2);
        }

        return $sumSquaredDiffs / count($filteredValues);
    }

    /**
     * Safe standard deviation calculation
     */
    public static function safeStandardDeviation(array $values, $defaultValue = 0): float
    {
        $variance = static::safeVariance($values, $defaultValue);
        return sqrt($variance);
    }

    /**
     * Format number dengan safe handling
     */
    public static function formatNumber($value, int $decimals = 2, $nullDisplay = 'N/A'): string
    {
        if (is_null($value)) {
            return $nullDisplay;
        }

        $numericValue = static::nullToZero($value);
        return number_format($numericValue, $decimals);
    }

    /**
     * Format percentage dengan safe handling
     */
    public static function formatPercentage($value, int $decimals = 1, $nullDisplay = 'N/A'): string
    {
        if (is_null($value)) {
            return $nullDisplay;
        }

        $numericValue = static::nullToZero($value);
        return number_format($numericValue, $decimals) . '%';
    }

    /**
     * Format currency dengan safe handling
     */
    public static function formatCurrency($value, string $currency = 'Rp', $nullDisplay = 'N/A'): string
    {
        if (is_null($value)) {
            return $nullDisplay;
        }

        $numericValue = static::nullToZero($value);
        return $currency . ' ' . number_format($numericValue, 0);
    }

    /**
     * Validate numeric input dan convert safely
     */
    public static function validateNumeric($value, $defaultValue = 0, $allowNegative = true): float
    {
        $converted = static::nullToZero($value);

        if (!$allowNegative && $converted < 0) {
            return (float) $defaultValue;
        }

        return $converted;
    }

    /**
     * Safe range calculation
     */
    public static function safeRange(array $values, $defaultValue = 0): float
    {
        $filteredValues = array_filter($values, function($value) {
            return !is_null($value);
        });

        if (empty($filteredValues)) {
            return (float) $defaultValue;
        }

        $min = static::safeMin($filteredValues);
        $max = static::safeMax($filteredValues);

        return $max - $min;
    }

    /**
     * Safe coefficient of variation calculation
     */
    public static function safeCoefficientOfVariation(array $values, $defaultValue = 0): float
    {
        $mean = static::safeAverage($values);
        $stdDev = static::safeStandardDeviation($values);

        if ($mean == 0) {
            return (float) $defaultValue;
        }

        return static::safeDivision($stdDev, $mean) * 100;
    }

    /**
     * Clean array dari null dan invalid values
     */
    public static function cleanArray(array $values, bool $removeZeros = false): array
    {
        $cleaned = array_filter($values, function($value) {
            return !is_null($value);
        });

        $cleaned = array_map([static::class, 'nullToZero'], $cleaned);

        if ($removeZeros) {
            $cleaned = array_filter($cleaned, function($value) {
                return $value != 0;
            });
        }

        return array_values($cleaned); // Re-index array
    }

    /**
     * Batch process array dengan safe operations
     */
    public static function batchProcess(array $data, callable $operation, $defaultValue = 0): array
    {
        $results = [];

        foreach ($data as $key => $value) {
            try {
                $results[$key] = $operation($value);
            } catch (\Exception $e) {
                $results[$key] = $defaultValue;
            }
        }

        return $results;
    }
}
