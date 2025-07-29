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
        Schema::table('business_metrics', function (Blueprint $table) {
            // Drop existing columns that we don't need
            if (Schema::hasColumn('business_metrics', 'metrics_id')) {
                $table->dropColumn('metrics_id');
            }
            if (Schema::hasColumn('business_metrics', 'value')) {
                $table->dropColumn('value');
            }
            if (Schema::hasColumn('business_metrics', 'period_date')) {
                $table->dropColumn('period_date');
            }
            if (Schema::hasColumn('business_metrics', 'notes')) {
                $table->dropColumn('notes');
            }

            // Add new columns for simplified structure
            if (!Schema::hasColumn('business_metrics', 'metric_name')) {
                $table->string('metric_name')->after('business_id');
            }
            if (!Schema::hasColumn('business_metrics', 'category')) {
                $table->string('category')->after('metric_name');
            }
            if (!Schema::hasColumn('business_metrics', 'icon')) {
                $table->string('icon')->default('bi-graph-up')->after('category');
            }
            if (!Schema::hasColumn('business_metrics', 'description')) {
                $table->text('description')->nullable()->after('icon');
            }
            if (!Schema::hasColumn('business_metrics', 'current_value')) {
                $table->decimal('current_value', 15, 2)->default(0)->after('description');
            }
            if (!Schema::hasColumn('business_metrics', 'previous_value')) {
                $table->decimal('previous_value', 15, 2)->default(0)->after('current_value');
            }
            if (!Schema::hasColumn('business_metrics', 'unit')) {
                $table->string('unit')->nullable()->after('previous_value');
            }
            if (!Schema::hasColumn('business_metrics', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_metrics', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['metric_name', 'category', 'icon', 'description', 'current_value', 'previous_value', 'unit', 'is_active']);

            // Restore original columns (if needed)
            $table->unsignedBigInteger('metrics_id')->after('business_id');
            $table->decimal('value', 15, 2)->after('metrics_id');
            $table->string('unit')->nullable()->after('value');
            $table->date('period_date')->after('unit');
            $table->text('notes')->nullable()->after('period_date');
        });
    }
};
