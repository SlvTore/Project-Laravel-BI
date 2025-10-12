<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING FACT_SALES DATES ===\n\n";

$dates = DB::table('fact_sales')
    ->join('dim_date', 'fact_sales.date_id', '=', 'dim_date.id')
    ->select('dim_date.date', DB::raw('COUNT(*) as count'))
    ->groupBy('dim_date.date')
    ->orderBy('dim_date.date')
    ->get();

echo "Dates in fact_sales:\n";
foreach ($dates as $d) {
    echo "  {$d->date}: {$d->count} records\n";
}

echo "\n=== END ===\n";
