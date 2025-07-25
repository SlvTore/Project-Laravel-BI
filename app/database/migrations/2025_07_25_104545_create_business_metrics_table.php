<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('metric_name'); // e.g., 'revenue', 'customers', 'conversion_rate'
            $table->decimal('value', 15, 2);
            $table->string('unit')->nullable(); // e.g., 'IDR', 'count', 'percentage'
            $table->date('period_date'); // Date for this metric
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'metric_name', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_metrics');
    }
};
