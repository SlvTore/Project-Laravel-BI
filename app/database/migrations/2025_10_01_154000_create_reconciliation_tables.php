<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->enum('frequency', ['hourly', 'daily', 'weekly', 'monthly'])->default('daily');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->timestamps();

            $table->unique('business_id');
        });

        Schema::create('reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->timestamp('reconciliation_date');
            $table->enum('overall_status', ['healthy', 'minor_issues', 'needs_attention', 'critical']);
            $table->integer('total_discrepancies')->default(0);
            $table->integer('critical_discrepancies')->default(0);
            $table->json('reconciliation_details');
            $table->json('recommendations')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'reconciliation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_logs');
        Schema::dropIfExists('reconciliation_schedules');
    }
};
