<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking vw_margin_daily structure ===\n";
try {
    $data = DB::table('vw_margin_daily')
        ->where('business_id', 1)
        ->orderBy('sales_date', 'desc')
        ->take(3)
        ->get();

    if ($data->count() > 0) {
        echo "Columns available: " . implode(', ', array_keys((array)$data->first())) . "\n";
        foreach ($data as $row) {
            echo "Date: {$row->sales_date}, Margin: {$row->total_margin}\n";
        }
    } else {
        echo "No data found in vw_margin_daily\n";
    }

    echo "\n=== Checking if margin is percentage or amount ===\n";
    $sample = DB::table('vw_margin_daily')
        ->where('business_id', 1)
        ->where('total_margin', '>', 0)
        ->first();

    if ($sample) {
        echo "Sample margin value: {$sample->total_margin}\n";
        echo "This appears to be: " . ($sample->total_margin > 1 && $sample->total_margin < 100 ? "percentage" : "amount") . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
