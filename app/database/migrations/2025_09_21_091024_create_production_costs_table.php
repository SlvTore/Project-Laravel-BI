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
        Schema::create('production_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('category', 100); // Bahan Baku, Tenaga Kerja, Overhead, Packaging, Lainnya
            $table->text('description');
            $table->decimal('amount', 15, 2); // Cost amount in Rupiah
            $table->decimal('unit_quantity', 10, 2)->default(1); // For per-unit cost calculation
            $table->string('unit_type', 50)->default('Pcs'); // Unit type
            $table->json('metadata')->nullable(); // Additional cost details
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_costs');
    }
};
