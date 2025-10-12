<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create dimension table for location with hierarchical structure.
     * Supports drill-down analysis from Province → City → District.
     */
    public function up(): void
    {
        Schema::create('dim_location', function (Blueprint $table) {
            $table->id(); // location_sk (surrogate key)

            // Business key for multi-tenant support
            $table->foreignId('business_id')
                ->nullable()
                ->constrained('businesses')
                ->onDelete('cascade');

            // Natural key (optional external reference)
            $table->string('location_nk', 50)->nullable()->comment('Natural key from external system');

            // Hierarchical attributes (drill-down levels)
            $table->string('kecamatan', 100)->nullable()->comment('District level (finest grain)');
            $table->string('kota', 100)->nullable()->comment('City/Regency level');
            $table->string('provinsi', 100)->nullable()->comment('Province level (highest grain)');

            // Additional attributes for enrichment
            $table->string('kode_pos', 10)->nullable()->comment('Postal code');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Metadata
            $table->timestamps();

            // Indexes for drill-down performance
            $table->index('provinsi', 'idx_location_provinsi');
            $table->index(['provinsi', 'kota'], 'idx_location_provinsi_kota');
            $table->index(['provinsi', 'kota', 'kecamatan'], 'idx_location_full_hierarchy');
            $table->index(['business_id', 'provinsi'], 'idx_location_business_provinsi');

            // Unique constraint for hierarchy within business
            $table->unique(['business_id', 'provinsi', 'kota', 'kecamatan'], 'uq_location_hierarchy');
        });

        // Add location_id to fact_sales (replacing channel_id)
        Schema::table('fact_sales', function (Blueprint $table) {
            // Add new location dimension
            $table->foreignId('location_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('dim_location')
                ->onDelete('set null');

            // Add index for location-based queries
            $table->index('location_id', 'idx_fact_sales_location');
            $table->index(['business_id', 'location_id', 'date_id'], 'idx_fact_location_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fact_sales', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex('idx_fact_sales_location');
            $table->dropIndex('idx_fact_location_time');
            $table->dropColumn('location_id');
        });

        Schema::dropIfExists('dim_location');
    }
};
