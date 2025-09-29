<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\OlapMetricAggregator;

echo "=== Testing updated dailyMargin method ===\n";

$aggregator = new OlapMetricAggregator();

try {
    $marginData = $aggregator->dailyMargin(1, 7); // Last 7 days for business_id 1

    echo "Sample margin percentages for last 7 days:\n";
    foreach ($marginData['dates'] as $index => $date) {
        $label = $marginData['labels'][$index];
        $value = $marginData['values'][$index];
        echo "Date: {$date} ({$label}), Margin: {$value}%\n";
    }

    echo "\nValues range from: " . min($marginData['values']->toArray()) . "% to " . max($marginData['values']->toArray()) . "%\n";
    echo "These should now be percentages between 0-100%\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
