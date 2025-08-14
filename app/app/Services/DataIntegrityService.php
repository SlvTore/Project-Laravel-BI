<?php

namespace App\Services;

use App\Models\SalesData;
use App\Models\ActivityLog;
use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DataIntegrityService
{
    /**
     * Backup data before any major changes
     */
    public static function backupData($model, $action = 'update')
    {
        try {
            $backupData = [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'action' => $action,
                'old_data' => $model->getOriginal(),
                'new_data' => $model->getAttributes(),
                'backup_timestamp' => now(),
                'user_id' => Auth::id()
            ];

            ActivityLog::create([
                'business_id' => $model->business_id ?? null,
                'user_id' => Auth::id(),
                'type' => 'data_backup',
                'title' => 'Data Backup',
                'description' => "Backup before {$action} on " . class_basename($model),
                'metadata' => $backupData,
                'icon' => 'bi-shield-check',
                'color' => 'info'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Data backup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate customer data integrity
     */
    public static function validateCustomerData($businessId, $date, $newCustomerCount, $totalCustomerCount)
    {
        $errors = [];

        // Basic validation
        if ($newCustomerCount < 0) {
            $errors[] = 'Jumlah pelanggan baru tidak boleh negatif';
        }

        if ($totalCustomerCount < 0) {
            $errors[] = 'Total pelanggan tidak boleh negatif';
        }

        if ($newCustomerCount > $totalCustomerCount) {
            $errors[] = 'Pelanggan baru tidak boleh lebih besar dari total pelanggan';
        }

        // Check against previous data for anomalies
        $previousData = SalesData::where('business_id', $businessId)
            ->where('sales_date', '<', $date)
            ->orderBy('sales_date', 'desc')
            ->first();

        if ($previousData) {
            // Check for sudden large changes
            $previousTotal = $previousData->total_customer_count ?? 0;
            if ($previousTotal > 0) {
                $changePercentage = abs($totalCustomerCount - $previousTotal) / $previousTotal * 100;
                
                if ($changePercentage > 50) {
                    $errors[] = "Perubahan total pelanggan sangat besar ({$changePercentage}%). Harap verifikasi data.";
                }
            }

            // Check if total customers decreased significantly
            if ($totalCustomerCount < $previousTotal * 0.7) {
                $errors[] = 'Total pelanggan turun drastis. Apakah ada kesalahan input?';
            }
        }

        return $errors;
    }

    /**
     * Validate sales data integrity
     */
    public static function validateSalesData($businessId, $date, $totalRevenue, $totalCogs = null)
    {
        $errors = [];

        if ($totalRevenue < 0) {
            $errors[] = 'Total revenue tidak boleh negatif';
        }

        if ($totalCogs !== null && $totalCogs < 0) {
            $errors[] = 'Total COGS tidak boleh negatif';
        }

        if ($totalCogs !== null && $totalCogs > $totalRevenue) {
            $errors[] = 'COGS tidak boleh lebih besar dari revenue';
        }

        // Check against previous data
        $previousData = SalesData::where('business_id', $businessId)
            ->where('sales_date', '<', $date)
            ->orderBy('sales_date', 'desc')
            ->first();

        if ($previousData && $previousData->total_revenue > 0) {
            $changePercentage = abs($totalRevenue - $previousData->total_revenue) / $previousData->total_revenue * 100;
            
            if ($changePercentage > 200) {
                $errors[] = "Perubahan revenue sangat besar ({$changePercentage}%). Harap verifikasi data.";
            }
        }

        return $errors;
    }

    /**
     * Detect data anomalies for a business
     */
    public static function detectAnomalies($businessId, $days = 7)
    {
        $anomalies = [];

        try {
            $recentData = SalesData::where('business_id', $businessId)
                ->where('sales_date', '>=', Carbon::now()->subDays($days))
                ->orderBy('sales_date', 'desc')
                ->get();

            foreach ($recentData as $data) {
                // Check customer data consistency
                if ($data->new_customer_count > $data->total_customer_count) {
                    $anomalies[] = [
                        'type' => 'customer_inconsistency',
                        'date' => $data->sales_date,
                        'message' => "Pelanggan baru ({$data->new_customer_count}) > Total pelanggan ({$data->total_customer_count})"
                    ];
                }

                // Check negative values
                if ($data->total_revenue < 0 || $data->total_customer_count < 0) {
                    $anomalies[] = [
                        'type' => 'negative_values',
                        'date' => $data->sales_date,
                        'message' => 'Ditemukan nilai negatif pada data'
                    ];
                }

                // Check COGS > Revenue
                if ($data->total_cogs > $data->total_revenue && $data->total_revenue > 0) {
                    $anomalies[] = [
                        'type' => 'cogs_revenue_mismatch',
                        'date' => $data->sales_date,
                        'message' => "COGS ({$data->total_cogs}) > Revenue ({$data->total_revenue})"
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Anomaly detection failed: ' . $e->getMessage());
        }

        return $anomalies;
    }

    /**
     * Recover data from backup
     */
    public static function recoverData($businessId, $date, $modelType = 'SalesData')
    {
        try {
            $backup = ActivityLog::where('business_id', $businessId)
                ->where('type', 'data_backup')
                ->whereDate('created_at', $date)
                ->whereJsonContains('metadata->model_type', "App\\Models\\{$modelType}")
                ->latest()
                ->first();

            if (!$backup || !isset($backup->metadata['old_data'])) {
                return ['success' => false, 'message' => 'Backup data tidak ditemukan'];
            }

            $oldData = $backup->metadata['old_data'];
            
            // Restore data
            if ($modelType === 'SalesData') {
                SalesData::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'sales_date' => $date
                    ],
                    $oldData
                );
            }

            // Log recovery action
            ActivityLog::create([
                'business_id' => $businessId,
                'user_id' => Auth::id(),
                'type' => 'data_recovery',
                'title' => 'Data Recovery',
                'description' => "Data restored from backup for {$date}",
                'metadata' => ['recovered_data' => $oldData],
                'icon' => 'bi-arrow-clockwise',
                'color' => 'warning'
            ]);

            return ['success' => true, 'message' => 'Data berhasil direstore'];

        } catch (\Exception $e) {
            Log::error('Data recovery failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Recovery gagal: ' . $e->getMessage()];
        }
    }

    /**
     * Generate data integrity report
     */
    public static function generateIntegrityReport($businessId)
    {
        $report = [
            'business_id' => $businessId,
            'generated_at' => now(),
            'anomalies' => self::detectAnomalies($businessId, 30),
            'statistics' => [],
            'recommendations' => []
        ];

        // Calculate statistics
        $totalRecords = SalesData::where('business_id', $businessId)->count();
        $recordsWithAnomalies = 0;

        foreach ($report['anomalies'] as $anomaly) {
            $recordsWithAnomalies++;
        }

        $report['statistics'] = [
            'total_records' => $totalRecords,
            'records_with_anomalies' => $recordsWithAnomalies,
            'integrity_score' => $totalRecords > 0 ? (($totalRecords - $recordsWithAnomalies) / $totalRecords) * 100 : 100
        ];

        // Generate recommendations
        if (!empty($report['anomalies'])) {
            $report['recommendations'][] = 'Review dan perbaiki data yang terdeteksi memiliki anomali';
            $report['recommendations'][] = 'Implementasikan validasi input yang lebih ketat';
            $report['recommendations'][] = 'Setup monitoring harian untuk deteksi dini';
        }

        return $report;
    }
}
