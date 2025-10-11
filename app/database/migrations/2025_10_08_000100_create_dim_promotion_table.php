<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dim_promotion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('promotion_nk', 50); // Natural key
            $table->string('name');
            $table->string('type')->nullable(); // percentage, nominal, bundle
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->string('discount_type', 20)->nullable(); // percent|amount
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'promotion_nk']);
            $table->index(['is_active', 'type']);
        });

        // Add promotion_id to fact_sales
        if (Schema::hasTable('fact_sales')) {
            Schema::table('fact_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('fact_sales', 'promotion_id')) {
                    $table->foreignId('promotion_id')->nullable()->after('channel_id')->constrained('dim_promotion')->onDelete('set null');
                }
            });
        }

        // Seed default "No Promotion" records for each business
        $this->seedDefaultPromotions();
    }

    public function down(): void
    {
        // Remove FK from fact_sales first
        if (Schema::hasTable('fact_sales')) {
            Schema::table('fact_sales', function (Blueprint $table) {
                if (Schema::hasColumn('fact_sales', 'promotion_id')) {
                    $table->dropForeign(['promotion_id']);
                    $table->dropColumn('promotion_id');
                }
            });
        }

        Schema::dropIfExists('dim_promotion');
    }

    private function seedDefaultPromotions(): void
    {
        $businesses = DB::table('businesses')->pluck('id');

        foreach ($businesses as $businessId) {
            DB::table('dim_promotion')->insert([
                'business_id' => $businessId,
                'promotion_nk' => 'NONE',
                'name' => 'No Promotion',
                'type' => null,
                'discount_value' => 0,
                'discount_type' => null,
                'discount_percent' => 0,
                'start_date' => null,
                'end_date' => null,
                'description' => 'Default promotion for transactions without promotions',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Also add UNKNOWN for safety
            DB::table('dim_promotion')->insert([
                'business_id' => $businessId,
                'promotion_nk' => 'UNKNOWN',
                'name' => 'Unknown Promotion',
                'type' => null,
                'discount_value' => null,
                'discount_type' => null,
                'discount_percent' => 0,
                'start_date' => null,
                'end_date' => null,
                'description' => 'Fallback for unidentified promotions',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
