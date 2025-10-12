<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== STAGING DEBUG ===\n\n";

$feedId = 1;

echo "Query: SELECT * FROM staging_sales_items WHERE data_feed_id = {$feedId}\n\n";

$items = DB::table('staging_sales_items')
    ->where('data_feed_id', $feedId)
    ->get();

echo "Count: " . $items->count() . "\n\n";

foreach ($items as $item) {
    echo "Item #{$item->id}:\n";
    echo "  product_id: {$item->product_id}\n";
    echo "  product_name: {$item->product_name}\n";
    echo "  quantity: {$item->quantity}\n";
    echo "  selling_price_at_transaction: {$item->selling_price_at_transaction}\n";
    echo "  discount_per_item: {$item->discount_per_item}\n";
    echo "  tax_amount: " . ($item->tax_amount ?? 'NULL') . "\n";
    echo "  shipping_cost: " . ($item->shipping_cost ?? 'NULL') . "\n";
    echo "  transaction_date: {$item->transaction_date}\n";
    echo "  customer_id: " . ($item->customer_id ?? 'NULL') . "\n";
    echo "  location_id: " . ($item->location_id ?? 'NULL') . "\n";
    echo "  provinsi: " . ($item->provinsi ?? 'NULL') . "\n";
    echo "  kota: " . ($item->kota ?? 'NULL') . "\n";
    echo "  kecamatan: " . ($item->kecamatan ?? 'NULL') . "\n";
    echo "\n";
}

// Test chunk
echo "\n=== TEST CHUNK ===\n\n";

$chunkCalled = 0;
$itemsInChunk = 0;

DB::table('staging_sales_items')
    ->where('data_feed_id', $feedId)
    ->orderBy('id')
    ->chunkById(500, function ($items) use (&$chunkCalled, &$itemsInChunk) {
        $chunkCalled++;
        $itemsInChunk += $items->count();
        echo "Chunk #{$chunkCalled}: {$items->count()} items\n";

        foreach ($items as $item) {
            echo "  - Item #{$item->id}: {$item->product_name}\n";
        }
    });

echo "\nTotal chunks: {$chunkCalled}\n";
echo "Total items processed: {$itemsInChunk}\n";

echo "\n=== END ===\n";
