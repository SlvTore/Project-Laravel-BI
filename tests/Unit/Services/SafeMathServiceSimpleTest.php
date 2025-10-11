<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SafeMathService;

class SafeMathServiceSimpleTest extends TestCase
{
    /** @test */
    public function test_basic_safe_operations()
    {
        // Safe division
        $this->assertEquals(25.0, SafeMathService::safeDivision(100, 4));
        $this->assertEquals(0.0, SafeMathService::safeDivision(100, 0));
        
        // Safe percentage
        $this->assertEquals(25.0, SafeMathService::safePercentage(25, 100));
        $this->assertEquals(0.0, SafeMathService::safePercentage(50, 0));
        
        // Safe growth rate
        $this->assertEquals(20.0, SafeMathService::safeGrowthRate(120, 100));
        $this->assertEquals(100.0, SafeMathService::safeGrowthRate(100, 0));
        
        // Null to zero conversion
        $this->assertEquals(0.0, SafeMathService::nullToZero(null));
        $this->assertEquals(42.0, SafeMathService::nullToZero(42));
        $this->assertEquals(42.5, SafeMathService::nullToZero('42.5'));
        $this->assertEquals(123.0, SafeMathService::nullToZero('$123'));
    }

    /** @test */
    public function test_null_handling()
    {
        $this->assertEquals(0.0, SafeMathService::safeDivision(null, 4));
        $this->assertEquals(0.0, SafeMathService::safeDivision(100, null));
        $this->assertEquals(999.0, SafeMathService::safeDivision(null, null, 999));
    }

    /** @test */
    public function test_margin_calculations()
    {
        // Normal margin
        $this->assertEquals(25.0, SafeMathService::safeMargin(100, 75));
        
        // Zero revenue
        $this->assertEquals(0.0, SafeMathService::safeMargin(0, 50));
        
        // Cost exceeds revenue (negative margin)
        $this->assertEquals(-50.0, SafeMathService::safeMargin(100, 150));
    }

    /** @test */
    public function test_array_operations()
    {
        $values = [10, null, 20, 30, null];
        
        // Safe sum
        $this->assertEquals(60.0, SafeMathService::safeSum($values));
        
        // Safe average  
        $this->assertEquals(20.0, SafeMathService::safeAverage($values));
        
        // Safe min/max
        $this->assertEquals(10.0, SafeMathService::safeMin($values));
        $this->assertEquals(30.0, SafeMathService::safeMax($values));
    }

    /** @test */
    public function test_real_world_scenarios()
    {
        // E-commerce scenarios
        $revenue = 50000;
        $orders = 500;
        $cogs = 30000;
        
        // Average order value
        $aov = SafeMathService::safeDivision($revenue, $orders);
        $this->assertEquals(100.0, $aov);
        
        // Gross margin
        $margin = SafeMathService::safeMargin($revenue, $cogs);
        $this->assertEquals(40.0, $margin);
        
        // Growth calculation
        $previousMonth = 40000;
        $currentMonth = 50000;
        $growth = SafeMathService::safeGrowthRate($currentMonth, $previousMonth);
        $this->assertEquals(25.0, $growth);
    }
}