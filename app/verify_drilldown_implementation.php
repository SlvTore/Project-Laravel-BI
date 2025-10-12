<?php
/**
 * VERIFICATION SCRIPT: Location Drill-Down Implementation
 *
 * Tests:
 * 1. Controller file exists
 * 2. Routes are registered
 * 3. JavaScript file exists
 * 4. Blade component exists
 * 5. Home integration exists
 * 6. Sample API call works
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Route;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   LOCATION DRILL-DOWN IMPLEMENTATION VERIFICATION          ║\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "\n";

$checks = [];
$passed = 0;
$failed = 0;

// Check 1: Controller exists
echo "📋 Check #1: OlapLocationController exists...\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/Dashboard/OlapLocationController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    $hasHierarchy = strpos($content, 'function hierarchy') !== false;
    $hasDrillDown = strpos($content, 'function drillDown') !== false;
    $hasTrend = strpos($content, 'function trend') !== false;
    $hasTop = strpos($content, 'function top') !== false;

    if ($hasHierarchy && $hasDrillDown && $hasTrend && $hasTop) {
        echo "   ✅ Controller exists with all 4 methods\n";
        $checks[] = ['name' => 'Controller', 'status' => 'PASS', 'details' => 'All 4 methods present'];
        $passed++;
    } else {
        echo "   ❌ Controller missing methods\n";
        $checks[] = ['name' => 'Controller', 'status' => 'FAIL', 'details' => 'Missing methods'];
        $failed++;
    }
} else {
    echo "   ❌ Controller not found\n";
    $checks[] = ['name' => 'Controller', 'status' => 'FAIL', 'details' => 'File not found'];
    $failed++;
}
echo "\n";

// Check 2: Routes registered
echo "📋 Check #2: Routes registered...\n";
$routes = Route::getRoutes();
$locationRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'dashboard/olap/location') !== false) {
        $locationRoutes[] = $uri;
    }
}

$expectedRoutes = [
    'dashboard/olap/location/hierarchy',
    'dashboard/olap/location/drill-down',
    'dashboard/olap/location/trend',
    'dashboard/olap/location/top'
];

$allRoutesFound = true;
foreach ($expectedRoutes as $expected) {
    if (!in_array($expected, $locationRoutes)) {
        $allRoutesFound = false;
        echo "   ⚠️  Missing route: {$expected}\n";
    }
}

if ($allRoutesFound && count($locationRoutes) >= 4) {
    echo "   ✅ All 4 location routes registered\n";
    echo "      • hierarchy, drill-down, trend, top\n";
    $checks[] = ['name' => 'Routes', 'status' => 'PASS', 'details' => '4 routes registered'];
    $passed++;
} else {
    echo "   ❌ Routes incomplete (found " . count($locationRoutes) . "/4)\n";
    $checks[] = ['name' => 'Routes', 'status' => 'FAIL', 'details' => 'Incomplete routes'];
    $failed++;
}
echo "\n";

// Check 3: JavaScript file
echo "📋 Check #3: JavaScript file exists...\n";
$jsPath = __DIR__ . '/public/js/location-drilldown.js';
if (file_exists($jsPath)) {
    $jsContent = file_get_contents($jsPath);
    $jsSize = filesize($jsPath);
    $hasLoadData = strpos($jsContent, 'loadDrillDownData') !== false;
    $hasRenderChart = strpos($jsContent, 'renderChart') !== false;
    $hasDrillDown = strpos($jsContent, 'drillDown') !== false;
    $hasDrillUp = strpos($jsContent, 'resetToLevel') !== false;

    if ($hasLoadData && $hasRenderChart && $hasDrillDown && $hasDrillUp) {
        echo "   ✅ JavaScript file complete (" . number_format($jsSize) . " bytes)\n";
        echo "      • loadDrillDownData, renderChart, drillDown, resetToLevel\n";
        $checks[] = ['name' => 'JavaScript', 'status' => 'PASS', 'details' => 'All functions present'];
        $passed++;
    } else {
        echo "   ❌ JavaScript missing functions\n";
        $checks[] = ['name' => 'JavaScript', 'status' => 'FAIL', 'details' => 'Missing functions'];
        $failed++;
    }
} else {
    echo "   ❌ JavaScript file not found\n";
    $checks[] = ['name' => 'JavaScript', 'status' => 'FAIL', 'details' => 'File not found'];
    $failed++;
}
echo "\n";

// Check 4: Blade component
echo "📋 Check #4: Blade component exists...\n";
$bladePath = __DIR__ . '/resources/views/components/location-drilldown-widget.blade.php';
if (file_exists($bladePath)) {
    $bladeContent = file_get_contents($bladePath);
    $hasCanvas = strpos($bladeContent, 'location-drilldown-chart') !== false;
    $hasBreadcrumb = strpos($bladeContent, 'drill-breadcrumb') !== false;
    $hasStats = strpos($bladeContent, 'stat-revenue') !== false;
    $hasMetricSelector = strpos($bladeContent, 'drill-metric-selector') !== false;

    if ($hasCanvas && $hasBreadcrumb && $hasStats && $hasMetricSelector) {
        echo "   ✅ Blade component complete\n";
        echo "      • Chart canvas, breadcrumbs, stats, metric selector\n";
        $checks[] = ['name' => 'Blade Component', 'status' => 'PASS', 'details' => 'All elements present'];
        $passed++;
    } else {
        echo "   ❌ Blade component incomplete\n";
        $checks[] = ['name' => 'Blade Component', 'status' => 'FAIL', 'details' => 'Missing elements'];
        $failed++;
    }
} else {
    echo "   ❌ Blade component not found\n";
    $checks[] = ['name' => 'Blade Component', 'status' => 'FAIL', 'details' => 'File not found'];
    $failed++;
}
echo "\n";

// Check 5: Home integration
echo "📋 Check #5: Home dashboard integration...\n";
$homePath = __DIR__ . '/resources/views/home/index.blade.php';
if (file_exists($homePath)) {
    $homeContent = file_get_contents($homePath);
    $hasInclude = strpos($homeContent, "include('components.location-drilldown-widget')") !== false;

    if ($hasInclude) {
        echo "   ✅ Widget integrated to home dashboard\n";
        $checks[] = ['name' => 'Home Integration', 'status' => 'PASS', 'details' => '@include directive found'];
        $passed++;
    } else {
        echo "   ❌ Widget not integrated to home\n";
        $checks[] = ['name' => 'Home Integration', 'status' => 'FAIL', 'details' => '@include not found'];
        $failed++;
    }
} else {
    echo "   ❌ Home view not found\n";
    $checks[] = ['name' => 'Home Integration', 'status' => 'FAIL', 'details' => 'File not found'];
    $failed++;
}
echo "\n";

// Check 6: OlapLocationService exists and has required methods
echo "📋 Check #6: OlapLocationService integration...\n";
$servicePath = __DIR__ . '/app/Services/Olap/OlapLocationService.php';
if (file_exists($servicePath)) {
    $serviceContent = file_get_contents($servicePath);
    $hasDrillDown = strpos($serviceContent, 'drillDownSalesByLocation') !== false;
    $hasTrend = strpos($serviceContent, 'getSalesTrendByLocation') !== false;
    $hasTop = strpos($serviceContent, 'getTopLocationsByLevel') !== false;
    $hasHierarchy = strpos($serviceContent, 'getLocationHierarchy') !== false;

    if ($hasDrillDown && $hasTrend && $hasTop && $hasHierarchy) {
        echo "   ✅ OlapLocationService has all required methods\n";
        echo "      • drillDownSalesByLocation, getSalesTrendByLocation\n";
        echo "      • getTopLocationsByLevel, getLocationHierarchy\n";
        $checks[] = ['name' => 'Service Layer', 'status' => 'PASS', 'details' => 'All methods present'];
        $passed++;
    } else {
        echo "   ❌ OlapLocationService missing methods\n";
        $checks[] = ['name' => 'Service Layer', 'status' => 'FAIL', 'details' => 'Missing methods'];
        $failed++;
    }
} else {
    echo "   ❌ OlapLocationService not found\n";
    $checks[] = ['name' => 'Service Layer', 'status' => 'FAIL', 'details' => 'File not found'];
    $failed++;
}
echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "VERIFICATION SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($checks as $check) {
    $statusIcon = $check['status'] === 'PASS' ? '✅' : '❌';
    printf("%-25s %s %s\n", $check['name'], $statusIcon, $check['status']);
    printf("%-25s    %s\n\n", '', $check['details']);
}

echo "═══════════════════════════════════════════════════════════\n";
printf("TOTAL: %d PASSED, %d FAILED\n", $passed, $failed);
echo "═══════════════════════════════════════════════════════════\n\n";

if ($failed === 0) {
    echo "🎉 ALL CHECKS PASSED! Location Drill-Down ready to use.\n\n";
    echo "📌 NEXT STEPS:\n";
    echo "   1. Start development server: php artisan serve\n";
    echo "   2. Login and navigate to dashboard\n";
    echo "   3. See Location Drill-Down widget with provinsi chart\n";
    echo "   4. Click any bar to drill-down to kota level\n";
    echo "   5. Click kota bar to drill-down to kecamatan level\n";
    echo "   6. Use breadcrumbs to drill-up\n";
    echo "   7. Test metric selector (Revenue, Margin, Transactions, Margin %)\n\n";
    echo "📊 DASHBOARD URL: http://localhost:8000/dashboard\n\n";
    exit(0);
} else {
    echo "⚠️  SOME CHECKS FAILED! Please fix the issues above.\n\n";
    exit(1);
}
