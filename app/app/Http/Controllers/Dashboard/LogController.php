<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LogController extends Controller
{
    use LogsActivity;
    /**
     * Display activity log
     */
    public function index(Request $request)
    {
        // Log dashboard access
        $this->logDashboardActivity('Activity Log');

        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found');
        }

        // Get live/real-time data (last 7 days for better content)
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(6); // 7 days total

        // Fetch activities (business scoped) - increase limit for more content
        $recentActivities = $this->getRecentActivities($business->id, $startDate, $endDate);
        $groupedActivities = $recentActivities
            ->groupBy(fn ($activity) => $activity->created_at->toDateString())
            ->sortKeysDesc();

        // Top active users (last 30 days) cached for 5 minutes
        $topUsers = Cache::remember("activity.top5.business.".$business->id, 300, function () use ($business) {
            $since = Carbon::now()->subDays(30);
            return ActivityLog::with('user')
                ->select('id','user_id','type','created_at')
                ->where('business_id', $business->id)
                ->where('created_at', '>=', $since)
                ->get()
                ->groupBy('user_id')
                ->map(fn($col) => [
                    'user' => $col->first()->user,
                    'count' => $col->count(),
                ])
                ->sortByDesc('count')
                ->take(5)
                ->values();
        });

        $totalLast30 = $topUsers->sum('count');
        $colorMap = [
            'user_joined' => 'success',
            'data_input' => 'primary',
            'promotion' => 'warning',
            'auth' => 'info',
        ];

        $summaryStats = $this->buildSummaryStats($recentActivities, $colorMap, $startDate, $endDate);
        $activityTrends = $this->buildActivityTrends($business->id, 7);
        $recentAlerts = $this->getRecentAlerts($business->id, 5);

        return view('log.index', [
            'business' => $business,
            'groupedActivities' => $groupedActivities,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'topUsers' => $topUsers,
            'totalLast30' => $totalLast30,
            'colorMap' => $colorMap,
            'summaryStats' => $summaryStats,
            'activityTrends' => $activityTrends,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    private function getRecentActivities(int $businessId, Carbon $startDate, Carbon $endDate)
    {
        return ActivityLog::with(['user'])
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->limit(500) // Increase limit for more comprehensive view
            ->get();
    }

    private function buildSummaryStats($activities, array $colorMap, Carbon $startDate, Carbon $endDate)
    {
        $dayCount = $startDate->diffInDays($endDate) + 1;

        $byType = $activities
            ->groupBy('type')
            ->map(function ($items, $type) use ($colorMap) {
                return [
                    'type' => $type,
                    'label' => Str::headline(str_replace('_', ' ', $type)),
                    'count' => $items->count(),
                    'color' => $colorMap[$type] ?? 'secondary',
                ];
            })
            ->sortByDesc('count')
            ->values();

        return [
            'total' => $activities->count(),
            'unique_users' => $activities->pluck('user_id')->filter()->unique()->count(),
            'types' => $byType,
            'top_type' => $byType->first(),
            'avg_per_day' => $dayCount > 0 ? round($activities->count() / $dayCount, 1) : $activities->count(),
            'window_label' => __('Last :days days', ['days' => $dayCount]),
            'date_range' => $startDate->format('M d') . ' – ' . $endDate->format('M d, Y'),
            'day_count' => $dayCount,
        ];
    }

    private function buildActivityTrends(int $businessId, int $days = 7)
    {
        $start = Carbon::today()->subDays($days - 1);

        $raw = ActivityLog::where('business_id', $businessId)
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $current = $start->copy()->addDays($i);
            $dateKey = $current->toDateString();
            $labels[] = $current->format('M d');
            $values[] = (int) optional($raw->get($dateKey))->count ?? 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function getRecentAlerts(int $businessId, int $limit = 5)
    {
        return ActivityLog::with('user')
            ->where('business_id', $businessId)
            ->whereIn('type', ['promotion', 'auth'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
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
