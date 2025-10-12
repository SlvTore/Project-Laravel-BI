<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== WAREHOUSE SYNC STATUS CHECK ===\n\n";

// 1. Check staging_sales_items
$stagingCount = DB::table('staging_sales_items')->count();
echo "📦 Staging Sales Items: {$stagingCount}\n";

if ($stagingCount > 0) {
    $sampleStaging = DB::table('staging_sales_items')
        ->select('id', 'data_feed_id', 'product_name', 'quantity', 'transaction_date', 'location_id')
        ->limit(3)
        ->get();

    echo "   Sample records:\n";
    foreach ($sampleStaging as $item) {
        echo "   - ID {$item->id}: {$item->product_name} (qty: {$item->quantity}, location_id: {$item->location_id})\n";
    }
}

// 2. Check fact_sales
$factCount = DB::table('fact_sales')->count();
echo "\n🏢 Fact Sales Records: {$factCount}\n";

if ($factCount > 0) {
    $sampleFact = DB::table('fact_sales as f')
        ->join('dim_product as p', 'f.product_id', '=', 'p.id')
        ->join('dim_date as d', 'f.date_id', '=', 'd.id')
        ->select('f.id', 'p.name as product', 'd.date', 'f.quantity', 'f.total_amount', 'f.location_id')
        ->limit(3)
        ->get();

    echo "   Sample records:\n";
    foreach ($sampleFact as $fact) {
        echo "   - ID {$fact->id}: {$fact->product} on {$fact->date} (qty: {$fact->quantity}, amt: {$fact->total_amount}, location_id: {$fact->location_id})\n";
    }
}

// 3. Check data_feeds status
echo "\n📊 Data Feeds Status:\n";
$feeds = DB::table('data_feeds')
    ->select('id', 'business_id', 'status', 'record_count', 'created_at')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($feeds as $feed) {
    echo "   Feed #{$feed->id}: {$feed->status} ({$feed->record_count} records) - {$feed->created_at}\n";
}

// 4. Check failed jobs
$failedJobs = DB::table('failed_jobs')->count();
echo "\n❌ Failed Queue Jobs: {$failedJobs}\n";

if ($failedJobs > 0) {
    $failed = DB::table('failed_jobs')
        ->select('id', 'queue', 'exception')
        ->limit(3)
        ->get();

    foreach ($failed as $job) {
        $exception = substr($job->exception, 0, 200);
        echo "   Job #{$job->id} ({$job->queue}): {$exception}...\n";
    }
}

// 5. Check jobs queue
$queuedJobs = DB::table('jobs')->count();
echo "\n⏳ Pending Queue Jobs: {$queuedJobs}\n";

if ($queuedJobs > 0) {
    $jobs = DB::table('jobs')
        ->select('id', 'queue', 'payload', 'created_at')
        ->limit(3)
        ->get();

    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobClass = $payload['displayName'] ?? 'Unknown';
        echo "   Job #{$job->id} ({$job->queue}): {$jobClass} - {$job->created_at}\n";
    }
}

// 6. Check dimensions
echo "\n📐 Dimension Tables:\n";
$dimProduct = DB::table('dim_product')->count();
$dimCustomer = DB::table('dim_customer')->count();
$dimLocation = DB::table('dim_location')->count();
$dimPromotion = DB::table('dim_promotion')->count();

echo "   dim_product: {$dimProduct}\n";
echo "   dim_customer: {$dimCustomer}\n";
echo "   dim_location: {$dimLocation}\n";
echo "   dim_promotion: {$dimPromotion}\n";

// 7. Check views
echo "\n👁️ OLAP Views:\n";
try {
    $salesDaily = DB::table('vw_sales_daily')->count();
    echo "   vw_sales_daily: {$salesDaily} records\n";
} catch (\Exception $e) {
    echo "   vw_sales_daily: ERROR - " . $e->getMessage() . "\n";
}

// 8. Check sales_transactions
$transactions = DB::table('sales_transactions')->count();
echo "\n💰 Sales Transactions: {$transactions}\n";

if ($transactions > 0) {
    $sampleTx = DB::table('sales_transactions')
        ->select('id', 'customer_id', 'transaction_date', 'total_amount', 'provinsi', 'kota', 'location_id')
        ->limit(3)
        ->get();

    echo "   Sample records:\n";
    foreach ($sampleTx as $tx) {
        $location = $tx->location_id ? "location_id: {$tx->location_id}" : "no location";
        echo "   - TX #{$tx->id}: {$tx->transaction_date} (amt: {$tx->total_amount}, {$location})\n";
    }
}

echo "\n=== END OF CHECK ===\n";
