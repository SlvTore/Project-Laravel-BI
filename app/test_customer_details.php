<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use Carbon\Carbon;

echo "=== Testing Customer Detail Enhancement ===\n";

try {
    $businessId = 1;
    $monthStart = Carbon::now()->subDays(30)->toDateString();

    echo "Testing new customers details:\n";
    $newCustomers = Customer::forBusiness($businessId)
        ->where('customer_type', 'new')
        ->where('first_purchase_date', '>=', $monthStart)
        ->orderBy('first_purchase_date', 'desc')
        ->limit(5)
        ->get(['customer_name', 'email', 'first_purchase_date', 'total_spent']);

    if ($newCustomers->count() > 0) {
        foreach ($newCustomers as $customer) {
            echo "- {$customer->customer_name} ({$customer->email}), Joined: {$customer->first_purchase_date}, Spent: Rp " . number_format($customer->total_spent, 0) . "\n";
        }
    } else {
        echo "No new customers found in recent period\n";
    }

    echo "\nTesting returning customers details:\n";
    $returningCustomers = Customer::forBusiness($businessId)
        ->where('customer_type', 'returning')
        ->where('last_purchase_date', '>=', $monthStart)
        ->orderBy('last_purchase_date', 'desc')
        ->limit(5)
        ->get(['customer_name', 'email', 'last_purchase_date', 'total_spent', 'total_purchases']);

    if ($returningCustomers->count() > 0) {
        foreach ($returningCustomers as $customer) {
            echo "- {$customer->customer_name} ({$customer->email}), Last: {$customer->last_purchase_date}, Transactions: {$customer->total_purchases}, Spent: Rp " . number_format($customer->total_spent, 0) . "\n";
        }
    } else {
        echo "No returning customers found in recent period\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
