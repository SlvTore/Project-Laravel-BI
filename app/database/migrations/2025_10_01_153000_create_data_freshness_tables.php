<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_freshness_log', function (Blueprint $table) {
            $table->id();
            $table->string('data_source');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->timestamp('last_updated');
            $table->string('last_operation')->default('update');
            $table->bigInteger('record_count')->default(0);
            $table->timestamps();

            $table->unique(['data_source', 'business_id']);
            $table->index(['business_id', 'last_updated']);
        });

        Schema::create('data_freshness_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->json('alert_thresholds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_alert_sent')->nullable();
            $table->timestamps();

            $table->unique('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_freshness_monitoring');
        Schema::dropIfExists('data_freshness_log');
    }
};
