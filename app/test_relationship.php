<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing User-Business relationship...\n";

    $user = App\Models\User::find(1);
    if ($user) {
        echo "User found: " . $user->name . "\n";

        // Test businesses relationship
        $businesses = $user->businesses;
        echo "Business count: " . $businesses->count() . "\n";

        if ($businesses->count() > 0) {
            echo "First business: " . $businesses->first()->business_name . "\n";
        }

        echo "Test successful - no SQL ambiguity error!\n";
    } else {
        echo "No user found\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
