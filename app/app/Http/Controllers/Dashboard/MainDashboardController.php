<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\WeatherService;
use App\Services\NewsService;
use App\Services\GeminiAIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MainDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $greeting = $this->getGreeting();
        $now = now();

        $business = $user->isBusinessOwner() ? $user->primaryBusiness()->first() : $user->businesses()->first();

        $weather = app(WeatherService::class);
        $weatherData = $weather->getCurrent(); // may be null if no API key

        // Get metrics with chart data
        $metrics = collect();
        if ($business) {
            $metrics = $business->metrics()->where('is_active', true)->take(6)->get()->map(function($metric) {
                // Get last 30 days of data for mini chart
                $records = $metric->records()
                    ->where('record_date', '>=', now()->subDays(30))
                    ->orderBy('record_date')
                    ->get()
                    ->map(function($record) {
                        return [
                            'date' => $record->record_date->format('M j'),
                            'value' => (float)$record->value
                        ];
                    });

                // Fill empty days with previous value or 0
                $chartData = [];
                $dates = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $dates[] = $date->format('M j');
                    $found = $records->firstWhere('date', $date->format('M j'));
                    $chartData[] = $found ? $found['value'] : ($chartData ? end($chartData) : 0);
                }

                $metric->chart_data = $chartData;
                $metric->chart_labels = $dates;
                $metric->formatted_value = $this->formatMetricValue($metric->current_value, $metric->unit);

                // Calculate change from previous period
                $prevValue = $records->count() > 1 ? $records->slice(-2, 1)->first()['value'] ?? 0 : 0;
                $currentValue = $metric->current_value ?? 0;
                if ($prevValue > 0) {
                    $changePercent = round((($currentValue - $prevValue) / $prevValue) * 100, 1);
                    $metric->formatted_change = ($changePercent >= 0 ? '+' : '') . $changePercent . '%';
                    $metric->change_status = $changePercent >= 0 ? 'increase' : 'decrease';
                } else {
                    $metric->formatted_change = 'New';
                    $metric->change_status = 'neutral';
                }

                return $metric;
            });
        }

        // Combined chart data for all metrics
        $combinedChartData = $this->getCombinedMetricsChart($business);

        // Statistics data for 30 days
        $stats = [
            'total_updates' => 0,
            'avg_value' => 0,
            'last_update' => null
        ];

        if ($business) {
            $recentRecords = \App\Models\MetricRecord::whereHas('businessMetric', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('created_at', '>=', now()->subDays(30))->get();

            $stats['total_updates'] = $recentRecords->count();
            $stats['avg_value'] = $recentRecords->avg('value') ?? 0;
            $stats['last_update'] = $recentRecords->max('created_at');
        }

        $aiResponse = null;
        try {
            $aiService = app(GeminiAIService::class);
            $prompt = "Berdasarkan data statistik bisnis berikut, berikan insight dan rekomendasi untuk meningkatkan performa bisnis.";
            $aiResult = $aiService->generateBusinessInsight($prompt, [
                'statistics' => $stats,
                'business_name' => $business ? $business->business_name : 'Your Business',
                'metric_name' => 'Business Overview'
            ]);
            $aiResponse = $aiResult['success'] ? $aiResult['response'] : $aiResult['error'];
        } catch (\Exception $e) {
            $aiResponse = 'AI service unavailable: ' . $e->getMessage();
        }

    $recentActivities = ActivityLog::when($business, fn($q) => $q->where('business_id', $business->id))
        ->latest()->take(5)->get();

    $orgUsers = $business ? $business->users()->with('userRole')->get() : collect();

        $news = app(NewsService::class);
        $articles = $news->getBusinessNews();

        return view('dashboard-main.index', compact(
            'greeting', 'now', 'user', 'business', 'weatherData', 'metrics', 'combinedChartData', 'stats', 'aiResponse', 'recentActivities', 'orgUsers', 'articles'
        ));
    }

    private function getCombinedMetricsChart($business): array
    {
        if (!$business) {
            return [
                'labels' => array_map(fn($i) => now()->subDays($i)->format('M j'), range(29, 0)),
                'datasets' => []
            ];
        }

        $metrics = $business->metrics()->where('is_active', true)->get();
        $labels = array_map(fn($i) => now()->subDays($i)->format('M j'), range(29, 0));
        $datasets = [];
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'];

        foreach ($metrics as $index => $metric) {
            $records = $metric->records()
                ->where('record_date', '>=', now()->subDays(30))
                ->orderBy('record_date')
                ->get()
                ->keyBy(fn($r) => $r->record_date->format('M j'));

            $data = [];
            foreach ($labels as $label) {
                $data[] = isset($records[$label]) ? (float)$records[$label]->value : ($data ? end($data) : 0);
            }

            $datasets[] = [
                'label' => $metric->metric_name,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '20',
                'tension' => 0.4,
                'fill' => false
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    private function formatMetricValue($value, $unit): string
    {
        if ($value >= 1000000) {
            return number_format($value / 1000000, 1) . 'M';
        } elseif ($value >= 1000) {
            return number_format($value / 1000, 1) . 'K';
        }
        return number_format($value, 0) . ($unit ? ' ' . $unit : '');
    }

    private function getGreeting(): string
    {
        return $this->greetingForHour(now()->hour);
    }

    private function greetingForHour(int $hour): string
    {
        if ($hour < 11) return 'Selamat Pagi';
        if ($hour < 15) return 'Selamat Siang';
        if ($hour < 19) return 'Selamat Sore';
        return 'Selamat Malam';
    }
}
