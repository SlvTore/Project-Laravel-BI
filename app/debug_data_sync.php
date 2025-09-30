<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\DataFeed;

echo "=== Checking Data Synchronization Between Data Feeds and OLAP Views ===\n\n";

try {
    // Check data feeds first
    echo "1. Data Feeds Status:\n";
    $dataFeeds = DataFeed::all();
    echo "   Total data feeds: " . $dataFeeds->count() . "\n";

    if ($dataFeeds->count() > 0) {
        foreach ($dataFeeds as $feed) {
            echo "   - ID: {$feed->id}, Status: {$feed->status}, Records: " . ($feed->total_records ?? 0) . "\n";
        }
    } else {
        echo "   No data feeds found (all cleaned)\n";
    }

    echo "\n2. Fact Sales Table Status:\n";
    $factSalesCount = DB::table('fact_sales')->count();
    echo "   Total fact_sales records: {$factSalesCount}\n";

    if ($factSalesCount > 0) {
        $businessIds = DB::table('fact_sales')->distinct()->pluck('business_id');
        echo "   Business IDs in fact_sales: " . $businessIds->implode(', ') . "\n";

        $sampleRecords = DB::table('fact_sales')->take(3)->get(['id', 'business_id', 'total_amount', 'created_at']);
        foreach ($sampleRecords as $record) {
            echo "   - Sample: ID {$record->id}, Business {$record->business_id}, Amount {$record->total_amount}, Created: {$record->created_at}\n";
        }
    }

    echo "\n3. OLAP Views Data Status:\n";
    $olapViews = [
        'vw_sales_daily' => 'sales_date, total_gross_revenue',
        'vw_margin_daily' => 'sales_date, total_margin',
        'vw_new_customers_daily' => 'sales_date, new_customers',
        'vw_returning_customers_daily' => 'sales_date, returning_customers'
    ];

    foreach ($olapViews as $view => $columns) {
        try {
            $count = DB::table($view)->count();
            echo "   {$view}: {$count} records\n";

            if ($count > 0) {
                $latest = DB::table($view)->orderBy('sales_date', 'desc')->first();
                echo "     Latest date: {$latest->sales_date}\n";
            }
        } catch (Exception $e) {
            echo "   {$view}: ERROR - {$e->getMessage()}\n";
        }
    }

    echo "\n4. Checking Dimension Tables:\n";
    $dimTables = ['dim_date', 'dim_product', 'dim_customer'];
    foreach ($dimTables as $table) {
        try {
            $count = DB::table($table)->count();
            echo "   {$table}: {$count} records\n";
        } catch (Exception $e) {
            echo "   {$table}: ERROR - {$e->getMessage()}\n";
        }
    }

    echo "\n5. DataFeed to FactSales Relationship Check:\n";
    $factSalesWithFeedId = DB::table('fact_sales')
        ->whereNotNull('data_feed_id')
        ->count();
    $factSalesWithoutFeedId = DB::table('fact_sales')
        ->whereNull('data_feed_id')
        ->count();

    echo "   FactSales with data_feed_id: {$factSalesWithFeedId}\n";
    echo "   FactSales without data_feed_id: {$factSalesWithoutFeedId}\n";

    if ($factSalesWithFeedId > 0) {
        echo "   ISSUE: Found fact_sales records still linked to data feeds!\n";
        $orphanedFeeds = DB::table('fact_sales as fs')
            ->leftJoin('data_feeds as df', 'fs.data_feed_id', '=', 'df.id')
            ->whereNull('df.id')
            ->whereNotNull('fs.data_feed_id')
            ->count();
        echo "   Orphaned fact_sales (linked to deleted feeds): {$orphanedFeeds}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
