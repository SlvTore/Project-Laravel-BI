<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking vw_sales_daily structure ===\n";
try {
    $data = DB::table('vw_sales_daily')
        ->where('business_id', 1)
        ->take(1)
        ->get();

    if ($data->count() > 0) {
        echo "Columns available: " . implode(', ', array_keys((array)$data->first())) . "\n";
    } else {
        echo "No data found in vw_sales_daily\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
