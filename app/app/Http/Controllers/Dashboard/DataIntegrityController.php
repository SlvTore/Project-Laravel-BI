<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DataIntegrityService;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataIntegrityController extends Controller
{
    /**
     * Get user's business - helper method
     */
    private function getUserBusiness()
    {
        $user = Auth::user();
        
        // Try to get business through Business model directly
        $business = Business::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->first();
        
        // Fallback to owned business
        if (!$business) {
            $business = Business::where('user_id', $user->id)->first();
        }
        
        return $business;
    }

    /**
     * Show data integrity dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getUserBusiness();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found');
        }

        // Generate integrity report
        $report = DataIntegrityService::generateIntegrityReport($business->id);

        return view('dashboard-integrity.index', compact('report', 'business'));
    }

    /**
     * Detect anomalies for a specific business
     */
    public function detectAnomalies(Request $request)
    {
        $business = $this->getUserBusiness();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $days = $request->input('days', 7);
        $anomalies = DataIntegrityService::detectAnomalies($business->id, $days);

        return response()->json([
            'success' => true,
            'anomalies' => $anomalies,
            'total_anomalies' => count($anomalies)
        ]);
    }

    /**
     * Recover data from backup
     */
    public function recoverData(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'model_type' => 'required|in:SalesData,MetricRecord'
        ]);

        $business = $this->getUserBusiness();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $result = DataIntegrityService::recoverData(
            $business->id,
            $request->date,
            $request->model_type
        );

        return response()->json($result);
    }

    /**
     * Download integrity report
     */
    public function downloadReport(Request $request)
    {
        $business = $this->getUserBusiness();

        if (!$business) {
            return redirect()->back()->with('error', 'No business found');
        }

        $report = DataIntegrityService::generateIntegrityReport($business->id);

        $filename = 'data_integrity_report_' . $business->id . '_' . now()->format('Y-m-d') . '.json';

        return response()->json($report)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get backup history for a specific date range
     */
    public function getBackupHistory(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $business = $this->getUserBusiness();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $backups = \App\Models\ActivityLog::where('business_id', $business->id)
            ->where('type', 'data_backup')
            ->whereBetween('created_at', [$request->start_date, $request->end_date])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'date' => $backup->created_at->format('Y-m-d H:i:s'),
                    'model_type' => $backup->metadata['model_type'] ?? 'Unknown',
                    'action' => $backup->metadata['action'] ?? 'Unknown',
                    'user_name' => $backup->user->name ?? 'System',
                    'description' => $backup->description
                ];
            });

        return response()->json([
            'success' => true,
            'backups' => $backups,
            'total' => $backups->count()
        ]);
    }
}
