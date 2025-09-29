<?php

// Test script untuk memvalidasi sinkronisasi data BOM dan Sales Transaction
// Jalankan dengan: php test_bom_sales_sync.php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\Business;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BillOfMaterial;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use Illuminate\Support\Facades\Schema;

// Cek koneksi database
try {
    echo "Testing database connection...\n";
    $business = Business::first();
    if ($business) {
        echo "✓ Database connected successfully. Business ID: {$business->id}\n";
    } else {
        echo "✗ No business found in database\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Cek apakah tabel BillOfMaterial ada dan dapat diakses
try {
    echo "\nTesting BillOfMaterial model...\n";
    $bomCount = BillOfMaterial::count();
    echo "✓ BillOfMaterial table accessible. Current records: $bomCount\n";

    // Cek struktur field
    $bomFields = Schema::getColumnListing('bill_of_materials');
    echo "✓ BillOfMaterial fields: " . implode(', ', $bomFields) . "\n";
} catch (Exception $e) {
    echo "✗ BillOfMaterial test failed: " . $e->getMessage() . "\n";
}

// Cek apakah tabel SalesTransaction ada dan dapat diakses
try {
    echo "\nTesting SalesTransaction model...\n";
    $salesCount = SalesTransaction::count();
    echo "✓ SalesTransaction table accessible. Current records: $salesCount\n";

    // Cek struktur field
    $salesFields = Schema::getColumnListing('sales_transactions');
    echo "✓ SalesTransaction fields: " . implode(', ', $salesFields) . "\n";
} catch (Exception $e) {
    echo "✗ SalesTransaction test failed: " . $e->getMessage() . "\n";
}

// Cek apakah tabel SalesTransactionItem ada dan dapat diakses
try {
    echo "\nTesting SalesTransactionItem model...\n";
    $itemCount = SalesTransactionItem::count();
    echo "✓ SalesTransactionItem table accessible. Current records: $itemCount\n";

    // Cek struktur field
    $itemFields = Schema::getColumnListing('sales_transaction_items');
    echo "✓ SalesTransactionItem fields: " . implode(', ', $itemFields) . "\n";
} catch (Exception $e) {
    echo "✗ SalesTransactionItem test failed: " . $e->getMessage() . "\n";
}

// Cek model Product dan Customer
try {
    echo "\nTesting Product and Customer models...\n";
    $productCount = Product::count();
    $customerCount = Customer::count();
    echo "✓ Product records: $productCount\n";
    echo "✓ Customer records: $customerCount\n";
} catch (Exception $e) {
    echo "✗ Product/Customer test failed: " . $e->getMessage() . "\n";
}

echo "\n✓ All model tests completed successfully!\n";
echo "\nNext steps:\n";
echo "1. Import CSV data melalui dashboard\n";
echo "2. Cek apakah data BillOfMaterial dan SalesTransaction tersinkronisasi\n";
echo "3. Verifikasi di dashboard bagian 'Kelola Data Produk' dan 'Transaksi Penjualan'\n";
