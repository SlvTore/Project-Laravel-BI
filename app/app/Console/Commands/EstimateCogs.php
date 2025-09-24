<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EstimateCogs extends Command
{
    protected $signature = 'olap:estimate-cogs {--business-id= : Limit to a specific business}';
    protected $description = 'Estimate and populate missing COGS amounts in fact_sales using dim_product cost baselines';

    public function handle(): int
    {
        $businessId = $this->option('business-id');

        $query = DB::table('fact_sales as f')
            ->join('dim_product as p', 'p.id', '=', 'f.product_id')
            ->whereNull('f.cogs_amount');

        if ($businessId) {
            $query->where('f.business_id', $businessId);
        }

        $rows = $query->select('f.id','f.quantity','p.cost_price')->limit(5000)->get();
        if ($rows->isEmpty()) {
            $this->info('No rows with NULL cogs_amount found.');
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($rows as $r) {
            $cogs = ($r->cost_price ?? 0) * ($r->quantity ?? 0);
            DB::table('fact_sales')->where('id', $r->id)->update(['cogs_amount' => $cogs]);
            $updated++;
        }

        $this->info("Updated {$updated} fact_sales rows with estimated COGS.");
        return self::SUCCESS;
    }
}
