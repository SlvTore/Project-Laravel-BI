<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staging_sales_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_feed_id')->constrained('data_feeds')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 15, 3);
            $table->string('unit_at_transaction')->nullable();
            $table->decimal('selling_price_at_transaction', 15, 2);
            $table->decimal('discount_per_item', 15, 2)->default(0);
            $table->dateTime('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_sales_items');
    }
};
