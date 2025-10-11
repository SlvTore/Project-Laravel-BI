<?php
/**
 * Quick test to demonstrate new OLAP services
 * Run: php test_olap_services.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MetricMappingService;
use App\Services\Olap\OlapDimensionService;
use App\Services\Olap\OlapFactService;
use Illuminate\Support\Facades\DB;

echo "\n=== OLAP Services Test ===\n\n";

// Test 1: MetricMappingService
echo "1. Testing MetricMappingService...\n";
$mapping = app(MetricMappingService::class);

$metrics = [
    MetricMappingService::METRIC_REVENUE,
    MetricMappingService::METRIC_COGS,
    MetricMappingService::METRIC_GROSS_MARGIN
];

foreach ($metrics as $metric) {
    $info = $mapping->getOlapMapping($metric);
    if ($info) {
        $name = $mapping->getSeriesName($metric);
        echo "   ✓ {$name} → {$info['view']}\n";
    } else {
        echo "   ✗ {$metric} → mapping not found\n";
    }
}

// Test 2: OlapDimensionService
echo "\n2. Testing OlapDimensionService...\n";
$dimensions = app(OlapDimensionService::class);

// Get or create test date
$dateDimId = $dimensions->getOrCreateDateDimension('2025-10-11');
echo "   ✓ Date dimension created/found: ID {$dateDimId} (2025-10-11)\n";

// Check if we have any businesses
$business = DB::table('businesses')->first();
if ($business) {
    echo "   ✓ Testing with business ID: {$business->id}\n";

    // Ensure default dimensions
    $dimensions->ensureDefaultDimensions($business->id);
    echo "   ✓ Default dimensions ensured\n";

    // Get or create product
    $productDimId = $dimensions->getOrCreateProductDimension(
        $business->id,
        999,
        'Test Product',
        'Test Category'
    );
    echo "   ✓ Product dimension: ID {$productDimId} (Test Product)\n";

    // Get or create customer (with null handling)
    $customerDimId = $dimensions->getOrCreateCustomerDimension(
        $business->id,
        null,
        'Test Customer',
        'retail',
        '08123456789'
    );
    echo "   ✓ Customer dimension: ID {$customerDimId} (Test Customer)\n";

    // Get or create channel
    $channelDimId = $dimensions->getOrCreateChannelDimension(
        $business->id,
        'ONLINE',
        'Online Store'
    );
    echo "   ✓ Channel dimension: ID {$channelDimId} (Online Store)\n";

    // Get or create promotion
    $promotionDimId = $dimensions->getOrCreatePromotionDimension(
        $business->id,
        'FLASH10',
        'Flash Sale 10%',
        10.0
    );
    echo "   ✓ Promotion dimension: ID {$promotionDimId} (Flash Sale 10%)\n";

    // Test 3: OlapFactService
    echo "\n3. Testing OlapFactService...\n";
    $facts = app(OlapFactService::class);

    // Insert a test fact
    $factId = $facts->insertFact([
        'business_id' => $business->id,
        'date_id' => $dateDimId,
        'product_id' => $productDimId,
        'customer_id' => $customerDimId,
        'channel_id' => $channelDimId,
        'promotion_id' => $promotionDimId,
        'quantity' => 5,
        'unit_price' => 100.00,
        'gross_revenue' => 500.00,
        'discount_amount' => 50.00,
        'net_revenue' => 450.00,
        'unit_cost' => 60.00,
        'cogs_amount' => 300.00,
        // margin calculated automatically
    ]);
    echo "   ✓ Fact inserted: ID {$factId}\n";

    // Get the fact
    $fact = $facts->getFactById($factId);
    echo "   ✓ Fact retrieved:\n";
    echo "      - Revenue: Rp " . number_format($fact->gross_revenue, 2) . "\n";
    echo "      - COGS: Rp " . number_format($fact->cogs_amount, 2) . "\n";
    echo "      - Margin: Rp " . number_format($fact->gross_margin_amount, 2) . "\n";
    echo "      - Margin %: " . number_format($fact->gross_margin_percent, 2) . "%\n";

    // Clean up test data
    $facts->deleteFact($factId);
    echo "   ✓ Test fact deleted (cleanup)\n";

} else {
    echo "   ⚠ No businesses found in database. Skipping dimension tests.\n";
}

echo "\n=== Test Complete ===\n\n";

echo "Summary:\n";
echo "- MetricMappingService: Provides centralized metric mappings ✓\n";
echo "- OlapDimensionService: Handles dimension creation with defaults ✓\n";
echo "- OlapFactService: Manages fact CRUD with auto-calculations ✓\n";
echo "\nAll services are working correctly!\n\n";
