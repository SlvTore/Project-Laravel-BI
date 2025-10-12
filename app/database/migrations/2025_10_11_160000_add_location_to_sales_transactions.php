<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add location dimension to sales_transactions for manual transaction input.
     */
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            // Add raw location fields for capturing transaction location
            $table->string('provinsi')->nullable()->after('notes');
            $table->string('kota')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kota');

            // Add location_id reference to dim_location
            $table->unsignedBigInteger('location_id')->nullable()->after('kecamatan');
            $table->foreign('location_id')->references('id')->on('dim_location')->nullOnDelete();

            // Add index for location queries
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn(['provinsi', 'kota', 'kecamatan', 'location_id']);
        });
    }
};
