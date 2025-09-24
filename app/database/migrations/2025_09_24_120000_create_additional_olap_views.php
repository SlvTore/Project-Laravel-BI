<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Sales by product per day
        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW vw_sales_product_daily AS
        SELECT
            f.business_id,
            d.date AS sales_date,
            p.id AS product_dim_id,
            p.name AS product_name,
            SUM(f.total_amount) AS total_revenue,
            SUM(f.quantity) AS total_quantity,
            COUNT(DISTINCT f.sales_transaction_id) AS transaction_count
        FROM fact_sales f
        JOIN dim_date d ON d.id = f.date_id
        JOIN dim_product p ON p.id = f.product_id
        GROUP BY f.business_id, d.date, p.id, p.name;
        SQL);

        // Estimated COGS per day (uses dim_product.product_nk to join products.id)
        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW vw_cogs_daily AS
        SELECT
            f.business_id,
            d.date AS sales_date,
            SUM(f.quantity * COALESCE(pr.cost_price, 0)) AS total_cogs
        FROM fact_sales f
        JOIN dim_date d ON d.id = f.date_id
        JOIN dim_product dp ON dp.id = f.product_id
        LEFT JOIN products pr ON pr.id = dp.product_nk
        GROUP BY f.business_id, d.date;
        SQL);

        // Margin per day = revenue - estimated cogs
        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW vw_margin_daily AS
        SELECT
            f.business_id,
            d.date AS sales_date,
            SUM(f.total_amount) - SUM(f.quantity * COALESCE(pr.cost_price, 0)) AS total_margin
        FROM fact_sales f
        JOIN dim_date d ON d.id = f.date_id
        JOIN dim_product dp ON dp.id = f.product_id
        LEFT JOIN products pr ON pr.id = dp.product_nk
        GROUP BY f.business_id, d.date;
        SQL);

        // New customers per day (first purchase date)
        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW vw_new_customers_daily AS
        WITH firsts AS (
            SELECT f.business_id, f.customer_id, MIN(d.date) AS first_date
            FROM fact_sales f
            JOIN dim_date d ON d.id = f.date_id
            WHERE f.customer_id IS NOT NULL
            GROUP BY f.business_id, f.customer_id
        )
        SELECT business_id, first_date AS sales_date, COUNT(*) AS new_customers
        FROM firsts
        GROUP BY business_id, first_date;
        SQL);

        // Returning customers per day (customers with total_tx >= 2 and purchased that day)
        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW vw_returning_customers_daily AS
        WITH counts AS (
            SELECT f.business_id, f.customer_id, COUNT(*) AS total_tx
            FROM fact_sales f
            WHERE f.customer_id IS NOT NULL
            GROUP BY f.business_id, f.customer_id
        )
        SELECT
            f.business_id,
            d.date AS sales_date,
            COUNT(DISTINCT f.customer_id) AS returning_customers
        FROM fact_sales f
        JOIN dim_date d ON d.id = f.date_id
        JOIN counts c ON c.business_id = f.business_id AND c.customer_id = f.customer_id
        WHERE f.customer_id IS NOT NULL AND c.total_tx >= 2
        GROUP BY f.business_id, d.date;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vw_returning_customers_daily');
        DB::unprepared('DROP VIEW IF EXISTS vw_new_customers_daily');
        DB::unprepared('DROP VIEW IF EXISTS vw_margin_daily');
        DB::unprepared('DROP VIEW IF EXISTS vw_cogs_daily');
        DB::unprepared('DROP VIEW IF EXISTS vw_sales_product_daily');
    }
};
