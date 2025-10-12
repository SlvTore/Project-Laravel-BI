<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== WAREHOUSE DATA TEST ===\n\n";

$businessId = 1;
$from = Carbon::now()->subDays(30);
$monthStart = Carbon::now()->startOfMonth();

// Test Total Penjualan query
echo "📊 TOTAL PENJUALAN:\n";
$dailySales = DB::table('vw_sales_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $from->toDateString())
    ->orderBy('date')
    ->get();

echo "Daily records: " . $dailySales->count() . "\n";
if ($dailySales->count() > 0) {
    $sample = $dailySales->first();
    echo "Sample fields: date={$sample->date}, gross_revenue={$sample->gross_revenue}, transaction_count={$sample->transaction_count}, item_count={$sample->item_count}\n";
}

$monthlySales = DB::table('vw_sales_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $monthStart->toDateString())
    ->selectRaw('SUM(gross_revenue) as total_revenue, SUM(transaction_count) as transaction_count, SUM(item_count) as total_quantity')
    ->first();

echo "Monthly: total_revenue=" . ($monthlySales->total_revenue ?? 0) . ", transaction_count=" . ($monthlySales->transaction_count ?? 0) . ", total_quantity=" . ($monthlySales->total_quantity ?? 0) . "\n\n";

// Test COGS
echo "💰 COGS:\n";
$dailyCogs = DB::table('vw_cogs_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $from)
    ->orderBy('date')
    ->get();

echo "Daily records: " . $dailyCogs->count() . "\n";
if ($dailyCogs->count() > 0) {
    $sample = $dailyCogs->first();
    echo "Sample: date={$sample->date}, total_cogs={$sample->total_cogs}\n";
}

$monthlyCogs = DB::table('vw_cogs_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $monthStart)
    ->selectRaw('SUM(total_cogs) as total_cogs')
    ->first();

echo "Monthly: total_cogs=" . ($monthlyCogs->total_cogs ?? 0) . "\n\n";

// Test Margin
echo "📈 MARGIN:\n";
$dailyMargin = DB::table('vw_margin_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $from)
    ->orderBy('date')
    ->get();

echo "Daily records: " . $dailyMargin->count() . "\n";
if ($dailyMargin->count() > 0) {
    $sample = $dailyMargin->first();
    echo "Sample: date={$sample->date}, total_margin={$sample->total_margin}\n";
}

$monthlyMargin = DB::table('vw_margin_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $monthStart)
    ->selectRaw('SUM(total_margin) as total_margin')
    ->first();

echo "Monthly: total_margin=" . ($monthlyMargin->total_margin ?? 0) . "\n\n";

// Test Top Products
echo "🏆 TOP PRODUCTS:\n";
$topProducts = DB::table('vw_sales_product_daily')
    ->where('business_id', $businessId)
    ->where('date', '>=', $from)
    ->select('product_name', DB::raw('SUM(revenue) as total_revenue'), DB::raw('SUM(quantity_sold) as total_qty'))
    ->groupBy('product_name')
    ->orderByDesc(DB::raw('SUM(revenue)'))
    ->limit(10)
    ->get();

echo "Top products: " . $topProducts->count() . "\n";
foreach ($topProducts as $p) {
    echo "  - {$p->product_name}: Rp " . number_format($p->total_revenue, 0) . " (qty: {$p->total_qty})\n";
}

echo "\n=== END ===\n";
