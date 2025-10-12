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
            // Add location columns for capturing raw location data from CSV
            $table->string('provinsi')->nullable()->after('notes');
            $table->string('kota')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kota');

            // Add location_id to link to dim_location after mapping
            $table->unsignedBigInteger('location_id')->nullable()->after('kecamatan');
            $table->foreign('location_id')->references('id')->on('dim_location')->nullOnDelete();

            // Add index for location_id
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staging_sales_items', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn(['provinsi', 'kota', 'kecamatan', 'location_id']);
        });
    }
};
