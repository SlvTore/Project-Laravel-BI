<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFIKASI INTEGRASI LOKASI PADA TRANSAKSI & ETL ===\n\n";

// 1. Check CSV Template
echo "1. CSV Template Update:\n";
$templatePath = storage_path('templates/universal_data_template.csv');
if (file_exists($templatePath)) {
    $header = fgets(fopen($templatePath, 'r'));
    $hasProvinsi = strpos($header, 'provinsi') !== false;
    $hasKota = strpos($header, 'kota') !== false;
    $hasKecamatan = strpos($header, 'kecamatan') !== false;

    echo "  " . ($hasProvinsi ? "✓" : "✗") . " Kolom 'provinsi' ada di template\n";
    echo "  " . ($hasKota ? "✓" : "✗") . " Kolom 'kota' ada di template\n";
    echo "  " . ($hasKecamatan ? "✓" : "✗") . " Kolom 'kecamatan' ada di template\n";

    if ($hasProvinsi && $hasKota && $hasKecamatan) {
        echo "  ✅ Template CSV sudah update dengan kolom lokasi\n";
    } else {
        echo "  ❌ Template CSV belum lengkap\n";
    }
} else {
    echo "  ❌ Template file tidak ditemukan\n";
}

// 2. Check sales_transactions Schema
echo "\n2. sales_transactions Table Schema:\n";
$columns = DB::select("SHOW COLUMNS FROM sales_transactions WHERE Field IN ('provinsi', 'kota', 'kecamatan', 'location_id')");
$foundColumns = array_column($columns, 'Field');

echo "  " . (in_array('provinsi', $foundColumns) ? "✓" : "✗") . " Kolom 'provinsi'\n";
echo "  " . (in_array('kota', $foundColumns) ? "✓" : "✗") . " Kolom 'kota'\n";
echo "  " . (in_array('kecamatan', $foundColumns) ? "✓" : "✗") . " Kolom 'kecamatan'\n";
echo "  " . (in_array('location_id', $foundColumns) ? "✓" : "✗") . " Kolom 'location_id'\n";

if (count($foundColumns) === 4) {
    echo "  ✅ sales_transactions sudah punya semua kolom lokasi\n";
} else {
    echo "  ❌ sales_transactions masih missing kolom: " . implode(', ', array_diff(['provinsi', 'kota', 'kecamatan', 'location_id'], $foundColumns)) . "\n";
}

// 3. Check Foreign Key
echo "\n3. Foreign Key Constraints:\n";
$fks = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'sales_transactions'
    AND COLUMN_NAME = 'location_id'
    AND REFERENCED_TABLE_NAME = 'dim_location'
    AND TABLE_SCHEMA = DATABASE()
");

if (!empty($fks)) {
    echo "  ✓ Foreign key 'location_id' → 'dim_location' exists\n";
    echo "  ✅ Referential integrity terjaga\n";
} else {
    echo "  ✗ Foreign key constraint tidak ditemukan\n";
    echo "  ❌ Perlu re-run migration\n";
}

// 4. Check staging_sales_items Schema
echo "\n4. staging_sales_items Table Schema:\n";
$stagingCols = DB::select("SHOW COLUMNS FROM staging_sales_items WHERE Field IN ('provinsi', 'kota', 'kecamatan', 'location_id')");
$foundStagingCols = array_column($stagingCols, 'Field');

echo "  " . (in_array('provinsi', $foundStagingCols) ? "✓" : "✗") . " Kolom 'provinsi'\n";
echo "  " . (in_array('kota', $foundStagingCols) ? "✓" : "✗") . " Kolom 'kota'\n";
echo "  " . (in_array('kecamatan', $foundStagingCols) ? "✓" : "✗") . " Kolom 'kecamatan'\n";
echo "  " . (in_array('location_id', $foundStagingCols) ? "✓" : "✗") . " Kolom 'location_id'\n";

if (count($foundStagingCols) === 4) {
    echo "  ✅ Staging table siap untuk capture location\n";
} else {
    echo "  ❌ Staging table missing kolom: " . implode(', ', array_diff(['provinsi', 'kota', 'kecamatan', 'location_id'], $foundStagingCols)) . "\n";
}

// 5. Check DataFeedService expectedHeaders
echo "\n5. DataFeedService Configuration:\n";
$servicePath = app_path('Services/DataFeedService.php');
$serviceContent = file_get_contents($servicePath);

$hasProvinsiInHeaders = strpos($serviceContent, "'provinsi'") !== false;
$hasKotaInHeaders = strpos($serviceContent, "'kota'") !== false;
$hasKecamatanInHeaders = strpos($serviceContent, "'kecamatan'") !== false;
$hasLocationService = strpos($serviceContent, 'OlapLocationService') !== false;

