<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use App\Models\SalesData;
use App\Models\ProductSales;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MetricRecordsController extends Controller
{
    public function show(BusinessMetric $businessMetric)
    {
        // Ensure the metric belongs to the authenticated user's business
        if ($businessMetric->business_id !== auth()->user()->business_id) {
            abort(403, 'Unauthorized access to this metric.');
        }

        $metricName = $businessMetric->metric_name;

        // Get records for chart data
        $chartData = $this->getChartData($businessMetric);

        // Get recent records for table
        $recentRecords = MetricRecord::where('business_metric_id', $businessMetric->id)
            ->orderBy('record_date', 'desc')
            ->limit(50)
            ->get();

        // Get specific data based on metric type
        $specificData = $this->getSpecificMetricData($businessMetric);

        return view('dashboard-metrics.records.show', compact(
            'businessMetric',
            'chartData',
            'recentRecords',
            'specificData'
        ));
    }

    public function store(Request $request, BusinessMetric $businessMetric)
    {
        // Ensure the metric belongs to the authenticated user's business
        if ($businessMetric->business_id !== auth()->user()->business_id) {
            abort(403, 'Unauthorized access to this metric.');
        }

        $validated = $request->validate([
            'record_date' => 'required|date',
            'value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Handle specific metric types
        $this->handleSpecificMetricStore($request, $businessMetric);

        // Store the metric record
        MetricRecord::updateOrCreate(
            [
                'business_metric_id' => $businessMetric->id,
                'record_date' => $validated['record_date'],
            ],
            [
                'value' => $validated['value'],
                'notes' => $validated['notes'],
            ]
        );

        // Update the business metric current and previous values
        $this->updateBusinessMetricValues($businessMetric);

        return redirect()->back()->with('success', 'Data berhasil disimpan!');
    }

    public function update(Request $request, BusinessMetric $businessMetric, MetricRecord $record)
    {
        // Ensure the record belongs to the metric
        if ($record->business_metric_id !== $businessMetric->id) {
            abort(403, 'Unauthorized access to this record.');
        }

        $validated = $request->validate([
            'value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $record->update($validated);

        // Update the business metric values
        $this->updateBusinessMetricValues($businessMetric);

        return redirect()->back()->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(BusinessMetric $businessMetric, MetricRecord $record)
    {
        // Ensure the record belongs to the metric
        if ($record->business_metric_id !== $businessMetric->id) {
            abort(403, 'Unauthorized access to this record.');
        }

        $record->delete();

        // Update the business metric values
        $this->updateBusinessMetricValues($businessMetric);

        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }

    private function getChartData(BusinessMetric $businessMetric)
    {
        $records = MetricRecord::where('business_metric_id', $businessMetric->id)
            ->whereDate('record_date', '>=', Carbon::now()->subDays(30))
            ->orderBy('record_date')
            ->get();

        return [
            'dates' => $records->pluck('record_date')->map(fn($date) => $date->format('Y-m-d')),
            'values' => $records->pluck('value'),
        ];
    }

    private function getSpecificMetricData(BusinessMetric $businessMetric)
    {
        $businessId = $businessMetric->business_id;
        $metricName = $businessMetric->metric_name;

        switch ($metricName) {
            case 'Total Penjualan':
                return [
                    'recent_sales' => SalesData::forBusiness($businessId)
                        ->orderBy('sales_date', 'desc')
                        ->limit(10)
                        ->get(),
                    'monthly_total' => SalesData::forBusiness($businessId)
                        ->thisMonth()
                        ->sum('total_revenue'),
                ];

            case 'Biaya Pokok Penjualan':
                return [
                    'recent_cogs' => SalesData::forBusiness($businessId)
                        ->orderBy('sales_date', 'desc')
                        ->limit(10)
                        ->get(),
                    'monthly_total' => SalesData::forBusiness($businessId)
                        ->thisMonth()
                        ->sum('total_cogs'),
                ];

            case 'Penjualan Produk Terlaris':
                return [
                    'top_products' => ProductSales::forBusiness($businessId)
                        ->thisMonth()
                        ->topSelling(10)
                        ->get(),
                    'categories' => ProductSales::forBusiness($businessId)
                        ->thisMonth()
                        ->selectRaw('category, SUM(revenue_generated) as total_revenue')
                        ->whereNotNull('category')
                        ->groupBy('category')
                        ->orderByDesc('total_revenue')
                        ->get(),
                ];

            case 'Jumlah Pelanggan Baru':
                return [
                    'new_customers' => Customer::forBusiness($businessId)
                        ->newCustomers()
                        ->orderBy('first_purchase_date', 'desc')
                        ->limit(10)
                        ->get(),
                    'monthly_count' => Customer::forBusiness($businessId)
                        ->thisMonth()
                        ->count(),
                ];

            case 'Jumlah Pelanggan Setia':
                return [
                    'loyal_customers' => Customer::forBusiness($businessId)
                        ->loyalCustomers()
                        ->orderBy('total_spent', 'desc')
                        ->limit(10)
                        ->get(),
                    'loyalty_stats' => [
                        'total_customers' => Customer::forBusiness($businessId)->count(),
                        'loyal_customers' => Customer::forBusiness($businessId)->loyalCustomers()->count(),
                        'returning_customers' => Customer::forBusiness($businessId)->byType('returning')->count(),
                    ],
                ];

            default:
                return [];
        }
    }

    private function handleSpecificMetricStore(Request $request, BusinessMetric $businessMetric)
    {
        $businessId = $businessMetric->business_id;
        $metricName = $businessMetric->metric_name;
        $recordDate = $request->record_date;

        switch ($metricName) {
            case 'Total Penjualan':
            case 'Biaya Pokok Penjualan':
                if ($request->has('total_revenue') || $request->has('total_cogs')) {
                    SalesData::updateOrCreate(
                        [
                            'business_id' => $businessId,
                            'sales_date' => $recordDate,
                        ],
                        [
                            'total_revenue' => $request->input('total_revenue', 0),
                            'total_cogs' => $request->input('total_cogs', 0),
                            'transaction_count' => $request->input('transaction_count', 0),
                            'notes' => $request->input('sales_notes'),
                        ]
                    );
                }
                break;
        }
    }

    private function updateBusinessMetricValues(BusinessMetric $businessMetric)
    {
        $latestRecord = $businessMetric->metricRecords()
            ->orderBy('record_date', 'desc')
            ->first();

        $previousRecord = $businessMetric->metricRecords()
            ->orderBy('record_date', 'desc')
            ->skip(1)
            ->first();

        if ($latestRecord) {
            $businessMetric->update([
                'current_value' => $latestRecord->value,
                'previous_value' => $previousRecord ? $previousRecord->value : 0,
            ]);
        }
    }
}
