<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeedsController extends Controller
{
    /**
     * Display the dashboard feeds page
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        // Handle date range filter (default: today and previous 2 days; max span: 3 days)
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');

        $startDate = $startDateParam ? Carbon::parse($startDateParam) : Carbon::now()->subDays(2)->startOfDay();
        $endDate = $endDateParam ? Carbon::parse($endDateParam) : Carbon::now()->endOfDay();

        // Normalize order if needed
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // Enforce max range of 3 calendar days (span <= 2 days difference)
        if ($startDate->diffInDays($endDate) > 2) {
            $endDate = $startDate->copy()->addDays(2)->endOfDay();
        }

        // Use date-only strings for view binding
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

    $activities = $this->getRecentActivities($business, $startDateString, $endDateString, $request->get('page', 1));
        $alerts = $this->getMetricAlerts($business);
        $insights = $this->getMetricInsights($business);

        if ($request->ajax()) {
            return response()->json([
                'activities' => $activities,
                'hasMore' => $activities->count() >= 20
            ]);
        }

        return view('dashboard-feeds.index', [
            'activities' => $activities,
            'alerts' => $alerts,
            'insights' => $insights,
            'business' => $business,
            'startDate' => $startDateString,
            'endDate' => $endDateString,
        ]);
    }

    /**
     * Get recent activities for the business
     */
    private function getRecentActivities($business, $startDate = null, $endDate = null, $page = 1)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Get activities from ActivityLog table first
        $loggedActivities = ActivityLog::where('business_id', $business->id)
            ->with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $activities = collect();

        foreach ($loggedActivities as $log) {
            $activities->push([
                'type' => $log->type,
                'title' => $log->title,
                'description' => $log->description,
                'time' => $log->created_at,
                'icon' => $log->icon,
                'color' => $log->color,
                'user' => $log->user,
                'metadata' => $log->metadata,
                'date_group' => $log->created_at->format('Y-m-d')
            ]);
        }

        // If we don't have enough logged activities, fall back to generating from records
        if ($activities->count() < $perPage && $page == 1) {
            // Get recent metric records for fallback
            $recentRecords = MetricRecord::whereHas('businessMetric', function ($query) use ($business) {
                    $query->where('business_id', $business->id);
                })
                ->with(['businessMetric', 'user'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->limit($perPage - $activities->count())
                ->get();

            foreach ($recentRecords as $record) {
                // Check if this activity is already logged
                $exists = $activities->firstWhere(function ($activity) use ($record) {
                    return $activity['type'] === 'data_input' &&
                           isset($activity['metadata']['metric_id']) &&
                           $activity['metadata']['metric_id'] == $record->business_metric_id &&
                           $activity['time']->diffInMinutes($record->created_at) < 5;
                });

                if (!$exists) {
                    $activities->push([
                        'type' => 'data_input',
                        'title' => 'Data Input',
                        'description' => ($record->user ? $record->user->name : 'System') . " updated {$record->businessMetric->metric_name}",
                        'time' => $record->created_at,
                        'icon' => 'bi-graph-up',
                        'color' => 'primary',
                        'user' => $record->user,
                        'metric' => $record->businessMetric,
                        'value' => $record->value,
                        'date_group' => $record->created_at->format('Y-m-d')
                    ]);
                }
            }
        }

        return $activities->sortByDesc('time')->take(20);
    }

    /**
     * Get metric alerts (empty data, significant changes)
     */
    private function getMetricAlerts($business)
    {
        $alerts = collect();

        $metrics = $business->metrics()->where('is_active', true)->get();

        foreach ($metrics as $metric) {
            // Check for empty data in last 7 days
            $recentRecords = $metric->records()
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count();

            if ($recentRecords == 0) {
                $alerts->push([
                    'type' => 'empty_data',
                    'title' => 'No Recent Data',
                    'description' => "No data has been recorded for {$metric->metric_name} in the last 7 days",
                    'metric' => $metric,
                    'severity' => 'warning',
                    'icon' => 'bi-exclamation-triangle'
                ]);
            }

            // Check for significant changes
            $lastTwoRecords = $metric->records()
                ->orderBy('created_at', 'desc')
                ->limit(2)
                ->get();

            if ($lastTwoRecords->count() == 2) {
                $current = $lastTwoRecords->first()->value;
                $previous = $lastTwoRecords->last()->value;

                if ($previous > 0) {
                    $changePercent = (($current - $previous) / $previous) * 100;

                    if (abs($changePercent) > 50) {
                        $alerts->push([
                            'type' => 'significant_change',
                            'title' => 'Significant Change Detected',
                            'description' => "{$metric->metric_name} " .
                                ($changePercent > 0 ? 'increased' : 'decreased') .
                                " by " . abs(round($changePercent, 1)) . "%",
                            'metric' => $metric,
                            'severity' => abs($changePercent) > 80 ? 'danger' : 'info',
                            'icon' => $changePercent > 0 ? 'bi-trending-up' : 'bi-trending-down',
                            'change' => $changePercent
                        ]);
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * Get metric insights and trends
     */
    private function getMetricInsights($business)
    {
        $insights = collect();

        $metrics = $business->metrics()->where('is_active', true)->get();

        foreach ($metrics as $metric) {
            $records = $metric->records()
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->orderBy('created_at')
                ->get();

            if ($records->count() >= 3) {
                $trend = $this->calculateTrend($records);

                $insights->push([
                    'metric' => $metric,
                    'trend' => $trend,
                    'latest_value' => $records->last()->value,
                    'records_count' => $records->count(),
                    'period' => '30 days'
                ]);
            }
        }

        return $insights;
    }

    /**
     * Calculate trend from records
     */
    private function calculateTrend($records)
    {
        if ($records->count() < 2) {
            return 'stable';
        }

        $values = $records->pluck('value')->toArray();
        $n = count($values);

        // Simple linear regression to determine trend
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $values[$i];

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        if ($slope > 0.1) {
            return 'increasing';
        } elseif ($slope < -0.1) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Get activities data for AJAX requests
     */
    public function getActivitiesData(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $activities = $this->getRecentActivities($business);

        return response()->json([
            'activities' => $activities->values(),
            'total' => $activities->count()
        ]);
    }
}
