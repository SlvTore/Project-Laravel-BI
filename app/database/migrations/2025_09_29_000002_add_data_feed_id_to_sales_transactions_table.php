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
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'data_feed_id')) {
                $table->foreignId('data_feed_id')->nullable()->after('business_id')->constrained('data_feeds')->nullOnDelete();
                $table->index('data_feed_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('sales_transactions', 'data_feed_id')) {
                $table->dropConstrainedForeignId('data_feed_id');
            }
        });
    }
};
