<?php

/**
 * Check Settings Page Database Requirements
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     SETTINGS PAGE - DATABASE VERIFICATION                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check users table columns
echo "1. Checking 'users' table columns...\n";
$usersColumns = Schema::getColumnListing('users');
$requiredUserColumns = ['theme', 'accent_color'];

foreach ($requiredUserColumns as $column) {
    $exists = in_array($column, $usersColumns);
    echo "   " . ($exists ? '✓' : '✗') . " {$column}" . ($exists ? "" : " - MISSING") . "\n";
}

// Check businesses table columns
echo "\n2. Checking 'businesses' table columns...\n";
$businessColumns = Schema::getColumnListing('businesses');
$requiredBusinessColumns = [
    'business_name',
    'dashboard_display_name',
    'industry',
    'description',
    'website',
    'logo_path',
    'public_id',
    'invitation_code',
];

foreach ($requiredBusinessColumns as $column) {
    $exists = in_array($column, $businessColumns);
    echo "   " . ($exists ? '✓' : '✗') . " {$column}" . ($exists ? "" : " - MISSING") . "\n";
}

// Check routes
echo "\n3. Checking routes...\n";
$routes = [
    'dashboard.settings' => 'GET /dashboard/settings',
    'dashboard.settings.update' => 'PUT /dashboard/settings',
    'dashboard.settings.branding' => 'POST /dashboard/settings/branding',
    'dashboard.settings.preferences' => 'POST /dashboard/settings/preferences',
    'dashboard.settings.invitation.regenerate' => 'POST /dashboard/settings/invitation/regenerate',
    'dashboard.settings.ownership.transfer' => 'POST /dashboard/settings/ownership/transfer',
    'dashboard.settings.business.destroy' => 'DELETE /dashboard/settings/business',
];

foreach ($routes as $name => $description) {
    $exists = \Illuminate\Support\Facades\Route::has($name);
    echo "   " . ($exists ? '✓' : '✗') . " {$name}\n";
}

// Test business methods
echo "\n4. Checking Business model methods...\n";
$business = new \App\Models\Business();
$methods = [
    'refreshInvitationCode',
    'generatePublicId',
    'generateInvitationCode',
    'addUser',
    'removeUser',
];

foreach ($methods as $method) {
    $exists = method_exists($business, $method);
    echo "   " . ($exists ? '✓' : '✗') . " {$method}\n";
}

// Check User model methods
echo "\n5. Checking User model methods...\n";
$user = new \App\Models\User();
$userMethods = [
    'isBusinessOwner',
    'isAdministrator',
    'canManageUsers',
];

foreach ($userMethods as $method) {
    $exists = method_exists($user, $method);
    echo "   " . ($exists ? '✓' : '✗') . " {$method}\n";
}

// Summary
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICATION COMPLETE                                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check for missing columns
$missingUserColumns = array_diff($requiredUserColumns, $usersColumns);
$missingBusinessColumns = array_diff($requiredBusinessColumns, $businessColumns);

if (!empty($missingUserColumns) || !empty($missingBusinessColumns)) {
    echo "⚠️  MISSING COLUMNS DETECTED:\n\n";

    if (!empty($missingUserColumns)) {
        echo "Users table missing:\n";
        foreach ($missingUserColumns as $col) {
            echo "  - {$col}\n";
        }
        echo "\n";
    }

    if (!empty($missingBusinessColumns)) {
        echo "Businesses table missing:\n";
        foreach ($missingBusinessColumns as $col) {
            echo "  - {$col}\n";
        }
        echo "\n";
    }

    echo "Action required: Run migration to add missing columns\n";
} else {
    echo "✅  All required database columns present!\n";
}

echo "\n";
