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
    /**
     * Check if the current user has access to the given business metric
     */
    private function authorizeMetricAccess(BusinessMetric $businessMetric)
    {
        $user = auth()->user();
        $userBusinessIds = $user->businesses()->pluck('id')->toArray();

        // Debug: Show what's happening
        if (empty($userBusinessIds)) {
            // User has no businesses, let's see what we can do
            \Log::warning('User has no businesses', ['user_id' => $user->id]);

            // For now, let's allow access if user has no businesses (development mode)
            // In production, you should ensure users always have businesses
            return;
        }

        // Debug: Log the authorization check
        \Log::info('Authorization check:', [
            'user_id' => $user->id,
            'user_business_ids' => $userBusinessIds,
            'metric_business_id' => $businessMetric->business_id,
            'metric_id' => $businessMetric->id
        ]);

        if (!in_array($businessMetric->business_id, $userBusinessIds)) {
            abort(403, 'Unauthorized access to this metric.');
        }
    }

    public function show(BusinessMetric $businessMetric, Request $request)
    {
        $this->authorizeMetricAccess($businessMetric);

        $metricName = $businessMetric->metric_name;

        // Handle AJAX requests for chart data
        if ($request->ajax() && $request->has('period')) {
            $period = $request->get('period', 30);
            $chartData = $this->getChartData($businessMetric, $period);
            return response()->json(['chartData' => $chartData]);
        }

        // Handle DataTables AJAX requests
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesData($businessMetric, $request);
        }

        // Get records for chart data
        $chartData = $this->getChartData($businessMetric);

        // Get statistics
        $statistics = $this->getStatistics($businessMetric);

        // Get specific data based on metric type
        $specificData = $this->getSpecificMetricData($businessMetric);

        return view('dashboard-metrics.edit', compact(
            'businessMetric',
            'chartData',
            'statistics',
            'specificData'
        ));
    }

    public function overview(BusinessMetric $businessMetric)
    {
        $this->authorizeMetricAccess($businessMetric);

        // Get statistics
        $statistics = $this->getStatistics($businessMetric);

        // Get chart data (30 days)
        $chartData = $this->getChartData($businessMetric, 30);

        // Get recent records (last 10)
        $recentRecords = MetricRecord::where('business_metric_id', $businessMetric->id)
            ->orderBy('record_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($record) {
                return [
                    'id' => $record->id,
                    'record_date' => $record->record_date->format('Y-m-d'),
                    'value' => (float) $record->value,
                    'formatted_value' => $record->formatted_value,
                    'notes' => $record->notes ?? ''
                ];
            });

        return response()->json([
            'metric' => [
                'id' => $businessMetric->id,
                'metric_name' => $businessMetric->metric_name,
                'unit' => $businessMetric->unit ?? 'number',
                'description' => $businessMetric->description
            ],
            'statistics' => $statistics,
            'chartData' => $chartData,
            'recentRecords' => $recentRecords
        ]);
    }

    public function getDataTablesData(BusinessMetric $businessMetric, Request $request)
    {
        $query = MetricRecord::where('business_metric_id', $businessMetric->id);

        // Handle search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%")
                  ->orWhere('record_date', 'like', "%{$search}%");
            });
        }

        // Get total before applying pagination
        $totalRecords = MetricRecord::where('business_metric_id', $businessMetric->id)->count();

        // Get filtered count
        $filteredRecords = $query->count();

        // Handle ordering
        if ($request->has('order') && isset($request->order[0])) {
            $columns = ['id', 'record_date', 'value', 'formatted_value', 'notes', 'created_at', 'actions'];
            $columnIndex = $request->order[0]['column'];
            $direction = $request->order[0]['dir'];

            // Skip checkbox and actions columns
            if ($columnIndex == 1) { // record_date
                $query->orderBy('record_date', $direction);
            } elseif ($columnIndex == 2) { // value
                $query->orderBy('value', $direction);
            } elseif ($columnIndex == 4) { // notes
                $query->orderBy('notes', $direction);
            } elseif ($columnIndex == 5) { // created_at
                $query->orderBy('created_at', $direction);
            }
        } else {
            $query->orderBy('record_date', 'desc');
        }

        // Handle pagination
        if ($request->has('start') && $request->has('length')) {
            $query->skip($request->start)->take($request->length);
        }

        $records = $query->with('businessMetric')->get();

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $records->map(function($record) {
                return [
                    'id' => $record->id,
                    'record_date' => $record->record_date->format('Y-m-d'),
                    'value' => (float) $record->value,
                    'formatted_value' => $record->formatted_value,
                    'notes' => $record->notes ?? '',
                    'created_at' => $record->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }    public function store(Request $request, BusinessMetric $businessMetric)
    {
        $this->authorizeMetricAccess($businessMetric);

        $validated = $request->validate([
            'record_date' => 'required|date|before_or_equal:today',
            'value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'record_date.before_or_equal' => 'Record date cannot be in the future.',
            'value.min' => 'Value must be positive.',
            'value.required' => 'Value is required.',
            'record_date.required' => 'Date is required.'
        ]);

        // Handle specific metric types
        $this->handleSpecificMetricStore($request, $businessMetric);

        // Store the metric record using updateOrCreate to prevent duplicates
        $record = MetricRecord::updateOrCreate(
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

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $record->wasRecentlyCreated ? 'Record created successfully' : 'Record updated successfully',
                'record' => [
                    'id' => $record->id,
                    'record_date' => $record->record_date->format('Y-m-d'),
                    'value' => (float) $record->value,
                    'formatted_value' => $record->formatted_value,
                    'notes' => $record->notes ?? '',
                    'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                    'was_recently_created' => $record->wasRecentlyCreated
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Data berhasil disimpan!');
    }

    public function update(Request $request, MetricRecord $record)
    {
        // Check if user has access to this metric
        $this->authorizeMetricAccess($record->businessMetric);

        // Enhanced validation - allow partial updates for inline editing
        $rules = [];
        $data = [];

        // Only validate fields that are actually being updated
        if ($request->has('record_date')) {
            $rules['record_date'] = 'required|date|before_or_equal:today';
            $data['record_date'] = $request->record_date;
        }

        if ($request->has('value')) {
            $rules['value'] = 'required|numeric|min:0';
            $data['value'] = $request->value;
        }

        if ($request->has('notes')) {
            $rules['notes'] = 'nullable|string|max:1000';
            $data['notes'] = $request->notes;
        }

        // Validate only the fields being updated
        $validated = $request->validate($rules);

        // Update only the provided fields
        $record->update($validated);

        // Update business metric current/previous values
        $this->updateBusinessMetricValues($record->businessMetric);

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully',
                'record' => [
                    'id' => $record->id,
                    'record_date' => $record->record_date->format('Y-m-d'),
                    'value' => (float) $record->value,
                    'formatted_value' => $record->formatted_value,
                    'notes' => $record->notes ?? '',
                    'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $record->updated_at->format('Y-m-d H:i:s')
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(MetricRecord $record)
    {
        // Check if user has access to this metric
        $this->authorizeMetricAccess($record->businessMetric);

        $businessMetric = $record->businessMetric;
        $record->delete();

        // Update business metric values after deletion
        $this->updateBusinessMetricValues($businessMetric);

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully'
        ]);
    }

    public function getTableStats(BusinessMetric $businessMetric)
    {
        $this->authorizeMetricAccess($businessMetric);

        $totalRecords = MetricRecord::where('business_metric_id', $businessMetric->id)->count();

        return response()->json([
            'total_records' => $totalRecords,
            'next_number' => $totalRecords + 1
        ]);
    }

    public function edit(MetricRecord $record)
    {
        $record->load(['businessMetric', 'salesData', 'productSales', 'customer']);

        // Check if user has access to this metric
        $this->authorizeMetricAccess($record->businessMetric);

        return response()->json($record);
    }

    public function editPage(BusinessMetric $businessMetric, Request $request)
    {
        $this->authorizeMetricAccess($businessMetric);

        // Handle AJAX requests for chart data
        if ($request->ajax() && $request->has('period')) {
            $period = $request->get('period', 30);
            $chartData = $this->getChartData($businessMetric, $period);
            return response()->json(['chartData' => $chartData]);
        }

        // Handle DataTables AJAX requests
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesData($businessMetric, $request);
        }

        // Get chart data
        $chartData = $this->getChartData($businessMetric);

        // Get statistics
        $statistics = $this->getStatistics($businessMetric);

        // Get specific data based on metric type
        $specificData = $this->getSpecificMetricData($businessMetric);

        return view('dashboard-metrics.edit', compact(
            'businessMetric',
            'chartData',
            'statistics',
            'specificData'
        ));
    }

    public function getRecord(MetricRecord $record)
    {
        $record->load(['businessMetric', 'salesData', 'productSales', 'customer']);

        // Check if user has access to this metric
        $this->authorizeMetricAccess($record->businessMetric);

        return response()->json($record);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:metric_records,id'
        ]);

        try {
            // Get user's business IDs
            $userBusinessIds = auth()->user()->businesses()->pluck('id')->toArray();

            // Get records with their business metrics
            $records = MetricRecord::with('businessMetric')
                ->whereIn('id', $request->ids)
                ->get();

            // Check if all records belong to user's businesses
            foreach ($records as $record) {
                if (!in_array($record->businessMetric->business_id, $userBusinessIds)) {
                    abort(403, 'Unauthorized access to one or more records.');
                }
            }

            MetricRecord::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' records deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete records'
            ], 500);
        }
    }

    public function export(BusinessMetric $businessMetric)
    {
        $this->authorizeMetricAccess($businessMetric);

        $records = MetricRecord::where('business_metric_id', $businessMetric->id)
            ->with(['salesData', 'productSales', 'customer'])
            ->orderBy('record_date', 'desc')
            ->get();

        $filename = 'metric_records_' . $businessMetric->slug . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($records, $businessMetric) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            $csvHeaders = ['Date', 'Value', 'Formatted Value', 'Notes', 'Created At'];

            // Add metric-specific headers
            if ($businessMetric->type === 'sales_data') {
                $csvHeaders = array_merge($csvHeaders, ['Revenue', 'COGS']);
            } elseif ($businessMetric->type === 'product_sales') {
                $csvHeaders = array_merge($csvHeaders, ['Product Name', 'Quantity Sold']);
            } elseif ($businessMetric->type === 'customers') {
                $csvHeaders = array_merge($csvHeaders, ['Customer Name', 'Customer Type']);
            }

            fputcsv($file, $csvHeaders);

            // Data rows
            foreach ($records as $record) {
                $row = [
                    $record->record_date->format('Y-m-d'),
                    $record->value,
                    $record->formatted_value,
                    $record->notes,
                    $record->created_at->format('Y-m-d H:i:s')
                ];

                // Add metric-specific data
                if ($businessMetric->type === 'sales_data' && $record->salesData) {
                    $row[] = $record->salesData->revenue;
                    $row[] = $record->salesData->cogs;
                } elseif ($businessMetric->type === 'product_sales' && $record->productSales) {
                    $row[] = $record->productSales->product_name;
                    $row[] = $record->productSales->quantity_sold;
                } elseif ($businessMetric->type === 'customers' && $record->customer) {
                    $row[] = $record->customer->customer_name;
                    $row[] = $record->customer->customer_type;
                } else {
                    // Fill empty columns for consistency
                    if ($businessMetric->type === 'sales_data') {
                        $row[] = null;
                        $row[] = null;
                    } elseif ($businessMetric->type === 'product_sales') {
                        $row[] = null;
                        $row[] = null;
                    } elseif ($businessMetric->type === 'customers') {
                        $row[] = null;
                        $row[] = null;
                    }
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getChartData(BusinessMetric $businessMetric, $days = 30)
    {
        $records = MetricRecord::where('business_metric_id', $businessMetric->id)
            ->whereDate('record_date', '>=', Carbon::now()->subDays($days))
            ->orderBy('record_date')
            ->get();

        return [
            'dates' => $records->pluck('record_date')->map(fn($date) => $date->format('Y-m-d')),
            'values' => $records->pluck('value'),
            'labels' => $records->pluck('record_date')->map(fn($date) => $date->format('d M')),
        ];
    }

    private function getStatistics(BusinessMetric $businessMetric)
    {
        $records = MetricRecord::where('business_metric_id', $businessMetric->id)->get();

        if ($records->isEmpty()) {
            return [
                'total_records' => 0,
                'avg_value' => 0,
                'max_value' => 0,
                'min_value' => 0,
                'last_update' => null,
                'growth_rate' => 0,
                'this_month' => 0,
                'last_month' => 0,
            ];
        }

        $thisMonth = $records->filter(function($record) {
            return $record->record_date->isCurrentMonth();
        });

        $lastMonth = $records->filter(function($record) {
            return $record->record_date->isLastMonth();
        });

        $thisMonthAvg = $thisMonth->avg('value') ?? 0;
        $lastMonthAvg = $lastMonth->avg('value') ?? 0;

        $growthRate = 0;
        if ($lastMonthAvg > 0) {
            $growthRate = (($thisMonthAvg - $lastMonthAvg) / $lastMonthAvg) * 100;
        }

        return [
            'total_records' => $records->count(),
            'avg_value' => $records->avg('value'),
            'max_value' => $records->max('value'),
            'min_value' => $records->min('value'),
            'last_update' => $records->max('created_at'),
            'growth_rate' => $growthRate,
            'this_month' => $thisMonthAvg,
            'last_month' => $lastMonthAvg,
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
