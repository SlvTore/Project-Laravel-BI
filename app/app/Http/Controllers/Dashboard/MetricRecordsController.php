<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use App\Models\SalesData;
use App\Models\ProductSales;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Services\GeminiAIService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetricRecordsController extends Controller
{
    /**
     * Check if the current user has access to the given business metric
     */
    private function authorizeMetricAccess(BusinessMetric $businessMetric)
    {
        $user = Auth::user();
        $userBusinessIds = $user->businesses()->pluck('businesses.id')->toArray();

        // Debug: Show what's happening
        if (empty($userBusinessIds)) {
            // User has no businesses, let's see what we can do
            Log::warning('User has no businesses', ['user_id' => $user->id]);

            // For now, let's allow access if user has no businesses (development mode)
            // In production, you should ensure users always have businesses
            return;
        }

        // Debug: Log the authorization check
        Log::info('Authorization check:', [
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

        // Basic validation for common fields
        $validated = $request->validate([
            'record_date' => 'required|date|before_or_equal:today',
            'value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'record_date.before_or_equal' => 'Record date cannot be in the future.',
            'value.min' => 'Value must be positive.',
            'record_date.required' => 'Date is required.'
        ]);

        // Handle metric-specific data and validation
        $metricSpecificData = $this->handleMetricSpecificValidation($request, $businessMetric);

        // Merge specific data with validated data
        $validated = array_merge($validated, $metricSpecificData);

        // Handle specific metric types (store additional data)
        $this->handleSpecificMetricStore($request, $businessMetric);

        // Calculate the value if not provided (for computed metrics)
        if (!isset($validated['value']) || $validated['value'] === null) {
            $validated['value'] = $this->calculateMetricValue($request, $businessMetric);
        }

        // Store the metric record using updateOrCreate to prevent duplicates
        $record = MetricRecord::updateOrCreate(
            [
                'business_metric_id' => $businessMetric->id,
                'record_date' => $validated['record_date'],
            ],
            [
                'user_id' => Auth::id(),
                'value' => $validated['value'],
                'notes' => $validated['notes'],
            ]
        );

        // Update the business metric current and previous values
        $this->updateBusinessMetricValues($businessMetric);

        // Log activity
        $user = Auth::user();
        ActivityLog::logDataInput(
            $businessMetric->business_id,
            $user->id,
            $businessMetric->id,
            $validated['value']
        );

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

    /**
     * Handle metric-specific validation
     */
    private function handleMetricSpecificValidation(Request $request, BusinessMetric $businessMetric)
    {
        $metricName = $businessMetric->metric_name;
        $rules = [];
        $data = [];

        switch ($metricName) {
            case 'Total Penjualan':
                $rules = [
                    'total_revenue' => 'required|numeric|min:0',
                    'transaction_count' => 'nullable|integer|min:0',
                ];
                break;

            case 'Biaya Pokok Penjualan (COGS)':
                $rules = [
                    'total_cogs' => 'required|numeric|min:0',
                    'cogs_notes' => 'nullable|string|max:500',
                ];
                break;

            case 'Jumlah Pelanggan Baru':
                $rules = [
                    'new_customer_count' => 'required|integer|min:0',
                    'customer_source' => 'nullable|string|max:100',
                    'customer_acquisition_cost' => 'nullable|numeric|min:0',
                ];
                break;

            case 'Jumlah Pelanggan Setia':
                $rules = [
                    'total_customer_count' => 'required|integer|min:0',
                    'loyal_customer_definition' => 'nullable|string|max:100',
                    'loyalty_program_members' => 'nullable|integer|min:0',
                    'avg_purchase_frequency' => 'nullable|numeric|min:0',
                ];
                break;

            case 'Penjualan Produk Terlaris':
                $rules = [
                    'product_name' => 'required|string|max:255',
                    'product_sku' => 'nullable|string|max:100',
                    'quantity_sold' => 'required|integer|min:0',
                    'unit_price' => 'required|numeric|min:0',
                    'cost_per_unit' => 'nullable|numeric|min:0',
                    'product_category' => 'nullable|string|max:100',
                ];
                break;

            case 'Margin Keuntungan (Profit Margin)':
                $rules = [
                    'margin_period' => 'required|string|in:daily,weekly,monthly,yearly',
                    'margin_target' => 'nullable|numeric|min:0|max:100',
                ];
                break;
        }

        if (!empty($rules)) {
            $data = $request->validate($rules);
        }

        return $data;
    }

    /**
     * Calculate metric value for computed metrics
     */
    private function calculateMetricValue(Request $request, BusinessMetric $businessMetric)
    {
        $metricName = $businessMetric->metric_name;

        switch ($metricName) {
            case 'Total Penjualan':
                return $request->input('total_revenue', 0);

            case 'Biaya Pokok Penjualan (COGS)':
                return $request->input('total_cogs', 0);

            case 'Jumlah Pelanggan Baru':
                return $request->input('new_customer_count', 0);

            case 'Jumlah Pelanggan Setia':
                $totalCustomers = $request->input('total_customer_count', 0);
                $newCustomers = $request->input('new_customer_count', 0);
                if ($totalCustomers > 0) {
                    return (($totalCustomers - $newCustomers) / $totalCustomers) * 100;
                }
                return 0;

            case 'Penjualan Produk Terlaris':
                $quantity = $request->input('quantity_sold', 0);
                $price = $request->input('unit_price', 0);
                return $quantity * $price;

            case 'Margin Keuntungan (Profit Margin)':
                // This will be calculated from existing sales data
                $period = $request->input('margin_period', 'monthly');
                return $this->calculateMarginFromPeriod($businessMetric->business_id, $period);

            default:
                return $request->input('value', 0);
        }
    }

    /**
     * Calculate margin percentage from period data
     */
    private function calculateMarginFromPeriod($businessId, $period)
    {
        $endDate = Carbon::now();
        switch ($period) {
            case 'daily':
                $startDate = $endDate->copy()->startOfDay();
                break;
            case 'weekly':
                $startDate = $endDate->copy()->startOfWeek();
                break;
            case 'monthly':
                $startDate = $endDate->copy()->startOfMonth();
                break;
            case 'yearly':
                $startDate = $endDate->copy()->startOfYear();
                break;
            default:
                $startDate = $endDate->copy()->startOfMonth();
        }

        $salesData = SalesData::forBusiness($businessId)
            ->dateRange($startDate, $endDate)
            ->get();

        $totalRevenue = $salesData->sum('total_revenue');
        $totalCogs = $salesData->sum('total_cogs');

        if ($totalRevenue > 0) {
            return (($totalRevenue - $totalCogs) / $totalRevenue) * 100;
        }

        return 0;
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
            $userBusinessIds = auth()->user()->businesses()->pluck('businesses.id')->toArray();

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
                SalesData::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'sales_date' => $recordDate,
                    ],
                    [
                        'total_revenue' => $request->input('total_revenue', 0),
                        'transaction_count' => $request->input('transaction_count', 0),
                        'notes' => $request->input('notes'),
                    ]
                );
                break;

            case 'Biaya Pokok Penjualan (COGS)':
                SalesData::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'sales_date' => $recordDate,
                    ],
                    [
                        'total_cogs' => $request->input('total_cogs', 0),
                        'notes' => $request->input('cogs_notes') ?? $request->input('notes'),
                    ]
                );
                break;

            case 'Jumlah Pelanggan Baru':
                SalesData::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'sales_date' => $recordDate,
                    ],
                    [
                        'new_customer_count' => $request->input('new_customer_count', 0),
                        'notes' => $request->input('notes'),
                    ]
                );

                // Store customer acquisition data in metadata
                $metadata = [
                    'customer_source' => $request->input('customer_source'),
                    'customer_acquisition_cost' => $request->input('customer_acquisition_cost'),
                ];

                // Update the metric record metadata
                MetricRecord::where('business_metric_id', $businessMetric->id)
                    ->where('record_date', $recordDate)
                    ->update(['metadata' => $metadata]);
                break;

            case 'Jumlah Pelanggan Setia':
                SalesData::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'sales_date' => $recordDate,
                    ],
                    [
                        'total_customer_count' => $request->input('total_customer_count', 0),
                        'notes' => $request->input('notes'),
                    ]
                );

                // Store loyalty data in metadata
                $metadata = [
                    'loyal_customer_definition' => $request->input('loyal_customer_definition'),
                    'loyalty_program_members' => $request->input('loyalty_program_members'),
                    'avg_purchase_frequency' => $request->input('avg_purchase_frequency'),
                ];

                // Update the metric record metadata
                MetricRecord::where('business_metric_id', $businessMetric->id)
                    ->where('record_date', $recordDate)
                    ->update(['metadata' => $metadata]);
                break;

            case 'Penjualan Produk Terlaris':
                $revenueGenerated = ($request->input('quantity_sold', 0) * $request->input('unit_price', 0));

                ProductSales::create([
                    'business_id' => $businessId,
                    'product_name' => $request->input('product_name'),
                    'product_sku' => $request->input('product_sku'),
                    'sales_date' => $recordDate,
                    'quantity_sold' => $request->input('quantity_sold', 0),
                    'unit_price' => $request->input('unit_price', 0),
                    'revenue_generated' => $revenueGenerated,
                    'cost_per_unit' => $request->input('cost_per_unit', 0),
                    'category' => $request->input('product_category'),
                    'notes' => $request->input('notes'),
                ]);
                break;

            case 'Margin Keuntungan (Profit Margin)':
                // Store margin calculation metadata
                $metadata = [
                    'margin_period' => $request->input('margin_period'),
                    'margin_target' => $request->input('margin_target'),
                    'calculation_method' => 'automatic',
                ];

                // Update the metric record metadata
                MetricRecord::where('business_metric_id', $businessMetric->id)
                    ->where('record_date', $recordDate)
                    ->update(['metadata' => $metadata]);
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

    /**
     * Get calculation data for margin and other computed metrics
     */
    public function getCalculationData(BusinessMetric $businessMetric, Request $request)
    {
        $this->authorizeMetricAccess($businessMetric);

        $period = $request->get('period', 'monthly');
        $businessId = $businessMetric->business_id;

        // Calculate date range based on period
        $endDate = Carbon::now();
        switch ($period) {
            case 'daily':
                $startDate = $endDate->copy()->startOfDay();
                break;
            case 'weekly':
                $startDate = $endDate->copy()->startOfWeek();
                break;
            case 'monthly':
                $startDate = $endDate->copy()->startOfMonth();
                break;
            case 'yearly':
                $startDate = $endDate->copy()->startOfYear();
                break;
            default:
                $startDate = $endDate->copy()->startOfMonth();
        }

        // Get sales data for the period
        $salesData = SalesData::forBusiness($businessId)
            ->dateRange($startDate, $endDate)
            ->get();

        $totalRevenue = $salesData->sum('total_revenue');
        $totalCogs = $salesData->sum('total_cogs');
        $totalCustomers = $salesData->sum('total_customer_count');
        $newCustomers = $salesData->sum('new_customer_count');

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'returning_customers' => max(0, $totalCustomers - $newCustomers),
            'margin_percentage' => $totalRevenue > 0 ? (($totalRevenue - $totalCogs) / $totalRevenue) * 100 : 0
        ]);
    }

    /**
     * Get daily data for a specific business
     */
    public function getDailyData($businessId, Request $request)
    {
        // Check access to business
        $userBusinessIds = auth()->user()->businesses()->pluck('businesses.id')->toArray();
        if (!in_array($businessId, $userBusinessIds)) {
            abort(403, 'Unauthorized access to business data.');
        }

        $date = $request->get('date', Carbon::now()->format('Y-m-d'));

        // Get sales data for the specific date
        $salesData = SalesData::forBusiness($businessId)
            ->where('sales_date', $date)
            ->first();

        if (!$salesData) {
            return response()->json([
                'date' => $date,
                'total_revenue' => 0,
                'total_cogs' => 0,
                'transaction_count' => 0,
                'new_customer_count' => 0,
                'total_customer_count' => 0
            ]);
        }

        return response()->json([
            'date' => $date,
            'total_revenue' => $salesData->total_revenue,
            'total_cogs' => $salesData->total_cogs,
            'transaction_count' => $salesData->transaction_count,
            'new_customer_count' => $salesData->new_customer_count,
            'total_customer_count' => $salesData->total_customer_count
        ]);
    }

    /**
     * Handle AI chat requests for business insights
     */
    public function askAI(Request $request, $id)
    {
        try {
            $businessMetric = BusinessMetric::with('business')->findOrFail($id);
            $this->authorizeMetricAccess($businessMetric);

            $question = $request->input('question');

            if (empty($question)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Question is required'
                ], 400);
            }

            // Get recent data for context
            $recentData = MetricRecord::where('business_metric_id', $id)
                ->orderBy('record_date', 'desc')
                ->limit(10)
                ->get(['record_date', 'value', 'notes'])
                ->toArray();

            // Get statistics
            $statistics = $this->getMetricStatistics($businessMetric);

            // Prepare context for AI
            $context = [
                'metric_name' => $businessMetric->metric_name,
                'business_name' => $businessMetric->business->business_name ?? 'Business',
                'recent_data' => $recentData,
                'statistics' => $statistics
            ];

            $aiService = new GeminiAIService();
            $result = $aiService->generateBusinessInsight($question, $context);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get statistics for a metric to provide context to AI
     */
    private function getMetricStatistics($businessMetric)
    {
        $records = MetricRecord::where('business_metric_id', $businessMetric->id)
            ->orderBy('record_date', 'desc')
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        $values = $records->pluck('value')->filter()->toArray();

        return [
            'total_records' => $records->count(),
            'latest_value' => $records->first()->value ?? 0,
            'average_value' => count($values) > 0 ? array_sum($values) / count($values) : 0,
            'max_value' => count($values) > 0 ? max($values) : 0,
            'min_value' => count($values) > 0 ? min($values) : 0,
            'date_range' => [
                'start' => $records->last()->record_date ?? null,
                'end' => $records->first()->record_date ?? null
            ]
        ];
    }
}
