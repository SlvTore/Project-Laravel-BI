<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // dim_date
        Schema::create('dim_date', function (Blueprint $table) {
            $table->id(); // surrogate key
            $table->date('date')->unique();
            $table->unsignedSmallInteger('day');
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('quarter');
            $table->string('month_name', 16);
            $table->string('day_name', 16);
            $table->timestamps();
        });

        // dim_product
        Schema::create('dim_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('product_nk')->nullable(); // natural key -> products.id
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit', 32)->nullable();
            $table->timestamps();
            $table->index(['business_id', 'product_nk'], 'idx_dp_biz_nk');
        });

        // dim_customer
        Schema::create('dim_customer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('customer_nk')->nullable(); // natural key -> customers.id
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'customer_nk'], 'idx_dc_biz_nk');
        });

        // dim_channel (optional)
        Schema::create('dim_channel', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->timestamps();
            $table->unique(['business_id', 'name'], 'uk_dc_biz_name');
        });

        // fact_sales (grain: transaction item)
        Schema::create('fact_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('date_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->unsignedBigInteger('sales_transaction_id')->nullable();
            $table->unsignedBigInteger('sales_transaction_item_id')->nullable();

            // measures
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->timestamps();

            $table->index(['business_id', 'date_id'], 'idx_fs_biz_date');
            $table->index(['business_id', 'product_id', 'date_id'], 'idx_fs_biz_prod_date');
            $table->index(['business_id', 'customer_id', 'date_id'], 'idx_fs_biz_cust_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_sales');
        Schema::dropIfExists('dim_channel');
        Schema::dropIfExists('dim_customer');
        Schema::dropIfExists('dim_product');
        Schema::dropIfExists('dim_date');
    }
};
