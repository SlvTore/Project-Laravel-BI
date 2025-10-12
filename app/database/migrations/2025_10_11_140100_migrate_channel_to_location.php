<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate from dim_channel to dim_location in fact_sales.
     * Preserves existing data by mapping to 'Unknown' location.
     */
    public function up(): void
    {
        // Step 1: Ensure dim_location has Unknown location for migration
        DB::table('dim_location')->insertOrIgnore([
            'business_id' => null,
            'location_nk' => 'UNKNOWN',
            'kecamatan' => 'Unknown',
            'kota' => 'Unknown',
            'provinsi' => 'Unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unknownLocationId = DB::table('dim_location')
            ->whereNull('business_id')
            ->where('provinsi', 'Unknown')
            ->value('id');

        // Step 2: Update existing fact_sales records
        // Map all existing records to Unknown location
        if ($unknownLocationId) {
            DB::table('fact_sales')
                ->whereNull('location_id')
                ->update(['location_id' => $unknownLocationId]);
        }

        // Step 3: Drop old channel_id column if exists
        if (Schema::hasColumn('fact_sales', 'channel_id')) {
            Schema::table('fact_sales', function (Blueprint $table) {
                // Drop index if exists (wrap in try-catch to handle if it doesn't exist)
                try {
                    $table->dropIndex('idx_fact_sales_channel');
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }

                // Drop column (no foreign key constraint exists)
                $table->dropColumn('channel_id');
            });
        }

        // Step 4: Drop dim_channel table if exists
        Schema::dropIfExists('dim_channel');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate dim_channel
        Schema::create('dim_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')
                ->constrained('businesses')
                ->onDelete('cascade');
            $table->string('name', 100);
            $table->timestamps();
            $table->index(['business_id', 'name'], 'idx_channel_business_name');
        });

        // Add channel_id back to fact_sales
        Schema::table('fact_sales', function (Blueprint $table) {
            $table->foreignId('channel_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('dim_channel')
                ->onDelete('set null');
            $table->index('channel_id', 'idx_fact_sales_channel');
        });

        // Note: Data migration back is not implemented
        // Manual intervention required if rollback is needed
    }
};
