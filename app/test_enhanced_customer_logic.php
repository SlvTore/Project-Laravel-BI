<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use Carbon\Carbon;

echo "=== Testing Enhanced Customer Detail Logic ===\n";

try {
    $businessId = 1;
    $monthStart = Carbon::now()->subDays(30)->toDateString();

    echo "Testing flexible new customers query:\n";
    $newCustomers = Customer::forBusiness($businessId)
        ->where(function($query) use ($monthStart) {
            $query->where('customer_type', 'new')
                  ->orWhere('first_purchase_date', '>=', $monthStart)
                  ->orWhereNull('customer_type');
        })
        ->orderBy('first_purchase_date', 'desc')
        ->limit(5)
        ->get(['customer_name', 'email', 'first_purchase_date', 'total_spent']);

    echo "Found {$newCustomers->count()} customers:\n";
    foreach ($newCustomers as $customer) {
        echo "- {$customer->customer_name} ({$customer->email}), Joined: {$customer->first_purchase_date}, Spent: Rp " . number_format($customer->total_spent ?? 0, 0) . "\n";
    }

    echo "\nTesting flexible returning customers query:\n";
    $returningCustomers = Customer::forBusiness($businessId)
        ->where(function($query) use ($monthStart) {
            $query->where('customer_type', 'returning')
                  ->orWhere('total_purchases', '>', 1)
                  ->orWhere('last_purchase_date', '>=', $monthStart);
        })
        ->orderBy('last_purchase_date', 'desc')
        ->limit(5)
        ->get(['customer_name', 'email', 'last_purchase_date', 'total_spent', 'total_purchases']);

    echo "Found {$returningCustomers->count()} customers:\n";
    foreach ($returningCustomers as $customer) {
        $totalPurchases = $customer->total_purchases ?? 0;
        $totalSpent = $customer->total_spent ?? 0;
        echo "- {$customer->customer_name} ({$customer->email}), Last: {$customer->last_purchase_date}, Transactions: {$totalPurchases}, Spent: Rp " . number_format($totalSpent, 0) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
