<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register Data Integrity Monitoring Command
Artisan::command('data:monitor-integrity 
    {--business-id= : Monitor specific business ID}
    {--send-alerts : Send email alerts for anomalies}
    {--days=7 : Number of days to check for anomalies}', function () {
    
    $businessId = $this->option('business-id');
    $sendAlerts = $this->option('send-alerts');
    $days = (int) $this->option('days');

    $this->info('Starting data integrity monitoring...');
    
    // Get business(es) to monitor
    if ($businessId) {
        $businesses = \App\Models\Business::where('id', $businessId)->get();
        if ($businesses->isEmpty()) {
            $this->error("Business with ID {$businessId} not found.");
            return 1;
        }
    } else {
        $businesses = \App\Models\Business::all();
    }

    $totalAnomalies = 0;
    $businessesWithIssues = [];

    foreach ($businesses as $business) {
        $this->info("Checking business: {$business->name} (ID: {$business->id})");

        try {
            $anomalies = \App\Services\DataIntegrityService::detectAnomalies($business->id, $days);
            
            if (!empty($anomalies)) {
                $totalAnomalies += count($anomalies);
                $businessesWithIssues[] = [
                    'business' => $business,
                    'anomalies' => $anomalies
                ];

                $this->warn("  Found " . count($anomalies) . " anomalies:");
                foreach ($anomalies as $anomaly) {
                    $this->line("    - {$anomaly['date']}: {$anomaly['message']}");
                }

                // Log alerts
                if ($sendAlerts) {
                    \Illuminate\Support\Facades\Log::warning("Data Integrity Alert", [
                        'business_id' => $business->id,
                        'business_name' => $business->name,
                        'anomalies_count' => count($anomalies),
                        'anomalies' => $anomalies
                    ]);
                    $this->info("  ✓ Alert logged");
                }
            } else {
                $this->info("  ✓ No anomalies detected");
            }

            // Generate and log integrity report
            $report = \App\Services\DataIntegrityService::generateIntegrityReport($business->id);
            $this->info("  Integrity Score: {$report['statistics']['integrity_score']}%");

        } catch (\Exception $e) {
            $this->error("  Error checking business {$business->id}: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("Data integrity monitoring error for business {$business->id}: " . $e->getMessage());
        }
    }

    // Summary
    $this->info("\n" . str_repeat('=', 50));
    $this->info("MONITORING SUMMARY");
    $this->info(str_repeat('=', 50));
    $this->info("Businesses checked: " . $businesses->count());
    $this->info("Total anomalies found: {$totalAnomalies}");
    $this->info("Businesses with issues: " . count($businessesWithIssues));

    if ($totalAnomalies > 0) {
        $this->warn("\nRecommendations:");
        $this->warn("1. Review flagged data for accuracy");
        $this->warn("2. Implement stricter validation rules");
        $this->warn("3. Consider data recovery for critical anomalies");
        return 1;
    } else {
        $this->info("\n✓ All systems running smoothly!");
        return 0;
    }
})->purpose('Monitor data integrity and detect anomalies');

// Schedule the monitoring to run daily
Schedule::command('data:monitor-integrity --send-alerts')
    ->daily()
    ->at('09:00')
    ->withoutOverlapping()
    ->description('Daily data integrity monitoring with alerts');
