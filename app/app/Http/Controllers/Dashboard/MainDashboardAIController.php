<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MetricRecord;
use App\Services\GeminiAIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MainDashboardAIController extends Controller
{
    public function insight(Request $request, GeminiAIService $ai)
    {
        $user = Auth::user();
        $business = $user->isBusinessOwner() ? $user->primaryBusiness()->first() : $user->businesses()->first();
        $since = Carbon::now()->subDays(30);

        $recordsQuery = MetricRecord::query();
        if ($business) {
            $recordsQuery = $recordsQuery->whereHas('businessMetric', function($q) use ($business){
                $q->where('business_id', $business->id);
            });
        } else {
            $recordsQuery = $recordsQuery->whereRaw('1=0');
        }
        $recordsQuery = $recordsQuery->where('created_at', '>=', $since);

        $stats = [
            'total_updates' => (clone $recordsQuery)->count(),
            'last_update' => (clone $recordsQuery)->max('created_at'),
            'avg_value' => (clone $recordsQuery)->avg('value'),
        ];

        $question = $request->input('q') ?: 'Ringkas kinerja bisnis 30 hari terakhir dan beri 3 rekomendasi.';
        $aiResult = $ai->generateBusinessInsight($question, [
            'business_name' => $business->business_name ?? 'Business',
            'metric_name' => 'All Metrics',
            'statistics' => $stats,
        ]);

        return response()->json($aiResult);
    }
}
