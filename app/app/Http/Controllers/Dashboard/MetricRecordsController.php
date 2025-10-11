<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BusinessMetric;
use App\Models\MetricRecord;
use App\Models\Views\SalesDailyView;
use App\Models\SalesData;
use App\Models\ProductSales;
use App\Models\Customer;
use App\Models\Business;
use App\Models\ActivityLog;
use App\Services\GeminiAIService;
use App\Services\DataIntegrityService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\OlapMetricAggregator;
use App\Services\MetricFormattingService;

class MetricRecordsController extends Controller
{
    private OlapMetricAggregator $aggregator;

    public function __construct(OlapMetricAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }
    /**
     * Check if the current user has access to the given business metric
     */
    private function authorizeMetricAccess(BusinessMetric $businessMetric)
    {
        $user = Auth::user();
        // Get business IDs that the user has access to via direct query
        $userBusinessIds = DB::table('business_user')
            ->where('user_id', $user->id)
            ->pluck('business_id')
            ->toArray();

        // Also include businesses owned by the user
        $ownedBusinessIds = Business::where('user_id', $user->id)->pluck('id')->toArray();
        $userBusinessIds = array_merge($userBusinessIds, $ownedBusinessIds);

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

        $warehouseData = $this->getWarehouseData($businessMetric);

        // Enrich metric with OLAP data using consistent service
        $businessMetric = MetricFormattingService::enrichMetricWithOlapData($businessMetric);

        return view('dashboard-metrics.edit', compact(
            'businessMetric',
            'chartData',
            'statistics',
            'specificData',
            'warehouseData'
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

        // Start database transaction for data safety
        DB::beginTransaction();

        try {
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

            // Pre-validate data integrity for customer metrics
            if (in_array($businessMetric->metric_name, ['Jumlah Pelanggan Baru', 'Jumlah Pelanggan Setia'])) {
                $newCustomerCount = $request->input('new_customer_count', 0);
                $totalCustomerCount = $request->input('total_customer_count', 0);

                $validationErrors = DataIntegrityService::validateCustomerData(
                    $businessMetric->business_id,
                    $validated['record_date'],
                    $newCustomerCount,
                    $totalCustomerCount
                );

                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'errors' => $validationErrors
                    ], 422);
                }
            }

            // Pre-validate data integrity for sales metrics
            if (in_array($businessMetric->metric_name, ['Total Penjualan', 'Biaya Pokok Penjualan (COGS)'])) {
                $totalRevenue = $request->input('total_revenue', 0);
                $totalCogs = $request->input('total_cogs', null);

                $validationErrors = DataIntegrityService::validateSalesData(
                    $businessMetric->business_id,
                    $validated['record_date'],
                    $totalRevenue,
                    $totalCogs
                );

                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'errors' => $validationErrors
                    ], 422);
                }
            }

            // Calculate the value if not provided (for computed metrics)
            if (!isset($validated['value']) || $validated['value'] === null) {
                $validated['value'] = $this->calculateMetricValue($request, $businessMetric);
            }

            // Check for existing record to backup before update
            $existingRecord = MetricRecord::where('business_metric_id', $businessMetric->id)
                ->where('record_date', $validated['record_date'])
                ->first();

            if ($existingRecord) {
                DataIntegrityService::backupData($existingRecord, 'update');
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

            // Handle specific metric types (store additional data)
            $this->handleSpecificMetricStore($request, $businessMetric);

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

            // Commit transaction
            DB::commit();

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

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollback();

            Log::error('Metric store error: ' . $e->getMessage(), [
                'business_metric_id' => $businessMetric->id,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.'])
                ->withInput();
        }
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
            $user = Auth::user();
            $userBusinessIds = DB::table('business_user')
                ->where('user_id', $user->id)
                ->pluck('business_id')
                ->toArray();

            // Also include businesses owned by the user
            $ownedBusinessIds = Business::where('user_id', $user->id)->pluck('id')->toArray();
            $userBusinessIds = array_merge($userBusinessIds, $ownedBusinessIds);

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
        $metric = $businessMetric->metric_name;
        $businessId = $businessMetric->business_id;

        try {
            switch ($metric) {
                case 'Total Penjualan': {
                    $data = $this->aggregator->dailyRevenue($businessId, (int)$days);
                    return [ 'dates' => collect($data['dates']), 'values' => collect($data['values']), 'labels' => collect($data['labels']) ];
                }
                case 'Biaya Pokok Penjualan (COGS)': {
                    $data = $this->aggregator->dailyCogs($businessId, (int)$days);
                    return [ 'dates' => collect($data['dates']), 'values' => collect($data['values']), 'labels' => collect($data['labels']) ];
                }
                case 'Margin Keuntungan (Profit Margin)': {
                    $data = $this->aggregator->dailyMargin($businessId, (int)$days);
                    return [ 'dates' => collect($data['dates']), 'values' => collect($data['values']), 'labels' => collect($data['labels']) ];
                }
                case 'Jumlah Pelanggan Baru': {
                    $data = $this->aggregator->dailyNewCustomers($businessId, (int)$days);
                    return [ 'dates' => collect($data['dates']), 'values' => collect($data['values']), 'labels' => collect($data['labels']) ];
                }
                case 'Jumlah Pelanggan Setia': {
                    $data = $this->aggregator->dailyReturningCustomers($businessId, (int)$days);
                    return [ 'dates' => collect($data['dates']), 'values' => collect($data['values']), 'labels' => collect($data['labels']) ];
                }
            }
        } catch (\Throwable $e) {
            // OLAP views may not be present yet; fall back below
        }

        // Fallback to manual metric records (or unsupported metric like Top Products)
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
        $metric = $businessMetric->metric_name;
        $bid = $businessMetric->business_id;
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth()->toDateString();
        $thirtyDaysAgo = $now->copy()->subDays(30)->toDateString();

        try {
            switch ($metric) {
                case 'Total Penjualan': {
                    $view = 'vw_sales_daily'; $col = 'total_revenue'; break;
                }
                case 'Biaya Pokok Penjualan (COGS)': {
                    $view = 'vw_cogs_daily'; $col = 'total_cogs'; break;
                }
                case 'Margin Keuntungan (Profit Margin)': {
                    $view = 'vw_margin_daily'; $col = 'total_margin'; break;
                }
                case 'Jumlah Pelanggan Baru': {
                    $view = 'vw_new_customers_daily'; $col = 'new_customers'; break;
                }
                case 'Jumlah Pelanggan Setia': {
                    $view = 'vw_returning_customers_daily'; $col = 'returning_customers'; break;
                }
                default: $view = null; $col = null; break;
            }

            if ($view) {
                $thisMonth = DB::table($view)
                    ->where('business_id', $bid)
                    ->where('sales_date', '>=', $thisMonthStart)
                    ->selectRaw('SUM(' . $col . ') as total_val, AVG(' . $col . ') as avg_daily, MAX(' . $col . ') as max_daily, MIN(' . $col . ') as min_daily, MAX(sales_date) as last_update')
                    ->first();
                $lastMonth = DB::table($view)
                    ->where('business_id', $bid)
                    ->whereBetween('sales_date', [$lastMonthStart, $lastMonthEnd])
                    ->selectRaw('SUM(' . $col . ') as total_val, AVG(' . $col . ') as avg_daily')
                    ->first();
                $overall = DB::table($view)
                    ->where('business_id', $bid)
                    ->where('sales_date', '>=', $thirtyDaysAgo)
                    ->selectRaw('COUNT(*) as total_records, AVG(' . $col . ') as avg_value, MAX(' . $col . ') as max_value, MIN(' . $col . ') as min_value')
                    ->first();

                $thisTotal = (float)($thisMonth->total_val ?? 0);
                $lastTotal = (float)($lastMonth->total_val ?? 0);
                $growthRate = ($lastTotal > 0) ? (($thisTotal - $lastTotal) / $lastTotal) * 100 : 0;

                return [
                    'total_records' => (int)($overall->total_records ?? 0),
                    'avg_value' => (float)($overall->avg_value ?? 0),
                    'max_value' => (float)($overall->max_value ?? 0),
                    'min_value' => (float)($overall->min_value ?? 0),
                    'last_update' => $thisMonth->last_update ?? null,
                    'growth_rate' => $growthRate,
                    'this_month' => $thisTotal,
                    'last_month' => $lastTotal,
                ];
            }
        } catch (\Throwable $e) {
            // Fall through to record-based fallback below
        }

        // Fallback to manual records when OLAP not available or unsupported metric
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
        $thisMonthSet = $records->filter(fn($r) => $r->record_date->isCurrentMonth());
        $lastMonthSet = $records->filter(fn($r) => $r->record_date->isLastMonth());
        $thisAvg = $thisMonthSet->avg('value') ?? 0; $lastAvg = $lastMonthSet->avg('value') ?? 0;
        $growthRate = $lastAvg > 0 ? (($thisAvg - $lastAvg) / $lastAvg) * 100 : 0;
        return [
            'total_records' => $records->count(),
            'avg_value' => $records->avg('value'),
            'max_value' => $records->max('value'),
            'min_value' => $records->min('value'),
            'last_update' => $records->max('created_at'),
            'growth_rate' => $growthRate,
            'this_month' => $thisAvg,
            'last_month' => $lastAvg,
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

    private function getWarehouseData(BusinessMetric $businessMetric): array
    {
        $metric = $businessMetric->metric_name;
        $businessId = $businessMetric->business_id;
        // Pagination & limit safeguards
        $request = request();
        $page = max(1, (int)$request->input('w_page', 1));
        $perPage = (int)$request->input('w_per_page', 30);
        if ($perPage > 200) { // enforce upper bound
            $perPage = 200;
        } elseif ($perPage < 5) {
            $perPage = 30; // sensible default
        }
        $offset = ($page - 1) * $perPage;

        // Use flexible date range - get latest available data or last 30 days
        $from = Carbon::now()->subDays(30)->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();

        // For customer metrics, check if we have recent data, otherwise use all available data
        $hasRecentData = true;
        if (in_array($metric, ['Jumlah Pelanggan Baru', 'Jumlah Pelanggan Setia'])) {
            $viewName = $metric === 'Jumlah Pelanggan Baru' ? 'vw_new_customers_daily' : 'vw_returning_customers_daily';
            $recentCount = DB::table($viewName)
                ->where('business_id', $businessId)
                ->where('sales_date', '>=', $from)
                ->count();

            if ($recentCount === 0) {
                // Use all available data if no recent data
                $latestDate = DB::table($viewName)->where('business_id', $businessId)->max('sales_date');
                if ($latestDate) {
                    $from = Carbon::parse($latestDate)->subDays(30)->toDateString();
                    $monthStart = Carbon::parse($latestDate)->startOfMonth()->toDateString();
                    $hasRecentData = false;
                }
            }
        }

        try {
            switch ($metric) {
                case 'Total Penjualan': {
                    $dailyBase = DB::table('vw_sales_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from);
                    $totalDaily = (clone $dailyBase)->count();
                    $daily = $dailyBase->orderBy('sales_date')
                        ->offset($offset)
                        ->limit($perPage)
                        ->get();
                    $topProducts = DB::table('vw_sales_product_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from)
                        ->select('product_name', DB::raw('SUM(total_revenue) as total_revenue'), DB::raw('SUM(total_quantity) as total_qty'))
                        ->groupBy('product_name')
                        ->orderByDesc(DB::raw('SUM(total_revenue)'))
                        ->limit(10)
                        ->get();
                    $monthly = DB::table('vw_sales_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $monthStart)
                        ->selectRaw('SUM(total_gross_revenue) as total_revenue, SUM(transaction_count) as transaction_count, SUM(total_quantity) as total_quantity')
                        ->first();
                    return [
                        'available' => true,
                        'type' => 'sales',
                        'daily' => $daily,
                        'pagination' => [
                            'total' => $totalDaily,
                            'page' => $page,
                            'per_page' => $perPage,
                            'total_pages' => (int)ceil($totalDaily / $perPage)
                        ],
                        'top' => $topProducts,
                        'monthly' => [
                            'total_revenue' => (float)($monthly->total_revenue ?? 0),
                            'transaction_count' => (int)($monthly->transaction_count ?? 0),
                            'total_quantity' => (float)($monthly->total_quantity ?? 0),
                        ],
                    ];
                }
                case 'Biaya Pokok Penjualan (COGS)': {
                    $dailyBase = DB::table('vw_cogs_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from);
                    $totalDaily = (clone $dailyBase)->count();
                    $daily = $dailyBase->orderBy('sales_date')
                        ->offset($offset)->limit($perPage)->get();
                    $monthly = DB::table('vw_cogs_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $monthStart)
                        ->selectRaw('SUM(total_cogs) as total_cogs')
                        ->first();
                    return [
                        'available' => true,
                        'type' => 'cogs',
                        'daily' => $daily,
                        'pagination' => [
                            'total' => $totalDaily,
                            'page' => $page,
                            'per_page' => $perPage,
                            'total_pages' => (int)ceil($totalDaily / $perPage)
                        ],
                        'monthly' => [
                            'total_cogs' => (float)($monthly->total_cogs ?? 0),
                        ],
                    ];
                }
                case 'Margin Keuntungan (Profit Margin)': {
                    $dailyBase = DB::table('vw_margin_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from);
                    $totalDaily = (clone $dailyBase)->count();
                    $daily = $dailyBase->orderBy('sales_date')
                        ->offset($offset)->limit($perPage)->get();
                    $monthly = DB::table('vw_margin_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $monthStart)
                        ->selectRaw('SUM(total_margin) as total_margin')
                        ->first();
                    return [
                        'available' => true,
                        'type' => 'margin',
                        'daily' => $daily,
                        'pagination' => [
                            'total' => $totalDaily,
                            'page' => $page,
                            'per_page' => $perPage,
                            'total_pages' => (int)ceil($totalDaily / $perPage)
                        ],
                        'monthly' => [
                            'total_margin' => (float)($monthly->total_margin ?? 0),
                        ],
                    ];
                }
                case 'Jumlah Pelanggan Baru': {
                    $daily = DB::table('vw_new_customers_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from)
                        ->orderBy('sales_date')
                        ->get();
                    $monthly = DB::table('vw_new_customers_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $monthStart)
                        ->selectRaw('SUM(new_customers) as new_customers')
                        ->first();

                    // Get recent new customers with details (flexible date range)
                    $recentNewCustomers = Customer::forBusiness($businessId)
                        ->where(function($query) use ($monthStart) {
                            $query->where('customer_type', 'new')
                                  ->orWhere('first_purchase_date', '>=', $monthStart)
                                  ->orWhereNull('customer_type'); // Include customers without explicit type
                        })
                        ->orderBy('first_purchase_date', 'desc')
                        ->limit(10)
                        ->get(['customer_name', 'email', 'first_purchase_date', 'total_spent']);

                    return [
                        'available' => true,
                        'type' => 'new_customers',
                        'daily' => $daily,
                        'pagination' => [
                            'total' => count($daily), // view row count post-range; simple
                            'page' => 1,
                            'per_page' => count($daily),
                            'total_pages' => 1
                        ],
                        'monthly' => [
                            'new_customers' => (int)($monthly->new_customers ?? 0),
                        ],
                        'customers' => $recentNewCustomers,
                    ];
                }
                case 'Jumlah Pelanggan Setia': {
                    $daily = DB::table('vw_returning_customers_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from)
                        ->orderBy('sales_date')
                        ->get();
                    $monthly = DB::table('vw_returning_customers_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $monthStart)
                        ->selectRaw('SUM(returning_customers) as returning_customers')
                        ->first();

                    // Get recent returning customers with details (flexible criteria)
                    $recentReturningCustomers = Customer::forBusiness($businessId)
                        ->where(function($query) use ($monthStart) {
                            $query->where('customer_type', 'returning')
                                  ->orWhere('total_purchases', '>', 1) // Customers with multiple purchases
                                  ->orWhere('last_purchase_date', '>=', $monthStart);
                        })
                        ->orderBy('last_purchase_date', 'desc')
                        ->limit(10)
                        ->get(['customer_name', 'email', 'last_purchase_date', 'total_spent', 'total_purchases']);

                    return [
                        'available' => true,
                        'type' => 'returning_customers',
                        'daily' => $daily,
                        'pagination' => [
                            'total' => count($daily),
                            'page' => 1,
                            'per_page' => count($daily),
                            'total_pages' => 1
                        ],
                        'monthly' => [
                            'returning_customers' => (int)($monthly->returning_customers ?? 0),
                        ],
                        'customers' => $recentReturningCustomers,
                    ];
                }
                case 'Penjualan Produk Terlaris': {
                    // Provide aggregate of top products over 30 days and daily breakdown (already captured in sales_product view)
                    $dailyBase = DB::table('vw_sales_product_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from);
                    $totalDaily = (clone $dailyBase)->count();
                    $daily = $dailyBase->orderBy('sales_date')
                        ->offset($offset)->limit($perPage)->get();
                    $topProducts = DB::table('vw_sales_product_daily')
                        ->where('business_id', $businessId)
                        ->where('sales_date', '>=', $from)
                        ->select('product_name', DB::raw('SUM(total_revenue) as total_revenue'), DB::raw('SUM(total_quantity) as total_qty'))
                        ->groupBy('product_name')
                        ->orderByDesc(DB::raw('SUM(total_revenue)'))
                        ->limit(15)
                        ->get();
                    return [
                        'available' => true,
                        'type' => 'top_products',
                        'daily' => $daily,
                        'pagination' => [
                            'total' => $totalDaily,
                            'page' => $page,
                            'per_page' => $perPage,
                            'total_pages' => (int)ceil($totalDaily / $perPage)
                        ],
                        'top' => $topProducts,
                    ];
                }
            }
        } catch (\Throwable $e) {
            return ['available' => false];
        }

        return ['available' => false];
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
        $user = Auth::user();
        $userBusinessIds = DB::table('business_user')
            ->where('user_id', $user->id)
            ->pluck('business_id')
            ->toArray();

        // Also include businesses owned by the user
        $ownedBusinessIds = Business::where('user_id', $user->id)->pluck('id')->toArray();
        $userBusinessIds = array_merge($userBusinessIds, $ownedBusinessIds);

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
                    'error' => 'Pertanyaan tidak boleh kosong'
                ], 400);
            }

            Log::info('AI Chat Request', [
                'metric_id' => $id,
                'metric_name' => $businessMetric->metric_name,
                'question' => $question
            ]);

            // Enrich metric dengan OLAP data untuk current & previous values
            $enrichedMetric = MetricFormattingService::enrichMetricWithOlapData($businessMetric);

            // Get recent records from MetricRecord (manual entries)
            $recentRecords = MetricRecord::where('business_metric_id', $id)
                ->orderBy('record_date', 'desc')
                ->limit(10)
                ->get(['record_date', 'value', 'notes'])
                ->map(function($record) {
                    return [
                        'date' => $record->record_date->format('Y-m-d'),
                        'value' => number_format($record->value, 0, ',', '.'),
                        'notes' => $record->notes
                    ];
                })
                ->toArray();

            // Get OLAP statistics
            $statistics = $this->getStatistics($enrichedMetric);

            // Get daily data from warehouse for trending
            $dailyTrend = $this->getDailyTrendForAI($enrichedMetric);

            // Prepare comprehensive context for AI
            $context = [
                'metric_name' => $enrichedMetric->metric_name,
                'metric_category' => $enrichedMetric->category ?? 'General',
                'metric_unit' => $enrichedMetric->unit ?? '',
                'business_name' => $enrichedMetric->business->business_name ?? 'Business',
                'business_id' => $enrichedMetric->business_id,

                // Current state
                'current_value' => $enrichedMetric->current_value,
                'previous_value' => $enrichedMetric->previous_value,
                'change_percentage' => $enrichedMetric->change_percentage,
                'formatted_value' => $enrichedMetric->formatted_value,
                'formatted_change' => $enrichedMetric->formatted_change,

                // Statistics from OLAP
                'statistics' => $statistics,

                // Historical data
                'recent_records' => $recentRecords,
                'daily_trend' => $dailyTrend,

                // Metadata
                'data_source' => 'OLAP Warehouse + Manual Records',
                'analysis_period' => '30 hari terakhir'
            ];

            Log::info('AI Context Prepared', [
                'has_current_value' => $enrichedMetric->current_value > 0,
                'has_statistics' => !empty($statistics),
                'records_count' => count($recentRecords),
                'trend_days' => count($dailyTrend)
            ]);

            $aiService = new GeminiAIService();
            $result = $aiService->generateBusinessInsight($question, $context);

            if ($result['success']) {
                Log::info('AI Response Success', [
                    'response_length' => strlen($result['response'])
                ]);
            } else {
                Log::error('AI Response Failed', [
                    'error' => $result['error']
                ]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('AI Chat Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
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

    /**
     * Get daily trend data from warehouse for AI context
     */
    private function getDailyTrendForAI(BusinessMetric $metric)
    {
        $mapping = $this->mapMetricToOlap($metric->metric_name);

        if (!$mapping) {
            return [];
        }

        try {
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subDays(30);

            $data = DB::table($mapping['view'])
                ->where('business_id', $metric->business_id)
                ->whereBetween('sales_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('sales_date', 'asc')
                ->get(['sales_date', $mapping['column']])
                ->map(function($row) use ($mapping) {
                    return [
                        'date' => Carbon::parse($row->sales_date)->format('Y-m-d'),
                        'value' => (float)$row->{$mapping['column']}
                    ];
                })
                ->toArray();

            return $data;

        } catch (\Exception $e) {
            Log::warning("Failed to get daily trend for AI: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Map metric name to OLAP view configuration
     */
    private function mapMetricToOlap(string $metricName): ?array
    {
        return match($metricName) {
            'Total Penjualan' => ['view' => 'vw_sales_daily', 'column' => 'total_gross_revenue'],
            'Biaya Pokok Penjualan (COGS)' => ['view' => 'vw_cogs_daily', 'column' => 'total_cogs'],
            'Margin Keuntungan (Profit Margin)' => ['view' => 'vw_margin_daily', 'column' => 'total_margin'],
            'Penjualan Produk Terlaris' => ['view' => 'vw_sales_product_daily', 'column' => 'total_quantity'],
            'Jumlah Pelanggan Baru' => ['view' => 'vw_new_customers_daily', 'column' => 'new_customers'],
            'Jumlah Pelanggan Setia' => ['view' => 'vw_returning_customers_daily', 'column' => 'returning_customers'],
            default => null,
        };
    }
}
