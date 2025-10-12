<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

echo "=== TESTING SALES TRANSACTION API ===\n\n";

// Simulate POST request to /api/sales-transactions
$testData = [
    'transaction_date' => '2024-01-18',
    'customer_name' => 'Test Customer',
    'customer_phone' => '08123456789',
    'customer_email' => 'test@example.com',
    'provinsi' => 'DKI Jakarta',
    'kota' => 'Jakarta Selatan',
    'kecamatan' => 'Kebayoran Baru',
    'items' => [
        [
            'product_name' => 'Test Product',
            'quantity' => 2,
            'selling_price' => 50000,
            'discount' => 5000,
        ]
    ],
    'tax_amount' => 9500,
    'shipping_cost' => 10000,
    'notes' => 'Test transaction',
];

echo "Test data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Check if route exists
$routes = Route::getRoutes();
$found = false;

foreach ($routes as $route) {
    if ($route->uri() === 'api/sales-transactions' && in_array('POST', $route->methods())) {
        $found = true;
        echo "✅ Route found: POST /api/sales-transactions\n";
        echo "   Controller: " . $route->getActionName() . "\n";
        break;
    }
}

if (!$found) {
    echo "❌ Route NOT found: POST /api/sales-transactions\n";
}

echo "\n=== END ===\n";
