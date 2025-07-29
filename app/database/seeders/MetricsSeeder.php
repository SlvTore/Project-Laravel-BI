<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Metric;

class MetricsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $metrics = [
            [
                'name' => 'Total Sales',
                'category' => 'Sales',
                'icon' => 'bi-graph-up',
                'description' => 'Measures the total number of transactions that occurred within a specific period',
                'is_active' => true
            ],
            [
                'name' => 'Revenue Growth',
                'category' => 'Revenue',
                'icon' => 'bi-trending-up',
                'description' => 'Tracks revenue growth over time to understand business trends',
                'is_active' => true
            ],
            [
                'name' => 'Average Sales per Customer',
                'category' => 'Customer',
                'icon' => 'bi-person-check',
                'description' => 'Indicates the average purchase amount per customer',
                'is_active' => true
            ],
            [
                'name' => 'Number of New Customers',
                'category' => 'Customer',
                'icon' => 'bi-people',
                'description' => 'Measures the activity of acquiring new customers',
                'is_active' => true
            ],
            [
                'name' => 'Best-Selling Products',
                'category' => 'Product',
                'icon' => 'bi-trophy',
                'description' => 'Identifies top-performing products for stock and marketing strategies',
                'is_active' => true
            ],
            [
                'name' => 'Customer Retention Rate',
                'category' => 'Customer',
                'icon' => 'bi-heart',
                'description' => 'Shows the percentage of customers who continue to make purchases',
                'is_active' => true
            ],
            [
                'name' => 'Profit Margin',
                'category' => 'Finance',
                'icon' => 'bi-calculator',
                'description' => 'Calculates the profitability of the business by analyzing revenue and costs',
                'is_active' => true
            ],
            [
                'name' => 'Market Share',
                'category' => 'Market',
                'icon' => 'bi-pie-chart',
                'description' => 'Measures your business position relative to competitors in the market',
                'is_active' => true
            ]
        ];

        foreach ($metrics as $metric) {
            Metric::firstOrCreate(
                ['name' => $metric['name']],
                $metric
            );
        }
    }
}
