<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WarehouseValidateCommand extends Command
{
    protected $signature = 'warehouse:validate {--fix= : Attempt auto-fix unknown dimension references}';
    protected $description = 'Validate data warehouse referential integrity (fact_sales vs dimensions)';

    public function handle(): int
    {
        $this->info('Starting warehouse validation...');

        $issues = [];
        // Dimension mappings aligned to current schema (fact_sales uses *_id referencing dim tables id PK)
        $checks = [
            ['fact_col' => 'customer_id', 'dim_table' => 'dim_customer', 'dim_col' => 'id'],
            ['fact_col' => 'product_id', 'dim_table' => 'dim_product', 'dim_col' => 'id'],
            ['fact_col' => 'date_id', 'dim_table' => 'dim_date', 'dim_col' => 'id'],
            ['fact_col' => 'channel_id', 'dim_table' => 'dim_channel', 'dim_col' => 'id', 'optional' => true],
        ];

        // Include promotion dimension only if both tables/columns exist in schema
        if (DB::getSchemaBuilder()->hasTable('dim_promotion') && DB::getSchemaBuilder()->hasTable('fact_sales') && DB::getSchemaBuilder()->hasColumn('fact_sales', 'promotion_key')) {
            $checks[] = ['fact_col' => 'promotion_key', 'dim_table' => 'dim_promotion', 'dim_col' => 'promotion_key', 'optional' => true];
        }

        foreach ($checks as $c) {
            if (!DB::getSchemaBuilder()->hasTable('fact_sales')) {
                $this->error('fact_sales table missing.');
                return 1;
            }
            if (!DB::getSchemaBuilder()->hasTable($c['dim_table'])) {
                if (!empty($c['optional'])) {
                    $this->warn("Skipping optional dimension {$c['dim_table']} (not found)");
                    continue;
                }
                $this->error("Dimension table {$c['dim_table']} not found");
                $issues[] = "missing:{$c['dim_table']}";
                continue;
            }
            // Only evaluate if the fact column actually exists (allows incremental evolution)
            if (!DB::getSchemaBuilder()->hasColumn('fact_sales', $c['fact_col'])) {
                $this->warn("Skipping check for missing fact column {$c['fact_col']}");
                continue;
            }
            $count = DB::table('fact_sales as f')
                ->leftJoin($c['dim_table'].' as d', 'f.'.$c['fact_col'], '=', 'd.'.$c['dim_col'])
                ->where(function($q) use ($c) {
                    $q->whereNull('f.'.$c['fact_col'])
                      ->orWhereNull('d.'.$c['dim_col']);
                })
                ->count();
            if ($count > 0) {
                $issues[] = [
                    'fact_column' => $c['fact_col'],
                    'dimension' => $c['dim_table'],
                    'missing_rows' => $count
                ];
            }
        }

        if (empty($issues)) {
            $this->info('No referential issues detected. ✅');
        } else {
            $this->warn('Referential gaps found:');
            $this->table(['Fact Column','Dimension','Missing Rows'], array_map(fn($i)=>[$i['fact_column'],$i['dimension'],$i['missing_rows']], array_filter($issues,'is_array')));
        }

        if ($this->option('fix')) {
            $this->info('Attempting auto-fix using UNKNOWN members...');
            $fixed = 0;
            // Example fix for promotion dimension
            if (DB::getSchemaBuilder()->hasTable('dim_promotion') && DB::table('dim_promotion')->where('promotion_code','UNKNOWN')->exists()) {
                $unknownKey = DB::table('dim_promotion')->where('promotion_code','UNKNOWN')->value('promotion_key');
                $updated = DB::table('fact_sales as f')
                    ->leftJoin('dim_promotion as p','f.promotion_key','=','p.promotion_key')
                    ->whereNull('p.promotion_key')
                    ->update(['promotion_key'=>$unknownKey]);
                $fixed += $updated;
                if ($updated) $this->line("  - Fixed $updated missing promotion_key rows");
            }
            $this->info("Auto-fix complete. Rows adjusted: $fixed");
        }

        return empty($issues) ? 0 : 2;
    }
}
