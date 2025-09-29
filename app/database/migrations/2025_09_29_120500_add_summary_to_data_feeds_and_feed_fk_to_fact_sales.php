<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('data_feeds')) {
            Schema::table('data_feeds', function (Blueprint $table) {
                if (!Schema::hasColumn('data_feeds', 'summary')) {
                    $table->json('summary')->nullable()->after('log_message');
                }
            });
        }

        if (Schema::hasTable('fact_sales')) {
            Schema::table('fact_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('fact_sales', 'data_feed_id')) {
                    $table->foreignId('data_feed_id')
                        ->nullable()
                        ->after('channel_id')
                        ->constrained('data_feeds')
                        ->nullOnDelete();
                }

                $table->index(['business_id', 'data_feed_id'], 'idx_fs_business_feed');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fact_sales')) {
            Schema::table('fact_sales', function (Blueprint $table) {
                if (Schema::hasColumn('fact_sales', 'data_feed_id')) {
                    $table->dropIndex('idx_fs_business_feed');
                    $table->dropConstrainedForeignId('data_feed_id');
                }
            });
        }

        if (Schema::hasTable('data_feeds')) {
            Schema::table('data_feeds', function (Blueprint $table) {
                if (Schema::hasColumn('data_feeds', 'summary')) {
                    $table->dropColumn('summary');
                }
            });
        }
    }
};
