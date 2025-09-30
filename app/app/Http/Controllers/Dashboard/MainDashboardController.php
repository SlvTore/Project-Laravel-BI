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
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\OlapMetricAggregator;

class MainDashboardController extends Controller
{
    use LogsActivity;

    public function index(OlapMetricAggregator $aggregator)
    {
        // Log dashboard access
        $this->logDashboardActivity('Home Dashboard');

        $user = Auth::user();
        $greeting = $this->getGreeting();
        $now = now();

        $business = $user->isBusinessOwner() ? $user->primaryBusiness()->first() : $user->businesses()->first();

        $weather = app(WeatherService::class);
        $weatherData = $weather->getCurrent(); // may be null if no API key

        // Get metrics with chart data
        $metrics = collect();
        if ($business) {
            $metrics = $business->metrics()->where('is_active', true)->take(6)->get()->map(function($metric) use ($aggregator) {
                $mapping = $this->mapMetricToOlap($metric->metric_name);
                if ($mapping) {
                    try {
                        // Build daily series via aggregator when possible
                        $series = $this->getDailySeries($mapping, $aggregator, $metric->business_id);
                        $metric->chart_labels = $series['labels'] ?? [];
                        $metric->chart_data = $series['values'] ?? [];

                        // Monthly aggregates
                        if ($mapping['type'] === 'top_products') {
                            [$current, $previous] = $this->getTopProductMonthlyTotals($metric->business_id);
                        } elseif ($mapping['type'] === 'margin') {
                            [$current, $previous] = $this->getMonthlyAggregate($mapping['view'], $mapping['column'], $metric->business_id, 'avg');
                        } else {
                            [$current, $previous] = $this->getMonthlyAggregate($mapping['view'], $mapping['column'], $metric->business_id, 'sum');
                        }
                        $metric->current_value = $current;
                        $metric->previous_value = $previous;
                    } catch (\Throwable $e) {
                        // fall back silently
                    }
                }

                $metric->formatted_value = $this->formatMetricValue($metric->current_value, $metric->unit);
                $prevValue = $metric->previous_value ?? 0;
                if ($prevValue > 0) {
                    $changePercent = round((($metric->current_value - $prevValue) / $prevValue) * 100, 1);
                    $metric->formatted_change = ($changePercent >= 0 ? '+' : '') . $changePercent . '%';
                    $metric->change_status = $changePercent > 0 ? 'increase' : ($changePercent < 0 ? 'decrease' : 'stable');
                } else {
                    $metric->formatted_change = $metric->current_value > 0 ? '+100%' : '0%';
                    $metric->change_status = 'increase';
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
            // Use sales view as primary activity proxy if available
            try {
                $recentRows = DB::table('vw_sales_daily')
                    ->where('business_id', $business->id)
                    ->where('sales_date', '>=', now()->subDays(30)->toDateString())
                    ->get();
                if ($recentRows->count() > 0) {
                    $stats['total_updates'] = $recentRows->count();
                    $stats['avg_value'] = (float)$recentRows->avg('total_revenue');
                    $stats['last_update'] = $recentRows->max('sales_date');
                }
            } catch (\Throwable $e) {
                // fallback silently
            }
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

        return view('home.index', compact(
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
            $mapping = $this->mapMetricToOlap($metric->metric_name);
            $data = [];
            if ($mapping) {
                try {
                    $series = $this->getDailySeries($mapping, app(OlapMetricAggregator::class), $metric->business_id);
                    $seriesAssoc = collect($series)->keyBy(fn($row) => \Carbon\Carbon::parse($row['date'])->format('M j'));
                    foreach ($labels as $label) {
                        $data[] = isset($seriesAssoc[$label]) ? (float)$seriesAssoc[$label]['value'] : ($data ? end($data) : 0);
                    }
                } catch (\Throwable $e) {
                    $data = array_fill(0, count($labels), 0);
                }
            } else {
                $data = array_fill(0, count($labels), 0);
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

    private function mapMetricToOlap(string $name): ?array
    {
        return match($name) {
            'Total Penjualan' => ['view' => 'vw_sales_daily', 'column' => 'total_revenue', 'type' => 'sum', 'series' => 'revenue'],
            'Biaya Pokok Penjualan (COGS)' => ['view' => 'vw_cogs_daily', 'column' => 'total_cogs', 'type' => 'sum', 'series' => 'cogs'],
            'Margin Keuntungan (Profit Margin)' => ['view' => 'vw_margin_daily', 'column' => 'total_margin', 'type' => 'margin', 'series' => 'margin'],
            'Penjualan Produk Terlaris' => ['view' => 'vw_sales_product_daily', 'column' => 'total_quantity', 'type' => 'top_products', 'series' => 'top_products'],
            'Jumlah Pelanggan Baru' => ['view' => 'vw_new_customers_daily', 'column' => 'new_customers', 'type' => 'sum', 'series' => 'new_customers'],
            'Jumlah Pelanggan Setia' => ['view' => 'vw_returning_customers_daily', 'column' => 'returning_customers', 'type' => 'sum', 'series' => 'returning_customers'],
            default => null,
        };
    }

    private function getDailySeries(array $mapping, OlapMetricAggregator $aggregator, int $businessId): array
    {
        return match($mapping['series']) {
            'revenue' => $aggregator->dailyRevenue($businessId),
            'cogs' => $aggregator->dailyCogs($businessId),
            'margin' => $aggregator->dailyMargin($businessId),
            'new_customers' => $aggregator->dailyNewCustomers($businessId),
            'returning_customers' => $aggregator->dailyReturningCustomers($businessId),
            'top_products' => [
                'labels' => [],
                'values' => []
            ], // not charted in combined small cards
            default => ['labels' => [], 'values' => []],
        };
    }

    private function getMonthlyAggregate(string $view, string $column, int $businessId, string $agg = 'sum'): array
    {
        $now = now();
        $startCurrent = $now->copy()->startOfMonth();
        $startPrevious = $now->copy()->subMonth()->startOfMonth();
        $endPrevious = $now->copy()->subMonth()->endOfMonth();
        $currentQ = DB::table($view)
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$startCurrent->toDateString(), $now->toDateString()]);
        $previousQ = DB::table($view)
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$startPrevious->toDateString(), $endPrevious->toDateString()]);
        $current = $agg === 'avg' ? $currentQ->avg($column) : $currentQ->sum($column);
        $previous = $agg === 'avg' ? $previousQ->avg($column) : $previousQ->sum($column);
        return [ (float)$current, (float)$previous ];
    }

    private function getTopProductMonthlyTotals(int $businessId): array
    {
        $now = now();
        $startCurrent = $now->copy()->startOfMonth();
        $startPrevious = $now->copy()->subMonth()->startOfMonth();
        $endPrevious = $now->copy()->subMonth()->endOfMonth();
        $current = DB::table('vw_sales_product_daily')
            ->select('product_id', DB::raw('SUM(total_quantity) as qty'))
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$startCurrent->toDateString(), $now->toDateString()])
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(1)
            ->value('qty');
        $previous = DB::table('vw_sales_product_daily')
            ->select('product_id', DB::raw('SUM(total_quantity) as qty'))
            ->where('business_id', $businessId)
            ->whereBetween('sales_date', [$startPrevious->toDateString(), $endPrevious->toDateString()])
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(1)
            ->value('qty');
        return [ (float)($current ?? 0), (float)($previous ?? 0) ];
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
