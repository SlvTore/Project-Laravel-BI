<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
            CREATE OR REPLACE VIEW vw_sales_daily AS
            SELECT
                fs.business_id,
                dd.date AS sales_date,
                SUM(fs.total_amount) AS total_revenue,
                SUM(fs.subtotal) AS subtotal_amount,
                SUM(fs.discount) AS total_discount,
                SUM(fs.tax_amount) AS total_tax,
                SUM(fs.shipping_cost) AS total_shipping,
                SUM(fs.quantity) AS total_quantity,
                COUNT(DISTINCT fs.sales_transaction_id) AS transaction_count
            FROM fact_sales fs
            INNER JOIN dim_date dd ON dd.id = fs.date_id
            GROUP BY fs.business_id, dd.date
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_sales_daily');
    }
};
