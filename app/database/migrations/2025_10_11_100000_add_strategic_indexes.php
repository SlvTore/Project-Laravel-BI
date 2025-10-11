<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfNotExists('fact_sales', ['business_id', 'date_id'], 'idx_fact_sales_business_date');
        $this->addIndexIfNotExists('fact_sales', 'product_id', 'idx_fact_sales_product');
        $this->addIndexIfNotExists('fact_sales', 'customer_id', 'idx_fact_sales_customer');
        $this->addIndexIfNotExists('fact_sales', 'channel_id', 'idx_fact_sales_channel');
        $this->addIndexIfNotExists('fact_sales', ['product_id', 'date_id'], 'idx_fact_sales_product_date');
        $this->addIndexIfNotExists('fact_sales', 'data_feed_id', 'idx_fact_sales_feed');
        $this->addIndexIfNotExists('dim_product', ['business_id', 'product_nk'], 'idx_dim_product_business_nk');
        $this->addIndexIfNotExists('dim_customer', ['business_id', 'customer_nk'], 'idx_dim_customer_business_nk');
        $this->addIndexIfNotExists('dim_date', 'date', 'idx_dim_date_date');
        $this->addIndexIfNotExists('dim_date', ['year', 'month'], 'idx_dim_date_year_month');
        $this->addIndexIfNotExists('dim_date', ['year', 'quarter'], 'idx_dim_date_year_quarter');
        $this->addIndexIfNotExists('staging_sales_items', 'data_feed_id', 'idx_staging_sales_feed');
        $this->addIndexIfNotExists('staging_sales_items', 'product_id', 'idx_staging_sales_product');
    }

    public function down(): void
    {
        Schema::table('fact_sales', function (Blueprint $table) {
            $table->dropIndex('idx_fact_sales_business_date');
            $table->dropIndex('idx_fact_sales_product');
            $table->dropIndex('idx_fact_sales_customer');
            $table->dropIndex('idx_fact_sales_channel');
            $table->dropIndex('idx_fact_sales_product_date');
            $table->dropIndex('idx_fact_sales_feed');
        });
        Schema::table('dim_product', function (Blueprint $table) {
            $table->dropIndex('idx_dim_product_business_nk');
        });
        Schema::table('dim_customer', function (Blueprint $table) {
            $table->dropIndex('idx_dim_customer_business_nk');
        });
        Schema::table('dim_date', function (Blueprint $table) {
            $table->dropIndex('idx_dim_date_date');
            $table->dropIndex('idx_dim_date_year_month');
            $table->dropIndex('idx_dim_date_year_quarter');
        });
        Schema::table('staging_sales_items', function (Blueprint $table) {
            $table->dropIndex('idx_staging_sales_feed');
            $table->dropIndex('idx_staging_sales_product');
        });
    }

    private function addIndexIfNotExists(string $table, $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $databaseName = DB::connection()->getDatabaseName();
            $result = DB::select('SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?', [$databaseName, $table, $indexName]);
            return isset($result[0]) && $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
