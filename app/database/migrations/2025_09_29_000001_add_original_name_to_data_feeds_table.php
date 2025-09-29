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
        Schema::table('data_feeds', function (Blueprint $table) {
            if (!Schema::hasColumn('data_feeds', 'original_name')) {
                $table->string('original_name')->nullable()->after('source');
                $table->index('original_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_feeds', function (Blueprint $table) {
            if (Schema::hasColumn('data_feeds', 'original_name')) {
                $table->dropIndex(['original_name']);
                $table->dropColumn('original_name');
            }
        });
    }
};
