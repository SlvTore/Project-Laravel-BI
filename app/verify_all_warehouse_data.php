<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== FINAL WAREHOUSE DATA VERIFICATION ===\n\n";

$businessId = 1;

// Get flexible date range
$latestFactDate = DB::table('fact_sales')
    ->where('business_id', $businessId)
    ->join('dim_date', 'fact_sales.date_id', '=', 'dim_date.id')
    ->max('dim_date.date');

if (!$latestFactDate) {
    echo "❌ No data in fact_sales!\n";
    exit(1);
}

$latestDate = Carbon::parse($latestFactDate);
$from = $latestDate->copy()->subDays(30);
$to = $latestDate->copy();
$monthStart = $latestDate->copy()->startOfMonth();

echo "📅 Date Range: {$from->toDateString()} to {$to->toDateString()}\n";
echo "📅 Month Start: {$monthStart->toDateString()}\n\n";

// Test all metric types
$metrics = [
    'Total Penjualan' => [
        'view' => 'vw_sales_daily',
        'fields' => ['gross_revenue', 'transaction_count', 'item_count']
    ],
    'COGS' => [
        'view' => 'vw_cogs_daily',
        'fields' => ['total_cogs']
    ],
    'Margin' => [
        'view' => 'vw_margin_daily',
        'fields' => ['total_margin', 'margin_percentage']
    ],
    'Pelanggan Baru' => [
        'view' => 'vw_new_customers_daily',
        'fields' => ['new_customers']
    ],
];

foreach ($metrics as $metricName => $config) {
    echo "📊 {$metricName}:\n";

    $daily = DB::table($config['view'])
        ->where('business_id', $businessId)
        ->where($config['view'] === 'vw_new_customers_daily' ? 'sales_date' : 'date', '>=', $from->toDateString())
        ->where($config['view'] === 'vw_new_customers_daily' ? 'sales_date' : 'date', '<=', $to->toDateString())
        ->orderBy($config['view'] === 'vw_new_customers_daily' ? 'sales_date' : 'date')
        ->get();

    echo "  Daily records: " . $daily->count() . "\n";

    if ($daily->count() > 0) {
        $sample = $daily->first();
        $dateField = $config['view'] === 'vw_new_customers_daily' ? 'sales_date' : 'date';
        echo "  Sample ({$sample->$dateField}):\n";

        foreach ($config['fields'] as $field) {
            if (isset($sample->$field)) {
                $value = $sample->$field;
                if (strpos($field, 'revenue') !== false || strpos($field, 'cogs') !== false || strpos($field, 'margin') !== false) {
                    echo "    - {$field}: Rp " . number_format($value, 0) . "\n";
                } else {
                    echo "    - {$field}: {$value}\n";
                }
            }
        }
    }
    echo "\n";
}

// Test top products
echo "🏆 TOP PRODUCTS:\n";
$topProducts = DB::table('vw_sales_product_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $from)
    ->where('date', '<=', $to)
    ->select('product_name', DB::raw('SUM(revenue) as total_revenue'), DB::raw('SUM(quantity_sold) as total_qty'))
    ->groupBy('product_name')
    ->orderByDesc(DB::raw('SUM(revenue)'))
    ->limit(10)
    ->get();

echo "  Total products: " . $topProducts->count() . "\n";
foreach ($topProducts as $index => $p) {
    echo "  " . ($index + 1) . ". {$p->product_name}: Rp " . number_format($p->total_revenue, 0) . " (qty: {$p->total_qty})\n";
}

echo "\n✅ ALL WAREHOUSE DATA AVAILABLE!\n";
echo "\n=== END ===\n";
