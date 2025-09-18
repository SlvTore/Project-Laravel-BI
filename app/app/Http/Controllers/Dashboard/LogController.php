<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    /**
     * Display activity log
     */
    public function index()
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found');
        }

        return view('log.index', compact('business'));
    }

    /**
     * Get activities data for AJAX requests
     */
    public function getActivitiesData(Request $request)
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $query = ActivityLog::where('business_id', $business->id)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by type if specified
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by date range if specified
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $activities = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'activities' => $activities->items(),
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'has_more' => $activities->hasMorePages()
            ]
        ]);
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(Request $request)
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $period = $request->get('period', 7); // days

        $stats = ActivityLog::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays($period))
            ->selectRaw('
                type,
                COUNT(*) as count,
                DATE(created_at) as date
            ')
            ->groupBy('type', 'date')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('type')
            ->map(function ($activities) {
                return [
                    'total' => $activities->sum('count'),
                    'daily' => $activities->pluck('count', 'date')
                ];
            });

        $totalActivities = ActivityLog::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays($period))
            ->count();

        return response()->json([
            'success' => true,
            'total_activities' => $totalActivities,
            'stats_by_type' => $stats,
            'period_days' => $period
        ]);
    }

    /**
     * Get user's business - helper method
     */
    private function getUserBusiness($user)
    {
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
}
