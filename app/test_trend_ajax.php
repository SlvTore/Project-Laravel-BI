<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BusinessMetric;
use App\Http\Controllers\Dashboard\MetricRecordsController;
use App\Services\OlapMetricAggregator;
use Illuminate\Http\Request;

echo "=== Testing Trend Analysis AJAX Endpoint ===\n";

try {
    // Find a business metric to test with
    $businessMetric = BusinessMetric::first();

    if (!$businessMetric) {
        echo "No business metrics found to test with\n";
        exit;
    }

    echo "Testing with metric: {$businessMetric->metric_name} (ID: {$businessMetric->id})\n";

    // Create a controller instance with required dependency
    $aggregator = new OlapMetricAggregator();
    $controller = new MetricRecordsController($aggregator);

    // Test different periods
    $periods = [7, 30, 90];

    foreach ($periods as $period) {
        echo "\nTesting {$period} days period:\n";

        // Create a mock request with period parameter
        $request = Request::create(
            route('dashboard.metrics.records.edit', $businessMetric->id),
            'GET',
            ['period' => $period]
        );
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        try {
            $response = $controller->editPage($businessMetric, $request);
            $data = $response->getData(true);

            if (isset($data['chartData'])) {
                $chartData = $data['chartData'];
                echo "- Success! Chart data has " . count($chartData['values']) . " data points\n";
                echo "- Date range: " . (count($chartData['dates']) > 0 ? $chartData['dates'][0] . ' to ' . end($chartData['dates']) : 'No dates') . "\n";
                echo "- Sample values: " . implode(', ', array_slice($chartData['values']->toArray(), 0, 3)) . "...\n";
            } else {
                echo "- Error: No chartData in response\n";
            }
        } catch (Exception $e) {
            echo "- Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