echo "  " . ($hasProvinsiInHeaders ? "✓" : "✗") . " 'provinsi' in expectedHeaders\n";
echo "  " . ($hasKotaInHeaders ? "✓" : "✗") . " 'kota' in expectedHeaders\n";
echo "  " . ($hasKecamatanInHeaders ? "✓" : "✗") . " 'kecamatan' in expectedHeaders\n";
echo "  " . ($hasLocationService ? "✓" : "✗") . " OlapLocationService integration\n";

if ($hasProvinsiInHeaders && $hasKotaInHeaders && $hasKecamatanInHeaders && $hasLocationService) {
    echo "  ✅ DataFeedService sudah terintegrasi dengan location\n";
} else {
    echo "  ⚠️  DataFeedService perlu review\n";
}

// 6. Check OlapWarehouseService
echo "\n6. OlapWarehouseService Configuration:\n";
$warehousePath = app_path('Services/OlapWarehouseService.php');
$warehouseContent = file_get_contents($warehousePath);

$hasLocationServiceImport = strpos($warehouseContent, 'use App\Services\Olap\OlapLocationService') !== false;
$hasLocationInConstructor = strpos($warehouseContent, 'OlapLocationService $locationService') !== false;
$hasLocationIdInInsert = strpos($warehouseContent, "'location_id'") !== false;

echo "  " . ($hasLocationServiceImport ? "✓" : "✗") . " OlapLocationService imported\n";
echo "  " . ($hasLocationInConstructor ? "✓" : "✗") . " locationService in constructor\n";
echo "  " . ($hasLocationIdInInsert ? "✓" : "✗") . " location_id in fact_sales insert\n";

if ($hasLocationServiceImport && $hasLocationInConstructor && $hasLocationIdInInsert) {
    echo "  ✅ OlapWarehouseService sudah support location sync\n";
} else {
    echo "  ⚠️  OlapWarehouseService perlu review\n";
}

// 7. Test Data Flow Simulation
echo "\n7. Data Flow Simulation:\n";
try {
    // Simulate location mapping
    $testProvinsi = 'DKI Jakarta';
    $testKota = 'Jakarta Pusat';
    $testKecamatan = 'Gambir';

    $location = DB::table('dim_location')
        ->where('provinsi', $testProvinsi)
        ->where('kota', $testKota)
        ->where('kecamatan', $testKecamatan)
        ->first();

    if ($location) {
        echo "  ✓ Test location found: {$testProvinsi} > {$testKota} > {$testKecamatan} (ID: {$location->id})\n";
        echo "  ✓ Location mapping siap digunakan\n";
        echo "  ✅ Data flow simulation passed\n";
    } else {
        echo "  ⚠️  Test location tidak ditemukan di dim_location\n";
        echo "  💡 Ini normal jika location akan di-create saat ETL\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// 8. Summary
echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY VERIFIKASI\n";
echo str_repeat("=", 70) . "\n";

$allChecks = [
    'CSV Template' => ($hasProvinsi ?? false) && ($hasKota ?? false) && ($hasKecamatan ?? false),
    'sales_transactions Schema' => count($foundColumns ?? []) === 4,
    'Foreign Key Constraint' => !empty($fks ?? []),
    'staging_sales_items Schema' => count($foundStagingCols ?? []) === 4,
    'DataFeedService Config' => ($hasProvinsiInHeaders ?? false) && ($hasLocationService ?? false),
    'OlapWarehouseService Config' => ($hasLocationServiceImport ?? false) && ($hasLocationIdInInsert ?? false),
];

$passed = array_filter($allChecks);
$total = count($allChecks);
$passedCount = count($passed);

echo "\nHasil: {$passedCount}/{$total} checks passed\n\n";

foreach ($allChecks as $check => $status) {
    echo ($status ? "✅" : "❌") . " {$check}\n";
}

if ($passedCount === $total) {
    echo "\n🎉 SEMUA VERIFIKASI PASSED! Integrasi lokasi complete.\n";
} else {
    echo "\n⚠️  Beberapa checks failed. Review dokumentasi untuk troubleshooting.\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "NEXT STEPS:\n";
echo "1. Test CSV import dengan template yang sudah update\n";
echo "2. Test manual sales input dengan location payload\n";
echo "3. Verify drill-down analytics include location dimension\n";
echo "4. (Optional) Update UI form jika ada transaction input form\n";
echo str_repeat("=", 70) . "\n";
