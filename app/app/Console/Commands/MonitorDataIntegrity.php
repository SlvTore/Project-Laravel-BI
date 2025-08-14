<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\User;
use App\Services\DataIntegrityService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MonitorDataIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:monitor-integrity 
                          {--business-id= : Monitor specific business ID}
                          {--send-alerts : Send email alerts for anomalies}
                          {--days=7 : Number of days to check for anomalies}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor data integrity and detect anomalies across all businesses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data integrity monitoring...');

        $businessId = $this->option('business-id');
        $sendAlerts = $this->option('send-alerts');
        $days = (int) $this->option('days');

        if ($businessId) {
            $businesses = Business::where('id', $businessId)->get();
            if ($businesses->isEmpty()) {
                $this->error("Business with ID {$businessId} not found.");
                return 1;
            }
        } else {
            $businesses = Business::all();
        }

        $totalAnomalies = 0;
        $businessesWithIssues = [];

        foreach ($businesses as $business) {
            $this->info("Checking business: {$business->name} (ID: {$business->id})");

            try {
                $anomalies = DataIntegrityService::detectAnomalies($business->id, $days);
                
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

                    // Send alerts if requested
                    if ($sendAlerts) {
                        $this->sendAlert($business, $anomalies);
                    }
                } else {
                    $this->info("  ✓ No anomalies detected");
                }

                // Generate and store integrity report
                $report = DataIntegrityService::generateIntegrityReport($business->id);
                $this->info("  Integrity Score: {$report['statistics']['integrity_score']}%");

            } catch (\Exception $e) {
                $this->error("  Error checking business {$business->id}: " . $e->getMessage());
                Log::error("Data integrity monitoring error for business {$business->id}: " . $e->getMessage());
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
            return 1; // Exit with error code to indicate issues found
        } else {
            $this->info("\n✓ All systems running smoothly!");
            return 0;
        }
    }

    /**
     * Send alert email to business owner
     */
    private function sendAlert($business, $anomalies)
    {
        try {
            // Get business owner
            $owner = User::find($business->user_id);
            
            if (!$owner || !$owner->email) {
                $this->warn("    No owner email found for business {$business->id}");
                return;
            }

            // Create email content
            $subject = "Data Integrity Alert - {$business->name}";
            $message = $this->buildAlertMessage($business, $anomalies);

            // For now, just log the alert (you can implement actual email sending)
            Log::warning("Data Integrity Alert", [
                'business_id' => $business->id,
                'business_name' => $business->name,
                'owner_email' => $owner->email,
                'anomalies_count' => count($anomalies),
                'anomalies' => $anomalies
            ]);

            $this->info("    ✓ Alert logged for {$owner->email}");

        } catch (\Exception $e) {
            $this->error("    Failed to send alert: " . $e->getMessage());
        }
    }

    /**
     * Build alert message content
     */
    private function buildAlertMessage($business, $anomalies)
    {
        $message = "Data integrity issues detected for {$business->name}:\n\n";
        
        foreach ($anomalies as $anomaly) {
            $message .= "• {$anomaly['date']}: {$anomaly['message']}\n";
        }

        $message .= "\nRecommended Actions:\n";
        $message .= "1. Review the flagged data for accuracy\n";
        $message .= "2. Correct any obvious errors\n";
        $message .= "3. Contact support if you need assistance\n";
        $message .= "\nAccess your dashboard: " . url('/dashboard') . "\n";

        return $message;
    }

    /**
     * Show detailed anomaly information
     */
    private function showAnomalyDetails($anomaly)
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Type', $anomaly['type']],
                ['Date', $anomaly['date']],
                ['Message', $anomaly['message']]
            ]
        );
    }
}
