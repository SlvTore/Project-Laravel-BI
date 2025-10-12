<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update all OLAP views to include location dimension for drill-down analysis.
     */
    public function up(): void
    {
        // Drop existing views (include all potentially existing views to handle migrate:fresh not dropping views)
        DB::statement('DROP VIEW IF EXISTS vw_sales_by_kecamatan');
        DB::statement('DROP VIEW IF EXISTS vw_sales_by_kota');
        DB::statement('DROP VIEW IF EXISTS vw_sales_by_provinsi');
        DB::statement('DROP VIEW IF EXISTS vw_sales_daily');
        DB::statement('DROP VIEW IF EXISTS vw_sales_product_daily');
        DB::statement('DROP VIEW IF EXISTS vw_sales_location_daily');
        DB::statement('DROP VIEW IF EXISTS vw_margin_daily');
        DB::statement('DROP VIEW IF EXISTS vw_cogs_daily');

        // Recreate vw_sales_daily with location
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
                COUNT(DISTINCT fs.sales_transaction_id) as transaction_count,
                COUNT(*) as item_count,
                ROUND(AVG(fs.unit_price), 2) as avg_unit_price
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            GROUP BY fs.business_id, dd.date, dd.year, dd.month, dd.quarter,
                     dl.provinsi, dl.kota, dl.kecamatan
        ");

        // Create location-focused sales view for drill-down
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
                COUNT(DISTINCT fs.sales_transaction_id) as transactions,
                COUNT(DISTINCT fs.customer_id) as unique_customers,
                COUNT(*) as items_sold,
                ROUND(AVG(fs.gross_margin_percent), 2) as avg_margin_pct
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            WHERE dl.id IS NOT NULL
            GROUP BY fs.business_id, dd.date, dl.id, dl.provinsi, dl.kota, dl.kecamatan
        ");

        // Recreate vw_sales_product_daily with location
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
                COUNT(DISTINCT fs.sales_transaction_id) as transaction_count
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            JOIN dim_product dp ON fs.product_id = dp.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            GROUP BY fs.business_id, dd.date, dd.year, dd.month,
                     dp.id, dp.name, dp.category, dl.provinsi, dl.kota
        ");

        // Recreate vw_margin_daily with location
        DB::statement("
            CREATE VIEW vw_margin_daily AS
            SELECT
                fs.business_id,
                dd.date,
                dd.year,
                dd.month,
                dl.provinsi,
                dl.kota,
                SUM(fs.gross_revenue) as total_revenue,
                SUM(fs.cogs_amount) as total_cogs,
                SUM(fs.gross_margin_amount) as total_margin,
                CASE
                    WHEN SUM(fs.gross_revenue) > 0
                    THEN ROUND((SUM(fs.gross_margin_amount) / SUM(fs.gross_revenue)) * 100, 2)
                    ELSE 0
                END as margin_percentage
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            GROUP BY fs.business_id, dd.date, dd.year, dd.month, dl.provinsi, dl.kota
        ");

        // Recreate vw_cogs_daily with location
        DB::statement("
            CREATE VIEW vw_cogs_daily AS
            SELECT
                fs.business_id,
                dd.date,
                dd.year,
                dd.month,
                dl.provinsi,
                SUM(fs.cogs_amount) as total_cogs,
                SUM(fs.quantity) as total_quantity_sold,
                COUNT(DISTINCT dp.id) as unique_products
            FROM fact_sales fs
            JOIN dim_date dd ON fs.date_id = dd.id
            JOIN dim_product dp ON fs.product_id = dp.id
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            GROUP BY fs.business_id, dd.date, dd.year, dd.month, dl.provinsi
        ");

        // Create aggregated views for each hierarchy level
        DB::statement("
            CREATE VIEW vw_sales_by_provinsi AS
            SELECT
                fs.business_id,
                dl.provinsi,
                SUM(fs.gross_revenue) as total_revenue,
                SUM(fs.gross_margin_amount) as total_margin,
                SUM(fs.cogs_amount) as total_cogs,
                COUNT(DISTINCT fs.sales_transaction_id) as transaction_count,
                COUNT(DISTINCT fs.customer_id) as unique_customers,
                ROUND(AVG(fs.gross_margin_percent), 2) as avg_margin_pct
            FROM fact_sales fs
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            WHERE dl.provinsi IS NOT NULL
            GROUP BY fs.business_id, dl.provinsi
        ");

        DB::statement("
            CREATE VIEW vw_sales_by_kota AS
            SELECT
                fs.business_id,
                dl.provinsi,
                dl.kota,
                SUM(fs.gross_revenue) as total_revenue,
                SUM(fs.gross_margin_amount) as total_margin,
                SUM(fs.cogs_amount) as total_cogs,
                COUNT(DISTINCT fs.sales_transaction_id) as transaction_count,
                COUNT(DISTINCT fs.customer_id) as unique_customers,
                ROUND(AVG(fs.gross_margin_percent), 2) as avg_margin_pct
            FROM fact_sales fs
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            WHERE dl.kota IS NOT NULL
            GROUP BY fs.business_id, dl.provinsi, dl.kota
        ");

        DB::statement("
            CREATE VIEW vw_sales_by_kecamatan AS
            SELECT
                fs.business_id,
                dl.provinsi,
                dl.kota,
                dl.kecamatan,
                SUM(fs.gross_revenue) as total_revenue,
                SUM(fs.gross_margin_amount) as total_margin,
                SUM(fs.cogs_amount) as total_cogs,
                COUNT(DISTINCT fs.sales_transaction_id) as transaction_count,
                COUNT(DISTINCT fs.customer_id) as unique_customers,
                ROUND(AVG(fs.gross_margin_percent), 2) as avg_margin_pct
            FROM fact_sales fs
            LEFT JOIN dim_location dl ON fs.location_id = dl.id
            WHERE dl.kecamatan IS NOT NULL
            GROUP BY fs.business_id, dl.provinsi, dl.kota, dl.kecamatan
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_sales_by_kecamatan');
        DB::statement('DROP VIEW IF EXISTS vw_sales_by_kota');
        DB::statement('DROP VIEW IF EXISTS vw_sales_by_provinsi');
        DB::statement('DROP VIEW IF EXISTS vw_cogs_daily');
        DB::statement('DROP VIEW IF EXISTS vw_margin_daily');
        DB::statement('DROP VIEW IF EXISTS vw_sales_product_daily');
        DB::statement('DROP VIEW IF EXISTS vw_sales_location_daily');
        DB::statement('DROP VIEW IF EXISTS vw_sales_daily');
    }
};
