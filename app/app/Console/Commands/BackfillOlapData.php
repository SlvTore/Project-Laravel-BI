<?php

namespace App\Console\Commands;

use App\Services\OlapWarehouseService;
use App\Models\Business;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOlapData extends Command
{
    protected $signature = 'olap:backfill {--business-id= : Business ID to backfill}';
    protected $description = 'Backfill OLAP fact tables from existing sales transactions';

    public function handle()
    {
        $businessId = $this->option('business-id');

        if (!$businessId) {
            // Use first business
            $business = Business::first();
            if (!$business) {
                $this->error('No business found. Please create a business first.');
                return 1;
            }
            $businessId = $business->id;
        }

        $this->info("Starting OLAP backfill for business ID: {$businessId}");

        try {
            $service = new OlapWarehouseService();

            $this->info("Loading facts from sales transactions...");
            $count = $service->loadFactsFromTransactions($businessId);

            $this->info("Backfill completed successfully!");
            $this->info("Created {$count} fact records");

            // Show results
            $this->newLine();
            $this->info("Current OLAP table counts:");
            $this->line("Dim Date: " . DB::table('dim_date')->count());
            $this->line("Dim Product: " . DB::table('dim_product')->count());
            $this->line("Dim Customer: " . DB::table('dim_customer')->count());
            $this->line("Fact Sales: " . DB::table('fact_sales')->count());

            try {
                $this->line("Daily Sales View: " . DB::table('vw_sales_daily')->count());
            } catch (\Exception $e) {
                $this->warn("Daily Sales View Error: " . $e->getMessage());
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
