<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING OLAP VIEWS ===\n\n";

// Check which views exist
$views = [
    'vw_sales_daily',
    'vw_sales_product_daily',
    'vw_cogs_daily',
    'vw_margin_daily',
    'vw_new_customers_daily',
    'vw_returning_customers_daily',
];

foreach ($views as $view) {
    try {
        $count = DB::table($view)->count();
        echo "✅ {$view}: {$count} records\n";

        if ($count > 0) {
            $sample = DB::table($view)->first();
            echo "   Sample: " . json_encode($sample) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$view}: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Check fact_sales
echo "\n📊 Fact Sales: ";
try {
    $factCount = DB::table('fact_sales')->count();
    echo "{$factCount} records\n";

    if ($factCount > 0) {
        $sample = DB::table('fact_sales')->first();
        echo "   Sample: " . json_encode($sample) . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== END ===\n";
