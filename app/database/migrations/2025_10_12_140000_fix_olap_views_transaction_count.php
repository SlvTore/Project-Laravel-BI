<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix transaction_count in views to handle NULL sales_transaction_id
     */
    public function up(): void
    {
        // Drop and recreate vw_sales_daily with fixed transaction_count
        DB::statement('DROP VIEW IF EXISTS vw_sales_daily');
        DB::statement("
            CREATE VIEW vw_sales_daily AS
            SELECT
                fs.business_id,
                dd.date,
                dd.year,
                dd.month,
                dd.quarter,
                dl.provinsi,
                dl.kota,
                dl.kecamatan,
                SUM(fs.gross_revenue) as gross_revenue,
                SUM(fs.discount) as total_discount,
                SUM(fs.tax_amount) as total_tax,
                COUNT(DISTINCT COALESCE(fs.sales_transaction_id, fs.id)) as transaction_count,
                COUNT(*) as item_count,
                ROUND(AVG(fs.unit_price), 2) as avg_unit_price
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            GROUP BY fs.business_id, dd.date, dd.year, dd.month, dd.quarter,
                     dl.provinsi, dl.kota, dl.kecamatan
        ");

        // Update vw_sales_product_daily similarly
        DB::statement('DROP VIEW IF EXISTS vw_sales_product_daily');
        DB::statement("
            CREATE VIEW vw_sales_product_daily AS
            SELECT
                fs.business_id,
                dd.date,
                dd.year,
                dd.month,
                dp.id as product_id,
                dp.name as product_name,
                dp.category,
                dl.provinsi,
                dl.kota,
                SUM(fs.quantity) as quantity_sold,
                SUM(fs.gross_revenue) as revenue,
                SUM(fs.cogs_amount) as cogs,
                SUM(fs.gross_margin_amount) as margin,
                ROUND(AVG(fs.gross_margin_percent), 2) as avg_margin_pct,
                COUNT(DISTINCT COALESCE(fs.sales_transaction_id, fs.id)) as transaction_count
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            JOIN dim_product dp ON fs.product_id = dp.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            GROUP BY fs.business_id, dd.date, dd.year, dd.month,
                     dp.id, dp.name, dp.category, dl.provinsi, dl.kota
        ");

        // Update vw_sales_location_daily similarly
        DB::statement('DROP VIEW IF EXISTS vw_sales_location_daily');
        DB::statement("
            CREATE VIEW vw_sales_location_daily AS
            SELECT
                fs.business_id,
                dd.date,
                dl.id as location_id,
                dl.provinsi,
                dl.kota,
                dl.kecamatan,
                SUM(fs.gross_revenue) as revenue,
                SUM(fs.gross_margin_amount) as margin,
                SUM(fs.cogs_amount) as cogs,
                COUNT(DISTINCT COALESCE(fs.sales_transaction_id, fs.id)) as transactions,
                COUNT(DISTINCT fs.customer_id) as unique_customers,
                COUNT(*) as items_sold,
                ROUND(AVG(fs.gross_margin_percent), 2) as avg_margin_pct
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            WHERE dl.id IS NOT NULL
            GROUP BY fs.business_id, dd.date, dl.id, dl.provinsi, dl.kota, dl.kecamatan
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Views will be dropped by the next migration if needed
    }
};
