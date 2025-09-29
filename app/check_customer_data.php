<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

echo "=== Checking Customer Data ===\n";

try {
    $businessId = 1;

    echo "Total customers for business {$businessId}: " . Customer::forBusiness($businessId)->count() . "\n";

    echo "\nCustomer types available:\n";
    $types = Customer::forBusiness($businessId)->distinct()->pluck('customer_type');
    foreach ($types as $type) {
        $count = Customer::forBusiness($businessId)->where('customer_type', $type)->count();
        echo "- {$type}: {$count} customers\n";
    }

    echo "\nSample customers:\n";
    $samples = Customer::forBusiness($businessId)
        ->orderBy('first_purchase_date', 'desc')
        ->limit(5)
        ->get(['customer_name', 'email', 'customer_type', 'first_purchase_date', 'total_spent']);

    foreach ($samples as $customer) {
        echo "- {$customer->customer_name} ({$customer->customer_type}), First: {$customer->first_purchase_date}, Spent: Rp " . number_format($customer->total_spent ?? 0, 0) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
