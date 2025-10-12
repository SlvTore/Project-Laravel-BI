<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OlapWarehouseService;
use App\Models\DataFeed;
use Illuminate\Support\Facades\DB;

echo "=== MANUAL ETL TRIGGER ===\n\n";

$feedId = $argv[1] ?? null;

if (!$feedId) {
    echo "Usage: php manual_etl_trigger.php <feed_id>\n\n";
    echo "Available feeds:\n";

    $feeds = DB::table('data_feeds')
        ->select('id', 'business_id', 'status', 'record_count', 'created_at')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();

    foreach ($feeds as $feed) {
        echo "  Feed #{$feed->id}: {$feed->status} ({$feed->record_count} records) - {$feed->created_at}\n";
    }

    exit(0);
}

echo "Processing feed #{$feedId}...\n\n";

$feed = DataFeed::find($feedId);
if (!$feed) {
    echo "ERROR: Feed #{$feedId} not found!\n";
    exit(1);
}

echo "Feed Status: {$feed->status}\n";
echo "Record Count: {$feed->record_count}\n\n";

// Check staging data
$stagingCount = DB::table('staging_sales_items')
    ->where('data_feed_id', $feedId)
    ->count();

echo "Staging items: {$stagingCount}\n";

if ($stagingCount === 0) {
    echo "WARNING: No staging data found for this feed!\n";
    exit(1);
}

// Show sample staging
$sample = DB::table('staging_sales_items')
    ->where('data_feed_id', $feedId)
    ->first();

echo "\nSample staging item:\n";
echo "  Product: {$sample->product_name}\n";
echo "  Quantity: {$sample->quantity}\n";
echo "  Price: {$sample->selling_price_at_transaction}\n";
echo "  Discount: {$sample->discount_per_item}\n";
echo "  Location ID: " . ($sample->location_id ?? 'NULL') . "\n";
echo "  Location: {$sample->kecamatan}, {$sample->kota}, {$sample->provinsi}\n\n";

echo "Starting ETL process...\n";

try {
    DB::beginTransaction();

    $warehouse = app(OlapWarehouseService::class);
    $result = $warehouse->loadFactsFromStaging($feed);

    DB::commit();

    echo "\n✅ ETL SUCCESS!\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

    // Check fact_sales
    $factCount = DB::table('fact_sales')
        ->where('data_feed_id', $feedId)
        ->count();

    echo "\nFact sales records inserted: {$factCount}\n";

    if ($factCount > 0) {
        $sampleFact = DB::table('fact_sales as f')
            ->join('dim_product as p', 'f.product_id', '=', 'p.id')
            ->where('f.data_feed_id', $feedId)
            ->select('f.*', 'p.name as product_name')
            ->first();

        echo "\nSample fact record:\n";
        echo "  Product: {$sampleFact->product_name}\n";
        echo "  Quantity: {$sampleFact->quantity}\n";
        echo "  Unit Price: {$sampleFact->unit_price}\n";
        echo "  Total Amount: {$sampleFact->total_amount}\n";
        echo "  Location ID: " . ($sampleFact->location_id ?? 'NULL') . "\n";
        echo "  Margin: {$sampleFact->gross_margin_amount} ({$sampleFact->gross_margin_percent}%)\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ETL FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== END ===\n";
