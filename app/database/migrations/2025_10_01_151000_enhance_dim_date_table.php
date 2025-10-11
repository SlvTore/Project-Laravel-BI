<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_date', function (Blueprint $table) {
            // Only add columns that don't exist yet
            // month, year, quarter, month_name, day_name already exist from create_dim_tables migration
            
            if (!Schema::hasColumn('dim_date', 'day_of_month')) {
                $table->integer('day_of_month')->nullable()->after('day');
            }
            if (!Schema::hasColumn('dim_date', 'week_of_year')) {
                $table->integer('week_of_year')->nullable()->after('quarter');
            }
            if (!Schema::hasColumn('dim_date', 'day_of_week')) {
                $table->integer('day_of_week')->nullable()->after('week_of_year');
            }
            if (!Schema::hasColumn('dim_date', 'is_weekend')) {
                $table->boolean('is_weekend')->default(false)->after('day_of_week');
            }
            if (!Schema::hasColumn('dim_date', 'fiscal_period')) {
                $table->string('fiscal_period', 10)->nullable()->after('is_weekend');
            }
        });

        // Backfill existing dates with hierarchy data
        $this->backfillDateHierarchy();
    }

    public function down(): void
    {
        Schema::table('dim_date', function (Blueprint $table) {
            $table->dropColumn([
                'day_of_month',
                'week_of_year',
                'day_of_week',
                'is_weekend',
                'fiscal_period'
            ]);
        });
    }

    private function backfillDateHierarchy(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                UPDATE dim_date
                SET
                    day_of_month = DAY(date),
                    week_of_year = WEEK(date, 3),
                    day_of_week = DAYOFWEEK(date),
                    is_weekend = IF(DAYOFWEEK(date) IN (1, 7), 1, 0),
                    fiscal_period = CONCAT(YEAR(date), '-Q', QUARTER(date))
                WHERE day_of_month IS NULL
            ");
        } elseif ($driver === 'pgsql') {
            DB::statement("
                UPDATE dim_date
                SET
                    day_of_month = EXTRACT(DAY FROM date),
                    week_of_year = EXTRACT(WEEK FROM date),
                    day_of_week = EXTRACT(DOW FROM date) + 1,
                    is_weekend = CASE WHEN EXTRACT(DOW FROM date) IN (0, 6) THEN TRUE ELSE FALSE END,
                    fiscal_period = CONCAT(EXTRACT(YEAR FROM date), '-Q', EXTRACT(QUARTER FROM date))
                WHERE day_of_month IS NULL
            ");
        }
    }
};
