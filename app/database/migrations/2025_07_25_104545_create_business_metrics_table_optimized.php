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
        Schema::create('business_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('metric_name'); // e.g., 'Total Penjualan', 'Biaya Pokok Penjualan'
            $table->string('category'); // e.g., 'Penjualan', 'Keuangan', 'Pelanggan'
            $table->string('icon')->default('bi-graph-up'); // Bootstrap icon
            $table->text('description')->nullable();
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('previous_value', 15, 2)->default(0);
            $table->string('unit')->nullable(); // e.g., 'Rp', '%', 'unit'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for better performance
            $table->index(['business_id', 'metric_name']);
            $table->index(['business_id', 'category']);
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_metrics');
    }
};
