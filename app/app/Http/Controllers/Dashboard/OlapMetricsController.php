<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Views\SalesDailyView;
use Illuminate\Http\Request;

class OlapMetricsController extends Controller
{
    public function dailySales(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $days = (int) $request->input('days', 30);
        $from = now()->subDays($days)->toDateString();

        $rows = SalesDailyView::query()
            ->where('business_id', $business->id)
            ->where('sales_date', '>=', $from)
            ->orderBy('sales_date')
            ->get(['sales_date', 'total_revenue', 'transaction_count', 'total_quantity']);

        return response()->json([
            'success' => true,
            'labels' => $rows->pluck('sales_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M')),
            'values' => $rows->pluck('total_revenue')->map(fn($v) => (float)$v),
            'transactions' => $rows->pluck('transaction_count')->map(fn($v) => (int)$v),
        ]);
    }
}
