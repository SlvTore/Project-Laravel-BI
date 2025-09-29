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
            $summary = $service->loadFactsFromTransactions($businessId);

            $this->info("Backfill completed successfully!");
            $this->info("Created {$summary['records']} fact records");
            $this->info("Gross revenue: " . number_format($summary['gross_revenue'], 2));
            $this->info("COGS: " . number_format($summary['cogs_amount'], 2));
            $this->info("Margin: " . number_format($summary['gross_margin_amount'], 2));

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
