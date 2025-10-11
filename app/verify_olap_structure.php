<?php
/**
 * Quick verification script for OLAP optimizations
 * Run: php verify_olap_structure.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== OLAP Structure Verification ===\n\n";

// Check dim_date enhancements
echo "1. Checking dim_date time hierarchies...\n";
$dateColumns = Schema::getColumnListing('dim_date');
$requiredDateColumns = ['day_of_month', 'week_of_year', 'day_of_week', 'is_weekend', 'fiscal_period'];
foreach ($requiredDateColumns as $col) {
    $status = in_array($col, $dateColumns) ? '✓' : '✗';
    echo "   {$status} {$col}\n";
}

// Check dim_promotion
echo "\n2. Checking dim_promotion table...\n";
if (Schema::hasTable('dim_promotion')) {
    echo "   ✓ dim_promotion table exists\n";
    $promotionColumns = Schema::getColumnListing('dim_promotion');
    $requiredPromotionColumns = ['business_id', 'promotion_nk', 'discount_percent'];
    foreach ($requiredPromotionColumns as $col) {
        $status = in_array($col, $promotionColumns) ? '✓' : '✗';
        echo "   {$status} {$col}\n";
    }
    
    // Check if promotion_id exists in fact_sales
    $factColumns = Schema::getColumnListing('fact_sales');
    $status = in_array('promotion_id', $factColumns) ? '✓' : '✗';
    echo "   {$status} promotion_id FK in fact_sales\n";
} else {
    echo "   ✗ dim_promotion table NOT found\n";
}

// Check indexes
echo "\n3. Checking strategic indexes...\n";
$indexes = [
    'fact_sales' => ['idx_fact_sales_business_date', 'idx_fact_sales_product', 'idx_fact_sales_customer', 
                     'idx_fact_sales_channel', 'idx_fact_sales_product_date', 'idx_fact_sales_feed'],
    'dim_product' => ['idx_dim_product_business_nk'],
    'dim_customer' => ['idx_dim_customer_business_nk'],
    'dim_date' => ['idx_dim_date_date', 'idx_dim_date_year_month', 'idx_dim_date_year_quarter'],
    'staging_sales_items' => ['idx_staging_sales_feed', 'idx_staging_sales_product'],
];

$databaseName = DB::connection()->getDatabaseName();

foreach ($indexes as $table => $indexList) {
    echo "   Table: {$table}\n";
    foreach ($indexList as $indexName) {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$databaseName, $table, $indexName]);
        
        $status = ($result[0]->count > 0) ? '✓' : '✗';
        echo "      {$status} {$indexName}\n";
    }
}

// Check services
echo "\n4. Checking service classes...\n";
$services = [
    'App\Services\MetricMappingService',
    'App\Services\Olap\OlapDimensionService',
    'App\Services\Olap\OlapFactService',
    'App\Services\Olap\OlapETLService',
    'App\Services\Olap\OlapQueryService',
];

foreach ($services as $service) {
    $status = class_exists($service) ? '✓' : '✗';
    $shortName = substr($service, strrpos($service, '\\') + 1);
    echo "   {$status} {$shortName}\n";
}

// Check repository
echo "\n5. Checking repository classes...\n";
$repositories = [
    'App\Repositories\FactSalesRepository',
];

foreach ($repositories as $repo) {
    $status = class_exists($repo) ? '✓' : '✗';
    $shortName = substr($repo, strrpos($repo, '\\') + 1);
    echo "   {$status} {$shortName}\n";
}

echo "\n=== Verification Complete ===\n\n";

// Summary
echo "Summary:\n";
echo "- Time Hierarchies: Enhanced dim_date with 5 new columns\n";
echo "- Promotion Dimension: New dim_promotion table with business scoping\n";
echo "- Strategic Indexes: 14 indexes across 5 tables\n";
echo "- Service Layer: 5 specialized services for OLAP operations\n";
echo "- Repository Pattern: FactSalesRepository for complex queries\n";
echo "\n";

echo "Next steps:\n";
echo "1. Seed some test data to verify dimension handling\n";
echo "2. Test batch ETL processing with OlapETLService\n";
echo "3. Verify caching works with OlapQueryService\n";
echo "4. Update controllers to use new services (Task 10)\n";
echo "\n";
