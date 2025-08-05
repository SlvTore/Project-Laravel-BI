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
        Schema::table('sales_data', function (Blueprint $table) {
            // Add new customer related fields
            $table->integer('new_customer_count')->default(0)->after('transaction_count');
            $table->integer('total_customer_count')->default(0)->after('new_customer_count');

            // Add indexes for better performance
            $table->index(['business_id', 'sales_date', 'new_customer_count']);
            $table->index(['business_id', 'sales_date', 'total_customer_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'sales_date', 'new_customer_count']);
            $table->dropIndex(['business_id', 'sales_date', 'total_customer_count']);
            $table->dropColumn(['new_customer_count', 'total_customer_count']);
        });
    }
};
