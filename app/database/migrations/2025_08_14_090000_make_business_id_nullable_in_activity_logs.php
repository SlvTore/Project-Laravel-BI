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
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['business_id']);
            
            // Make business_id nullable
            $table->foreignId('business_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['business_id']);
            
            // Make business_id not nullable again
            $table->foreignId('business_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }
};