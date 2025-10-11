<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Adjust fact & dim names as needed
    DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW vw_customer_ltv AS
SELECT 
    c.id AS customer_dim_id,
    c.customer_nk AS customer_natural_key,
    c.name AS customer_name,
    MIN(d.date) AS first_purchase_date,
    MAX(d.date) AS last_purchase_date,
    COUNT(DISTINCT COALESCE(f.sales_transaction_id, f.id)) AS orders_count,
    SUM(f.total_amount) AS total_revenue,
    /* Margin not yet stored in fact_sales; placeholder NULL so downstream can COALESCE */
    NULL AS total_margin,
    CASE WHEN COUNT(DISTINCT COALESCE(f.sales_transaction_id, f.id)) = 0 THEN 0 
     ELSE SUM(f.total_amount) / COUNT(DISTINCT COALESCE(f.sales_transaction_id, f.id)) END AS avg_order_value,
    DATEDIFF(MAX(d.date), MIN(d.date)) AS lifecycle_days,
    CASE WHEN DATEDIFF(MAX(d.date), MIN(d.date)) > 0 
     THEN (SUM(f.total_amount) / NULLIF(DATEDIFF(MAX(d.date), MIN(d.date)),0)) * 30 
     ELSE SUM(f.total_amount) END AS approx_monthly_value
FROM fact_sales f
JOIN dim_customer c ON c.id = f.customer_id
JOIN dim_date d ON d.id = f.date_id
GROUP BY c.id, c.customer_nk, c.name;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_customer_ltv');
    }
};
