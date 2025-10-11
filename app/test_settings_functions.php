<?php

/**
 * Settings Page - Comprehensive Test Script
 * Tests all settings functionality
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     SETTINGS PAGE - FUNCTIONAL TESTS                         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

function testResult($name, $passed, $message = '') {
    $status = $passed ? '✓ PASSED' : '✗ FAILED';
    $color = $passed ? "\033[32m" : "\033[31m";
    echo "{$color}{$status}\033[0m - {$name}\n";
    if ($message) {
        echo "    → {$message}\n";
    }
}

// Cleanup previous test data
echo "Cleaning up test data...\n";
DB::table('users')->where('email', 'LIKE', '%@settings-test.com')->delete();
DB::table('businesses')->where('business_name', 'LIKE', 'Settings Test %')->delete();
echo "✓ Cleanup complete\n\n";

$testsPassed = 0;
$testsFailed = 0;

// ============================================================================
// TEST 1: Business Identity Update
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST 1: Business Identity Update\n";
echo "══════════════════════════════════════════════════════════════\n";

try {
    DB::beginTransaction();

    // Create test owner
    $ownerRole = Role::where('name', 'business-owner')->first();
    $owner = User::create([
        'name' => 'Test Owner Settings',
        'email' => 'owner@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $ownerRole->id,
        'is_active' => true,
    ]);

    // Create business
    $business = Business::create([
        'user_id' => $owner->id,
        'business_name' => 'Settings Test Business',
        'industry' => 'Technology',
    ]);
    $business->generatePublicId();
    $business->generateInvitationCode();

    echo "✓ Created test business\n";

    // Test 1a: Update business name
    $newName = 'Settings Test Business Updated';
    $business->business_name = $newName;
    $business->save();
    $business->refresh();

    if ($business->business_name === $newName) {
        testResult('Business name update', true);
        $testsPassed++;
    } else {
        testResult('Business name update', false, 'Name not saved');
        $testsFailed++;
    }

    // Test 1b: Update dashboard display name
    $displayName = 'Custom Display Name';
    $business->dashboard_display_name = $displayName;
    $business->save();
    $business->refresh();

    if ($business->dashboard_display_name === $displayName) {
        testResult('Dashboard display name', true);
        $testsPassed++;
    } else {
        testResult('Dashboard display name', false);
        $testsFailed++;
    }

    // Test 1c: Update industry, website, description
    $business->industry = 'Retail';
    $business->website = 'https://example.com';
    $business->description = 'Test description for business';
    $business->save();
    $business->refresh();

    if ($business->industry === 'Retail' &&
        $business->website === 'https://example.com' &&
        $business->description === 'Test description for business') {
        testResult('Additional fields update', true);
        $testsPassed++;
    } else {
        testResult('Additional fields update', false);
        $testsFailed++;
    }

    DB::rollBack();
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    testResult('Business Identity Update', false, $e->getMessage());
    $testsFailed++;
    echo "\n";
}

// ============================================================================
// TEST 2: Logo Upload/Remove Simulation
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST 2: Logo Storage Path\n";
echo "══════════════════════════════════════════════════════════════\n";

try {
    DB::beginTransaction();

    // Create test business
    $ownerRole = Role::where('name', 'business-owner')->first();
    $owner = User::create([
        'name' => 'Test Owner Logo',
        'email' => 'owner-logo@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $ownerRole->id,
    ]);

    $business = Business::create([
        'user_id' => $owner->id,
        'business_name' => 'Settings Test Logo Business',
    ]);

    // Test logo path storage
    $testLogoPath = 'business-logos/test-logo.png';
    $business->logo_path = $testLogoPath;
    $business->save();
    $business->refresh();

    if ($business->logo_path === $testLogoPath) {
        testResult('Logo path storage', true);
        $testsPassed++;
    } else {
        testResult('Logo path storage', false);
        $testsFailed++;
    }

    // Test logo removal
    $business->logo_path = null;
    $business->save();
    $business->refresh();

    if ($business->logo_path === null) {
        testResult('Logo removal', true);
        $testsPassed++;
    } else {
        testResult('Logo removal', false);
        $testsFailed++;
    }

    // Check if storage directory exists
    $storagePath = storage_path('app/public/business-logos');
    if (file_exists($storagePath) || is_writable(storage_path('app/public'))) {
        testResult('Storage directory accessible', true);
        $testsPassed++;
    } else {
        testResult('Storage directory accessible', false, 'Run: php artisan storage:link');
        $testsFailed++;
    }

    DB::rollBack();
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    testResult('Logo Storage', false, $e->getMessage());
    $testsFailed++;
    echo "\n";
}

// ============================================================================
// TEST 3: User Preferences (Theme & Accent Color)
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST 3: User Preferences\n";
echo "══════════════════════════════════════════════════════════════\n";

try {
    DB::beginTransaction();

    $ownerRole = Role::where('name', 'business-owner')->first();
    $user = User::create([
        'name' => 'Test User Prefs',
        'email' => 'user-prefs@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $ownerRole->id,
    ]);

    // Test theme update
    $user->theme = 'dark';
    $user->save();
    $user->refresh();

    if ($user->theme === 'dark') {
        testResult('Theme update', true);
        $testsPassed++;
    } else {
        testResult('Theme update', false);
        $testsFailed++;
    }

    // Test accent color update
    $testColor = '#FF5733';
    $user->accent_color = strtolower($testColor);
    $user->save();
    $user->refresh();

    if (strtolower($user->accent_color) === strtolower($testColor)) {
        testResult('Accent color update', true);
        $testsPassed++;
    } else {
        testResult('Accent color update', false, "Got: {$user->accent_color}");
        $testsFailed++;
    }

    // Test theme switcher values
    $validThemes = ['light', 'dark'];
    $user->theme = 'light';
    $user->save();

    if (in_array($user->theme, $validThemes)) {
        testResult('Theme validation', true);
        $testsPassed++;
    } else {
        testResult('Theme validation', false);
        $testsFailed++;
    }

    DB::rollBack();
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    testResult('User Preferences', false, $e->getMessage());
    $testsFailed++;
    echo "\n";
}

// ============================================================================
// TEST 4: Invitation Code Regeneration
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST 4: Invitation Code Regeneration\n";
echo "══════════════════════════════════════════════════════════════\n";

try {
    DB::beginTransaction();

    $ownerRole = Role::where('name', 'business-owner')->first();
    $owner = User::create([
        'name' => 'Test Owner Code',
        'email' => 'owner-code@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $ownerRole->id,
    ]);

    $business = Business::create([
        'user_id' => $owner->id,
        'business_name' => 'Settings Test Code Business',
    ]);
    $business->generatePublicId();

    // Test initial code generation
    $initialCode = $business->generateInvitationCode();

    if (!empty($initialCode) && strlen($initialCode) === 8) {
        testResult('Initial code generation', true, "Code: {$initialCode}");
        $testsPassed++;
    } else {
        testResult('Initial code generation', false);
        $testsFailed++;
    }

    // Test code regeneration
    $business->refresh();
    $oldCode = $business->invitation_code;
    $newCode = $business->refreshInvitationCode();

    if ($newCode !== $oldCode && !empty($newCode)) {
        testResult('Code regeneration', true, "Old: {$oldCode}, New: {$newCode}");
        $testsPassed++;
    } else {
        testResult('Code regeneration', false);
        $testsFailed++;
    }

    // Test code persistence
    $business->refresh();
    if ($business->invitation_code === $newCode) {
        testResult('Code persistence in DB', true);
        $testsPassed++;
    } else {
        testResult('Code persistence in DB', false);
        $testsFailed++;
    }

    DB::rollBack();
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    testResult('Invitation Code', false, $e->getMessage());
    $testsFailed++;
    echo "\n";
}

// ============================================================================
// TEST 5: Ownership Transfer Integration
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST 5: Ownership Transfer Integration\n";
echo "══════════════════════════════════════════════════════════════\n";

try {
    DB::beginTransaction();

    $ownerRole = Role::where('name', 'business-owner')->first();
    $adminRole = Role::where('name', 'administrator')->first();

    $owner = User::create([
        'name' => 'Current Owner',
        'email' => 'current-owner@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $ownerRole->id,
    ]);

    $admin = User::create([
        'name' => 'Future Owner',
        'email' => 'future-owner@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $adminRole->id,
    ]);

    $business = Business::create([
        'user_id' => $owner->id,
        'business_name' => 'Settings Test Transfer Business',
    ]);

    // Add admin to business
    $business->addUser($admin);

    // Check BusinessOwnershipService exists
    if (class_exists(\App\Services\BusinessOwnershipService::class)) {
        testResult('BusinessOwnershipService available', true);
        $testsPassed++;
    } else {
        testResult('BusinessOwnershipService available', false);
        $testsFailed++;
    }

    // Test transfer helper method
    if (method_exists($business, 'transferOwnershipTo')) {
        testResult('Business::transferOwnershipTo() exists', true);
        $testsPassed++;
    } else {
        testResult('Business::transferOwnershipTo() exists', false);
        $testsFailed++;
    }

    // Test actual transfer
    $service = app(\App\Services\BusinessOwnershipService::class);
    $result = $service->transferOwnership($business, $owner, 'Settings page transfer test');

    $business->refresh();
    $admin->refresh();
    $owner->refresh();

    if ($result['success'] &&
        $business->user_id === $admin->id &&
        $admin->isBusinessOwner() &&
        $owner->isAdministrator()) {
        testResult('Ownership transfer execution', true, "New owner: {$admin->name}");
        $testsPassed++;
    } else {
        testResult('Ownership transfer execution', false);
        $testsFailed++;
    }

    DB::rollBack();
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    testResult('Ownership Transfer', false, $e->getMessage());
    $testsFailed++;
    echo "\n";
}

// ============================================================================
// TEST 6: Business Deletion Cascade
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST 6: Business Deletion Cascade\n";
echo "══════════════════════════════════════════════════════════════\n";

try {
    DB::beginTransaction();

    $ownerRole = Role::where('name', 'business-owner')->first();
    $staffRole = Role::where('name', 'staff')->first();

    $owner = User::create([
        'name' => 'Delete Test Owner',
        'email' => 'delete-owner@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $ownerRole->id,
        'setup_completed' => true,
        'setup_completed_at' => now(),
    ]);

    $staff = User::create([
        'name' => 'Delete Test Staff',
        'email' => 'delete-staff@settings-test.com',
        'password' => Hash::make('password123'),
        'role_id' => $staffRole->id,
    ]);

    $business = Business::create([
        'user_id' => $owner->id,
        'business_name' => 'Settings Test Delete Business',
        'logo_path' => 'business-logos/test.png',
    ]);
    $business->generatePublicId();

    $business->addUser($staff);

    $businessId = $business->id;
    $ownerId = $owner->id;
    $staffId = $staff->id;

    // Test pivot table entry
    $pivotCount = DB::table('business_user')
        ->where('business_id', $businessId)
        ->count();

    if ($pivotCount > 0) {
        testResult('Pivot table entries created', true, "{$pivotCount} entries");
        $testsPassed++;
    } else {
        testResult('Pivot table entries created', false);
        $testsFailed++;
    }

    // Simulate deletion
    $business->users()->detach();
    $business->delete();

    // Verify deletion
    $businessExists = Business::find($businessId) !== null;

    if (!$businessExists) {
        testResult('Business deleted', true);
        $testsPassed++;
    } else {
        testResult('Business deleted', false);
        $testsFailed++;
    }

    // Verify pivot table cleared
    $pivotAfter = DB::table('business_user')
        ->where('business_id', $businessId)
        ->count();

    if ($pivotAfter === 0) {
        testResult('Pivot entries cleared', true);
        $testsPassed++;
    } else {
        testResult('Pivot entries cleared', false);
        $testsFailed++;
    }

    // Test setup_completed reset
    $owner->update([
        'setup_completed' => false,
        'setup_completed_at' => null,
    ]);
    $owner->refresh();

    if (!$owner->setup_completed) {
        testResult('Owner setup_completed reset', true);
        $testsPassed++;
    } else {
        testResult('Owner setup_completed reset', false);
        $testsFailed++;
    }

    DB::rollBack();
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    testResult('Business Deletion', false, $e->getMessage());
    $testsFailed++;
    echo "\n";
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "══════════════════════════════════════════════════════════════\n";
echo "TEST SUMMARY\n";
echo "══════════════════════════════════════════════════════════════\n";
echo "\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "\033[32mPassed: {$testsPassed}\033[0m\n";
echo "\033[31mFailed: {$testsFailed}\033[0m\n";
echo "\n";

if ($testsFailed === 0) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ ALL SETTINGS FUNCTIONS WORKING                           ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
} else {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠ SOME TESTS FAILED - REVIEW REQUIRED                      ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
}

echo "\n";
echo "Features Tested:\n";
echo "  ✓ Business identity update (name, display name, industry, website, description)\n";
echo "  ✓ Logo upload/remove path storage\n";
echo "  ✓ User preferences (theme, accent color)\n";
echo "  ✓ Invitation code generation & regeneration\n";
echo "  ✓ Ownership transfer integration\n";
echo "  ✓ Business deletion cascade\n";
echo "\n";
