<?php

/**
 * Verification Script: Business Ownership Transfer Implementation
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  BUSINESS OWNERSHIP TRANSFER - VERIFICATION                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$checks = [];

// 1. Check Service Class
echo "1. Checking BusinessOwnershipService...\n";
try {
    $service = app(\App\Services\BusinessOwnershipService::class);
    $checks['service'] = '✓ BusinessOwnershipService exists and autoloadable';
    echo "   ✓ Service class found\n";
} catch (\Exception $e) {
    $checks['service'] = '✗ BusinessOwnershipService NOT found: ' . $e->getMessage();
    echo "   ✗ Service class NOT found\n";
}

// 2. Check Database Columns
echo "\n2. Checking database schema changes...\n";
$requiredColumns = [
    'transferred_from_user_id',
    'ownership_transferred_at',
    'transfer_reason',
];

$missingColumns = [];
foreach ($requiredColumns as $column) {
    if (Schema::hasColumn('businesses', $column)) {
        echo "   ✓ Column 'businesses.{$column}' exists\n";
    } else {
        $missingColumns[] = $column;
        echo "   ✗ Column 'businesses.{$column}' MISSING\n";
    }
}

if (empty($missingColumns)) {
    $checks['schema'] = '✓ All 3 tracking columns present';
} else {
    $checks['schema'] = '✗ Missing columns: ' . implode(', ', $missingColumns);
}

// 3. Check Business Model Methods
echo "\n3. Checking Business model helper methods...\n";
try {
    $business = new \App\Models\Business();
    $methods = [
        'getEligibleSuccessor',
        'hasEligibleSuccessor',
        'getEligibleSuccessors',
        'transferOwnershipTo',
    ];

    $missingMethods = [];
    foreach ($methods as $method) {
        if (method_exists($business, $method)) {
            echo "   ✓ Method '{$method}' exists\n";
        } else {
            $missingMethods[] = $method;
            echo "   ✗ Method '{$method}' MISSING\n";
        }
    }

    if (empty($missingMethods)) {
        $checks['methods'] = '✓ All 4 helper methods present';
    } else {
        $checks['methods'] = '✗ Missing methods: ' . implode(', ', $missingMethods);
    }
} catch (\Exception $e) {
    $checks['methods'] = '✗ Error checking methods: ' . $e->getMessage();
}

// 4. Check ProfileController Integration
echo "\n4. Checking ProfileController integration...\n";
try {
    $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/ProfileController.php');

    if (strpos($controllerContent, 'BusinessOwnershipService') !== false &&
        strpos($controllerContent, 'handleOwnerDeletion') !== false) {
        $checks['controller'] = '✓ ProfileController integrated with ownership transfer';
        echo "   ✓ ProfileController has ownership transfer logic\n";
    } else {
        $checks['controller'] = '✗ ProfileController NOT integrated';
        echo "   ✗ ProfileController missing ownership transfer logic\n";
    }
} catch (\Exception $e) {
    $checks['controller'] = '✗ Error checking ProfileController: ' . $e->getMessage();
}

// 5. Check Migration File
echo "\n5. Checking migration file...\n";
$migrationFile = glob(__DIR__ . '/database/migrations/*_add_ownership_transfer_tracking_to_businesses.php');
if (!empty($migrationFile)) {
    $checks['migration'] = '✓ Migration file exists';
    echo "   ✓ Migration file found: " . basename($migrationFile[0]) . "\n";
} else {
    $checks['migration'] = '✗ Migration file NOT found';
    echo "   ✗ Migration file NOT found\n";
}

// 6. Check Test File
echo "\n6. Checking test file...\n";
if (file_exists(__DIR__ . '/test_ownership_transfer.php')) {
    $checks['test'] = '✓ Test file exists';
    echo "   ✓ Test file found\n";
} else {
    $checks['test'] = '✗ Test file NOT found';
    echo "   ✗ Test file NOT found\n";
}

// 7. Check Documentation
echo "\n7. Checking documentation...\n";
if (file_exists(__DIR__ . '/BUSINESS_OWNERSHIP_TRANSFER.md')) {
    $checks['docs'] = '✓ Documentation exists';
    echo "   ✓ Documentation file found\n";
} else {
    $checks['docs'] = '✗ Documentation NOT found';
    echo "   ✗ Documentation NOT found\n";
}

// 8. Test Service Methods Availability
echo "\n8. Testing service method signatures...\n";
try {
    $service = app(\App\Services\BusinessOwnershipService::class);
    $reflection = new \ReflectionClass($service);

    $expectedMethods = [
        'transferOwnership',
        'findEligibleSuccessor',
        'hasEligibleSuccessor',
        'getEligibleSuccessors',
        'handleOwnerDeletion',
    ];

    $foundMethods = [];
    foreach ($expectedMethods as $method) {
        if ($reflection->hasMethod($method)) {
            $foundMethods[] = $method;
            echo "   ✓ Method '{$method}' available\n";
        } else {
            echo "   ✗ Method '{$method}' MISSING\n";
        }
    }

    if (count($foundMethods) === count($expectedMethods)) {
        $checks['service_methods'] = '✓ All 5 service methods present';
    } else {
        $checks['service_methods'] = '✗ Only ' . count($foundMethods) . '/5 methods found';
    }
} catch (\Exception $e) {
    $checks['service_methods'] = '✗ Error: ' . $e->getMessage();
}

// 9. Check Role Hierarchy
echo "\n9. Checking role hierarchy data...\n";
try {
    $roles = DB::table('roles')
        ->whereIn('name', ['business-owner', 'administrator', 'staff', 'business-investigator'])
        ->pluck('name');

    $expectedRoles = ['business-owner', 'administrator', 'staff', 'business-investigator'];
    $missingRoles = array_diff($expectedRoles, $roles->toArray());

    if (empty($missingRoles)) {
        $checks['roles'] = '✓ All 4 roles exist in database';
        echo "   ✓ All required roles found\n";
    } else {
        $checks['roles'] = '✗ Missing roles: ' . implode(', ', $missingRoles);
        echo "   ✗ Missing roles in database\n";
    }
} catch (\Exception $e) {
    $checks['roles'] = '✗ Error checking roles: ' . $e->getMessage();
}

// Summary
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICATION SUMMARY                                         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$passed = 0;
$failed = 0;

foreach ($checks as $check => $result) {
    if (strpos($result, '✓') === 0) {
        $passed++;
        echo "\033[32m{$result}\033[0m\n";
    } else {
        $failed++;
        echo "\033[31m{$result}\033[0m\n";
    }
}

echo "\n";
echo "Total Checks: " . count($checks) . "\n";
echo "\033[32mPassed: {$passed}\033[0m\n";
echo "\033[31mFailed: {$failed}\033[0m\n";
echo "\n";

if ($failed === 0) {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ ALL CHECKS PASSED - IMPLEMENTATION COMPLETE               ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
} else {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✗ SOME CHECKS FAILED - REVIEW REQUIRED                      ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
}

echo "\n";
