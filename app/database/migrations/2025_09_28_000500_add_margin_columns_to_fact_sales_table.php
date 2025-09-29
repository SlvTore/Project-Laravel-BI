<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('fact_sales')) {
			return;
		}

		Schema::table('fact_sales', function (Blueprint $table) {
			if (!Schema::hasColumn('fact_sales', 'gross_revenue')) {
				$table->decimal('gross_revenue', 18, 2)->default(0)->after('total_amount');
			}
			if (!Schema::hasColumn('fact_sales', 'cogs_amount')) {
				$table->decimal('cogs_amount', 18, 2)->default(0)->after('gross_revenue');
			}
			if (!Schema::hasColumn('fact_sales', 'gross_margin_amount')) {
				$table->decimal('gross_margin_amount', 18, 2)->default(0)->after('cogs_amount');
			}
			if (!Schema::hasColumn('fact_sales', 'gross_margin_percent')) {
				$table->decimal('gross_margin_percent', 7, 4)->default(0)->after('gross_margin_amount');
			}
		});

		DB::table('fact_sales')->update([
			'gross_revenue' => DB::raw('(quantity * unit_price) - discount'),
			'cogs_amount' => 0,
			'gross_margin_amount' => DB::raw('((quantity * unit_price) - discount)'),
			'gross_margin_percent' => DB::raw('CASE WHEN (quantity * unit_price) = 0 THEN 0 ELSE (((quantity * unit_price) - discount) / NULLIF((quantity * unit_price), 0)) * 100 END'),
		]);
	}

	public function down(): void
	{
		if (!Schema::hasTable('fact_sales')) {
			return;
		}

		Schema::table('fact_sales', function (Blueprint $table) {
			if (Schema::hasColumn('fact_sales', 'gross_margin_percent')) {
				$table->dropColumn('gross_margin_percent');
			}
			if (Schema::hasColumn('fact_sales', 'gross_margin_amount')) {
				$table->dropColumn('gross_margin_amount');
			}
			if (Schema::hasColumn('fact_sales', 'cogs_amount')) {
				$table->dropColumn('cogs_amount');
			}
			if (Schema::hasColumn('fact_sales', 'gross_revenue')) {
				$table->dropColumn('gross_revenue');
			}
		});
	}
};
