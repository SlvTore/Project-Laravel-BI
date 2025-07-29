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
        Schema::create('metric_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_metric_id')->constrained()->onDelete('cascade');
            $table->date('record_date');
            $table->decimal('value', 15, 2);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // For storing additional data specific to metric type
            $table->timestamps();

            // Indexes
            $table->index(['business_metric_id', 'record_date']);
            $table->unique(['business_metric_id', 'record_date'], 'unique_metric_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_records');
    }
};
