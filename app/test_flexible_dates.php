<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TESTING FLEXIBLE DATE RANGE ===\n\n";

$businessId = 1;

// Check latest fact_sales date
$latestFactDate = DB::table('fact_sales')
    ->where('business_id', $businessId)
    ->join('dim_date', 'fact_sales.date_id', '=', 'dim_date.id')
    ->max('dim_date.date');

echo "Latest fact_sales date: {$latestFactDate}\n";

if ($latestFactDate) {
    $latestDate = Carbon::parse($latestFactDate);
    $from = $latestDate->copy()->subDays(30);
    $to = $latestDate->copy();
    $monthStart = $latestDate->copy()->startOfMonth();

    echo "Adjusted date range: {$from->toDateString()} to {$to->toDateString()}\n";
    echo "Month start: {$monthStart->toDateString()}\n\n";

    // Test sales query with adjusted range
    echo "📊 TOTAL PENJUALAN (adjusted range):\n";
    $dailySales = DB::table('vw_sales_daily')
        ->where('business_id', $businessId)
        ->where('date', '>=', $from->toDateString())
        ->where('date', '<=', $to->toDateString())
        ->orderBy('date')
        ->get();

    echo "Daily records: " . $dailySales->count() . "\n";
    foreach ($dailySales as $row) {
        echo "  {$row->date}: Rp " . number_format($row->gross_revenue, 0) . " ({$row->transaction_count} trans, {$row->item_count} items)\n";
    }

    $monthlySales = DB::table('vw_sales_daily')
        ->where('business_id', $businessId)
        ->where('date', '>=', $monthStart->toDateString())
        ->where('date', '<=', $to->toDateString())
        ->selectRaw('SUM(gross_revenue) as total_revenue, SUM(transaction_count) as transaction_count, SUM(item_count) as total_quantity')
        ->first();

    echo "\nMonthly summary: Rp " . number_format($monthlySales->total_revenue, 0) . " ({$monthlySales->transaction_count} trans, {$monthlySales->total_quantity} items)\n";
}

echo "\n=== END ===\n";
