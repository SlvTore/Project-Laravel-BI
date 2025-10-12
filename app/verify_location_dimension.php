<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LOCATION DIMENSION VERIFICATION ===\n\n";

// Check dim_location
$locationCount = DB::table('dim_location')->count();
echo "✓ dim_location records: {$locationCount}\n";

if ($locationCount > 0) {
    echo "\nSample locations:\n";
    $locations = DB::table('dim_location')
        ->select('provinsi', 'kota', 'kecamatan')
        ->limit(10)
        ->get();

    foreach ($locations as $loc) {
        echo "  - {$loc->provinsi} > {$loc->kota} > {$loc->kecamatan}\n";
    }
}

// Check fact_sales structure
echo "\n\n✓ fact_sales columns:\n";
$columns = DB::select("SHOW COLUMNS FROM fact_sales");
foreach ($columns as $col) {
    if (in_array($col->Field, ['location_id', 'channel_id'])) {
        echo "  - {$col->Field}: {$col->Type} " . ($col->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
}

// Check staging_sales_items structure
echo "\n✓ staging_sales_items location columns:\n";
$stagingCols = DB::select("SHOW COLUMNS FROM staging_sales_items WHERE Field IN ('provinsi', 'kota', 'kecamatan', 'location_id')");
foreach ($stagingCols as $col) {
    echo "  - {$col->Field}: {$col->Type}\n";
}

// Check views
echo "\n✓ OLAP views with location:\n";
$views = ['vw_sales_daily', 'vw_sales_product_daily', 'vw_margin_daily', 'vw_cogs_daily',
          'vw_sales_by_provinsi', 'vw_sales_by_kota', 'vw_sales_by_kecamatan', 'vw_sales_location_daily'];

foreach ($views as $view) {
    $exists = DB::select("SHOW TABLES LIKE '{$view}'");
    echo "  " . ($exists ? "✓" : "✗") . " {$view}\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
