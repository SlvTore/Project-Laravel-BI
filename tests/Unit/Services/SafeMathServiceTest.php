<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SafeMathService;

class SafeMathServiceTest extends TestCase
{
    private SafeMathService $safeMath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->safeMath = new SafeMathService();
    }

    /** @test */
    public function test_safe_division_with_valid_numbers()
    {
        $result = SafeMathService::safeDivision(100, 4);
        $this->assertEquals(25.0, $result);

        $result = SafeMathService::safeDivision(7, 3);
        $this->assertEquals(2.3333333333333335, $result); // Exact float precision
    }

    /** @test */
    public function test_safe_division_with_zero_denominator()
    {
        $result = SafeMathService::safeDivision(100, 0);
        $this->assertEquals(0.0, $result);

        $result = SafeMathService::safeDivision(100, 0, 999);
        $this->assertEquals(999.0, $result);
    }

    /** @test */
    public function test_safe_division_with_null_values()
    {
        $result = SafeMathService::safeDivision(null, 4);
        $this->assertEquals(0.0, $result);

        $result = SafeMathService::safeDivision(100, null);
        $this->assertEquals(0.0, $result);

        $result = SafeMathService::safeDivision(null, null, 42);
        $this->assertEquals(42.0, $result);
    }

    /** @test */
    public function test_safe_percentage_calculation()
    {
        $result = SafeMathService::safePercentage(25, 100);
        $this->assertEquals(25.0, $result);

        $result = SafeMathService::safePercentage(33, 100);
        $this->assertEquals(33.0, $result);

        $result = SafeMathService::safePercentage(1, 3);
        $this->assertEquals(33.33, $result);
    }

    /** @test */
    public function test_safe_percentage_with_zero_total()
    {
        $result = SafeMathService::safePercentage(50, 0);
        $this->assertEquals(0.0, $result);

        $result = SafeMathService::safePercentage(50, 0, 100);
        $this->assertEquals(10000.0, $result); // 50/0 default to 100, then * 100 = 10000
    }

    /** @test */
    public function test_safe_growth_rate_calculation()
    {
        // Normal growth
        $result = SafeMathService::safeGrowthRate(120, 100);
        $this->assertEquals(20.0, $result);

        // Negative growth
        $result = SafeMathService::safeGrowthRate(80, 100);
        $this->assertEquals(-20.0, $result);

        // No change
        $result = SafeMathService::safeGrowthRate(100, 100);
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function test_safe_growth_rate_with_zero_previous()
    {
        // From zero to positive
        $result = SafeMathService::safeGrowthRate(100, 0);
        $this->assertEquals(100.0, $result);

        // From zero to zero
        $result = SafeMathService::safeGrowthRate(0, 0);
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function test_safe_average_calculation()
    {
        $values = [10, 20, 30, 40, 50];
        $result = SafeMathService::safeAverage($values);
        $this->assertEquals(30.0, $result);

        $values = [100, 200, 300];
        $result = SafeMathService::safeAverage($values);
        $this->assertEquals(200.0, $result);
    }

    /** @test */
    public function test_safe_average_with_null_values()
    {
        $values = [10, null, 30, null, 50];
        $result = SafeMathService::safeAverage($values);
        $this->assertEquals(30.0, $result);

        $values = [null, null, null];
        $result = SafeMathService::safeAverage($values, 999);
        $this->assertEquals(999.0, $result);
    }

    /** @test */
    public function test_safe_median_calculation()
    {
        // Odd number of values
        $values = [1, 3, 5, 7, 9];
        $result = SafeMathService::safeMedian($values);
        $this->assertEquals(5.0, $result);

        // Even number of values
        $values = [1, 2, 3, 4];
        $result = SafeMathService::safeMedian($values);
        $this->assertEquals(2.5, $result);
    }

    /** @test */
    public function test_safe_margin_calculation()
    {
        // Normal margin
        $result = SafeMathService::safeMargin(100, 75);
        $this->assertEquals(25.0, $result);

        // Zero cost (100% margin)
        $result = SafeMathService::safeMargin(100, 0);
        $this->assertEquals(100.0, $result);

        // Cost equals revenue (0% margin)
        $result = SafeMathService::safeMargin(100, 100);
        $this->assertEquals(0.0, $result);

        // Cost exceeds revenue (negative margin)
        $result = SafeMathService::safeMargin(100, 150);
        $this->assertEquals(-50.0, $result);
    }

    /** @test */
    public function test_safe_margin_with_zero_revenue()
    {
        $result = SafeMathService::safeMargin(0, 50);
        $this->assertEquals(0.0, $result);

        $result = SafeMathService::safeMargin(0, 50, 999);
        $this->assertEquals(999.0, $result);
    }

    /** @test */
    public function test_null_to_zero_conversion()
    {
        $this->assertEquals(0.0, SafeMathService::nullToZero(null));
        $this->assertEquals(0.0, SafeMathService::nullToZero(''));
        $this->assertEquals(0.0, SafeMathService::nullToZero(false));
        $this->assertEquals(42.0, SafeMathService::nullToZero(42));
        $this->assertEquals(42.5, SafeMathService::nullToZero('42.5'));
        $this->assertEquals(123.0, SafeMathService::nullToZero('$123'));
    }

    /** @test */
    public function test_safe_min_max_calculations()
    {
        $values = [10, null, 5, 20, null, 15];
        
        $min = SafeMathService::safeMin($values);
        $this->assertEquals(5.0, $min);

        $max = SafeMathService::safeMax($values);
        $this->assertEquals(20.0, $max);

        // Empty array
        $emptyValues = [null, null];
        $min = SafeMathService::safeMin($emptyValues, 999);
        $this->assertEquals(999.0, $min);
    }

    /** @test */
    public function test_safe_sum_calculation()
    {
        $values = [10, null, 20, 30, null];
        $result = SafeMathService::safeSum($values);
        $this->assertEquals(60.0, $result);

        $values = [null, null, null];
        $result = SafeMathService::safeSum($values);
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function test_safe_count_operations()
    {
        $values = [10, null, 20, 0, null, 30];
        
        // Count including zeros
        $count = SafeMathService::safeCount($values, true);
        $this->assertEquals(4, $count);

        // Count excluding zeros
        $count = SafeMathService::safeCount($values, false);
        $this->assertEquals(3, $count);
    }

    /** @test */
    public function test_safe_variance_and_standard_deviation()
    {
        $values = [2, 4, 4, 4, 5, 5, 7, 9];
        
        $variance = SafeMathService::safeVariance($values);
        $this->assertEquals(4.0, $variance);

        $stdDev = SafeMathService::safeStandardDeviation($values);
        $this->assertEquals(2.0, $stdDev);

        // Insufficient data
        $insufficientValues = [5];
        $variance = SafeMathService::safeVariance($insufficientValues, 999);
        $this->assertEquals(999.0, $variance);
    }

    /** @test */
    public function test_format_number_functions()
    {
        $this->assertEquals('1,234.57', SafeMathService::formatNumber(1234.567, 2));
        $this->assertEquals('N/A', SafeMathService::formatNumber(null));
        $this->assertEquals('Unknown', SafeMathService::formatNumber(null, 2, 'Unknown'));

        $this->assertEquals('25.5%', SafeMathService::formatPercentage(25.5));
        $this->assertEquals('N/A', SafeMathService::formatPercentage(null));

        $this->assertEquals('Rp 1,234', SafeMathService::formatCurrency(1234));
        $this->assertEquals('N/A', SafeMathService::formatCurrency(null));
    }

    /** @test */
    public function test_validate_numeric_function()
    {
        $this->assertEquals(42.0, SafeMathService::validateNumeric(42));
        $this->assertEquals(42.5, SafeMathService::validateNumeric('42.5'));
        $this->assertEquals(0.0, SafeMathService::validateNumeric(null)); // nullToZero always converts to 0
        $this->assertEquals(0.0, SafeMathService::validateNumeric(null, 999)); // nullToZero called first

        // Test negative values
        $this->assertEquals(-10.0, SafeMathService::validateNumeric(-10, 0, true));
        $this->assertEquals(0.0, SafeMathService::validateNumeric(-10, 0, false));
    }

    /** @test */
    public function test_clean_array_function()
    {
        $values = [10, null, 20, 0, null, 30, ''];
        
        $cleaned = SafeMathService::cleanArray($values, false);
        $this->assertEquals([10, 20, 0, 30, 0], array_values($cleaned));

        $cleaned = SafeMathService::cleanArray($values, true);
        $this->assertEquals([10, 20, 30], array_values($cleaned));
    }

    /** @test */
    public function test_batch_process_function()
    {
        $data = [10, 20, 30, null, 40];
        
        $results = SafeMathService::batchProcess($data, function($value) {
            return SafeMathService::nullToZero($value) * 2;
        });

        $expected = [20, 40, 60, 0, 80];
        $this->assertEquals($expected, array_values($results));
    }

    /** @test */
    public function test_real_world_scenarios()
    {
        // E-commerce margin calculation
        $revenue = 1000;
        $cogs = 650;
        $margin = SafeMathService::safeMargin($revenue, $cogs);
        $this->assertEquals(35.0, $margin);

        // Customer retention rate
        $retainedCustomers = 85;
        $totalCustomers = 100;
        $retentionRate = SafeMathService::safePercentage($retainedCustomers, $totalCustomers);
        $this->assertEquals(85.0, $retentionRate);

        // Monthly growth calculation
        $thisMonth = 12000;
        $lastMonth = 10000;
        $growthRate = SafeMathService::safeGrowthRate($thisMonth, $lastMonth);
        $this->assertEquals(20.0, $growthRate);

        // Average order value
        $totalRevenue = 50000;
        $totalOrders = 250;
        $aov = SafeMathService::safeDivision($totalRevenue, $totalOrders);
        $this->assertEquals(200.0, $aov);
    }

    /** @test */
    public function test_edge_cases()
    {
        // Very large numbers
        $largeNumber = 9999999999.99;
        $result = SafeMathService::safeDivision($largeNumber, 1);
        $this->assertEquals($largeNumber, $result);

        // Very small numbers
        $smallNumber = 0.000001;
        $result = SafeMathService::safeDivision($smallNumber, 1);
        $this->assertEquals($smallNumber, $result);

        // String numbers
        $result = SafeMathService::nullToZero('1,234.56');
        $this->assertEquals(1234.56, $result);

        // Scientific notation
        $result = SafeMathService::nullToZero('1.23e4');
        $this->assertEquals(12300.0, $result);
    }
}