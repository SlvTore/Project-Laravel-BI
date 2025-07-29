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
        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->date('sales_date');
            $table->integer('quantity_sold')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('revenue_generated', 15, 2)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'sales_date']);
            $table->index(['business_id', 'product_name']);
            $table->index(['business_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
