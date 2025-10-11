<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Extend OLAP schema for all metrics.
 *
 * NOTE: This file was previously empty causing a Class not found error.
 * Implement the required structural changes here. For now it is a safe no-op
 * stub so migrations can proceed. Replace the TODO section with real DDL when
 * the design is finalized.
 */
class ExtendOlapSchemaForAllMetrics extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		if (Schema::hasTable('fact_sales')) {
			Schema::table('fact_sales', function (Blueprint $table) {
				$table->index(['business_id', 'date_id', 'product_id'], 'idx_fs_biz_date_product');
				// Add extended OLAP measure columns if they do not exist (idempotent for production & test env)
				if (!Schema::hasColumn('fact_sales', 'gross_revenue')) {
					$table->decimal('gross_revenue', 18, 2)->nullable()->after('total_amount');
				}
				if (!Schema::hasColumn('fact_sales', 'cogs_amount')) {
					$table->decimal('cogs_amount', 18, 2)->nullable()->after('gross_revenue');
				}
				if (!Schema::hasColumn('fact_sales', 'gross_margin_amount')) {
					$table->decimal('gross_margin_amount', 18, 2)->nullable()->after('cogs_amount');
				}
				if (!Schema::hasColumn('fact_sales', 'gross_margin_percent')) {
					$table->decimal('gross_margin_percent', 9, 4)->nullable()->after('gross_margin_amount');
				}
			});
		}

		// Skip complex view creation in unsupported drivers (e.g., SQLite during tests) or when base columns are missing
		if (DB::getDriverName() === 'sqlite') {
			return; // views will not be used in unit tests; service layer logic can be tested directly
		}

		$this->createOrReplaceViews();

		if (Schema::hasTable('dim_channel') && Schema::hasTable('businesses')) {
			$businessIds = DB::table('businesses')->pluck('id');
			foreach ($businessIds as $businessId) {
				foreach (['Online', 'Offline'] as $channel) {
					DB::table('dim_channel')->updateOrInsert(
						['business_id' => $businessId, 'name' => $channel],
						['updated_at' => now(), 'created_at' => now()]
					);
				}
			}
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		$views = [
			'vw_returning_customers_daily',
			'vw_new_customers_daily',
			'vw_margin_daily',
			'vw_cogs_daily',
			'vw_sales_product_daily',
			'vw_sales_daily',
			'vw_sales_unified',
		];

		foreach ($views as $view) {
			DB::statement("DROP VIEW IF EXISTS {$view}");
		}

		if (Schema::hasTable('fact_sales')) {
			Schema::table('fact_sales', function (Blueprint $table) {
				$table->dropIndex('idx_fs_biz_date_product');
			});
		}
	}

	protected function createOrReplaceViews(): void
	{
		if (Schema::hasTable('fact_sales') &&
			Schema::hasColumn('fact_sales', 'gross_revenue') &&
			Schema::hasColumn('fact_sales', 'cogs_amount') &&
			Schema::hasColumn('fact_sales', 'gross_margin_amount') &&
			Schema::hasColumn('fact_sales', 'gross_margin_percent')) {
			DB::statement('DROP VIEW IF EXISTS vw_sales_unified');
			DB::unprepared(<<<SQL
			CREATE VIEW vw_sales_unified AS
			SELECT
				f.id AS fact_sales_id,
				f.business_id,
				d.date AS sales_date,
				p.id AS product_dim_id,
				p.name AS product_name,
				p.category AS product_category,
				c.id AS customer_dim_id,
				c.name AS customer_name,
				ch.id AS channel_dim_id,
				ch.name AS channel_name,
				f.quantity,
				f.unit_price,
				f.discount,
				f.subtotal,
				f.total_amount,
				f.gross_revenue,
				f.cogs_amount,
				f.gross_margin_amount,
				f.gross_margin_percent
			FROM fact_sales f
			JOIN dim_date d ON d.id = f.date_id
			LEFT JOIN dim_product p ON p.id = f.product_id
			LEFT JOIN dim_customer c ON c.id = f.customer_id
			LEFT JOIN dim_channel ch ON ch.id = f.channel_id;
			SQL);
		}

		DB::statement('DROP VIEW IF EXISTS vw_sales_daily');
		DB::unprepared(<<<SQL
		CREATE VIEW vw_sales_daily AS
		SELECT
			f.business_id,
			d.date AS sales_date,
			SUM(f.gross_revenue) AS total_gross_revenue,
			SUM(f.total_amount) AS total_net_revenue,
			SUM(f.discount) AS total_discount,
			SUM(f.quantity) AS total_quantity,
			COUNT(DISTINCT COALESCE(f.sales_transaction_id, f.id)) AS transaction_count
		FROM fact_sales f
		JOIN dim_date d ON d.id = f.date_id
		GROUP BY f.business_id, d.date;
		SQL);

		DB::statement('DROP VIEW IF EXISTS vw_sales_product_daily');
		DB::unprepared(<<<SQL
		CREATE VIEW vw_sales_product_daily AS
		SELECT
			f.business_id,
			d.date AS sales_date,
			p.name AS product_name,
			SUM(f.quantity) AS total_quantity,
			SUM(f.gross_revenue) AS total_revenue,
			SUM(f.cogs_amount) AS total_cogs,
			SUM(f.gross_margin_amount) AS total_margin
		FROM fact_sales f
		JOIN dim_date d ON d.id = f.date_id
		LEFT JOIN dim_product p ON p.id = f.product_id
		GROUP BY f.business_id, d.date, p.name;
		SQL);

		DB::statement('DROP VIEW IF EXISTS vw_cogs_daily');
		DB::unprepared(<<<SQL
		CREATE VIEW vw_cogs_daily AS
		SELECT
			f.business_id,
			d.date AS sales_date,
			SUM(f.cogs_amount) AS total_cogs
		FROM fact_sales f
		JOIN dim_date d ON d.id = f.date_id
		GROUP BY f.business_id, d.date;
		SQL);

		DB::statement('DROP VIEW IF EXISTS vw_margin_daily');
		DB::unprepared(<<<SQL
		CREATE VIEW vw_margin_daily AS
		SELECT
			f.business_id,
			d.date AS sales_date,
			SUM(f.gross_margin_amount) AS total_margin,
			CASE
				WHEN SUM(f.gross_revenue) = 0 THEN 0
				ELSE SUM(f.gross_margin_amount) / NULLIF(SUM(f.gross_revenue), 0)
			END AS margin_ratio
		FROM fact_sales f
		JOIN dim_date d ON d.id = f.date_id
		GROUP BY f.business_id, d.date;
		SQL);

		DB::statement('DROP VIEW IF EXISTS vw_new_customers_daily');
		DB::unprepared(<<<SQL
		CREATE VIEW vw_new_customers_daily AS
		SELECT
			f.business_id,
			d.date AS sales_date,
			COUNT(DISTINCT CASE
				WHEN f.customer_id IS NOT NULL AND d.date = (
					SELECT MIN(d2.date)
					FROM fact_sales f2
					JOIN dim_date d2 ON d2.id = f2.date_id
					WHERE f2.customer_id = f.customer_id
				) THEN f.customer_id
			END) AS new_customers
		FROM fact_sales f
		JOIN dim_date d ON d.id = f.date_id
		GROUP BY f.business_id, d.date;
		SQL);

		DB::statement('DROP VIEW IF EXISTS vw_returning_customers_daily');
		DB::unprepared(<<<SQL
		CREATE VIEW vw_returning_customers_daily AS
		SELECT
			f.business_id,
			d.date AS sales_date,
			COUNT(DISTINCT CASE
				WHEN f.customer_id IS NOT NULL AND d.date > (
					SELECT MIN(d2.date)
					FROM fact_sales f2
					JOIN dim_date d2 ON d2.id = f2.date_id
					WHERE f2.customer_id = f.customer_id
				) THEN f.customer_id
			END) AS returning_customers
		FROM fact_sales f
		JOIN dim_date d ON d.id = f.date_id
		GROUP BY f.business_id, d.date;
		SQL);
	}
}

