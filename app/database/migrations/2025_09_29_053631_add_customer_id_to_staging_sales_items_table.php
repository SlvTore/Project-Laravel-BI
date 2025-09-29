<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staging_sales_items', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('product_id')->constrained('customers')->nullOnDelete();
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_per_item');
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('tax_amount');
            $table->string('payment_method')->nullable()->after('shipping_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staging_sales_items', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'tax_amount', 'shipping_cost', 'payment_method']);
        });
    }
};
