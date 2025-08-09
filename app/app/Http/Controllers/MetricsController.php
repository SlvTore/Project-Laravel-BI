<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessMetric;
use App\Models\Business;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MetricsController extends Controller
{
    /**
     * Predefined metric types available for selection
     */
    private function getAvailableMetrics()
    {
        return [
            'Total Penjualan' => [
                'category' => 'Penjualan',
                'icon' => 'bi-currency-dollar',
                'description' => 'Total nilai penjualan yang dihasilkan dalam periode tertentu',
                'unit' => 'Rp'
            ],
            'Biaya Pokok Penjualan (COGS)' => [
                'category' => 'Keuangan',
                'icon' => 'bi-receipt',
                'description' => 'Total biaya langsung yang dikeluarkan untuk menghasilkan produk yang dijual',
                'unit' => 'Rp'
            ],
            'Margin Keuntungan (Profit Margin)' => [
                'category' => 'Keuangan',
                'icon' => 'bi-percent',
                'description' => 'Persentase keuntungan dari penjualan setelah dikurangi biaya pokok penjualan',
                'unit' => '%'
            ],
            'Penjualan Produk Terlaris' => [
                'category' => 'Produk',
                'icon' => 'bi-star-fill',
                'description' => 'Jumlah unit produk terlaris yang terjual dalam periode tertentu',
                'unit' => 'unit'
            ],
            'Jumlah Pelanggan Baru' => [
                'category' => 'Pelanggan',
                'icon' => 'bi-person-plus',
                'description' => 'Jumlah pelanggan baru yang diperoleh dalam periode tertentu',
                'unit' => 'orang'
            ],
            'Jumlah Pelanggan Setia' => [
                'category' => 'Pelanggan',
                'icon' => 'bi-heart-fill',
                'description' => 'Jumlah pelanggan yang melakukan pembelian berulang dalam periode tertentu',
                'unit' => 'orang'
            ]
        ];
    }

    /**
     * Display a listing of the metrics.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Get user's primary business or business they're associated with
        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        // Get metrics for this business
        $businessMetrics = BusinessMetric::where('business_id', $business->id)
                                      ->orderBy('created_at', 'desc')
                                      ->get();

        // Filter metrics based on user role for staff
        if ($user->isStaff()) {
            // Staff can only see metrics they can edit/input data
            $businessMetrics = $businessMetrics->filter(function($metric) {
                return in_array($metric->metric_type, [
                    'Total Penjualan',
                    'Biaya Pokok Penjualan (COGS)',
                    'Penjualan Produk Terlaris',
                    'Jumlah Pelanggan Baru',
                    'Jumlah Pelanggan Setia'
                ]);
            });
        }

        return view('dashboard-metrics.index', compact('businessMetrics', 'business'));
    }

    public function create()
    {
        /** @var User $user */
        $user = Auth::user();

        // Only Business Owner and Administrator can create/import metrics
        if (!$user->canImportMetrics()) {
            abort(403, 'You do not have permission to create metrics. Only business owners and administrators can import new metrics.');
        }

        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        $availableMetrics = $this->getAvailableMetrics();

        // Ambil metrics yang sudah diimport
        $importedMetrics = BusinessMetric::where('business_id', $business->id)
                                       ->active()
                                       ->pluck('metric_name')
                                       ->toArray();

        return view('dashboard-metrics.create', compact('availableMetrics', 'importedMetrics', 'business'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Only Business Owner and Administrator can create/import metrics
        if (!$user->canImportMetrics()) {
            abort(403, 'You do not have permission to create metrics. Only business owners and administrators can import new metrics.');
        }

        $request->validate([
            'selected_metrics' => 'required|array|min:1',
            'selected_metrics.*' => 'string'
        ]);

        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        $availableMetrics = $this->getAvailableMetrics();
        $selectedMetrics = $request->input('selected_metrics', []);
        $createdCount = 0;

        foreach ($selectedMetrics as $metricName) {
            if (!isset($availableMetrics[$metricName])) {
                continue;
            }

            // Cek apakah metric sudah ada
            $existingMetric = BusinessMetric::where('business_id', $business->id)
                                          ->where('metric_name', $metricName)
                                          ->first();

            if (!$existingMetric) {
                $metricData = $availableMetrics[$metricName];

                $newMetric = BusinessMetric::create([
                    'business_id' => $business->id,
                    'metric_name' => $metricName,
                    'category' => $metricData['category'],
                    'icon' => $metricData['icon'],
                    'description' => $metricData['description'],
                    'current_value' => 0,
                    'previous_value' => 0,
                    'unit' => $metricData['unit'],
                    'is_active' => true
                ]);

                // Log activity
                ActivityLog::logActivity(
                    $business->id,
                    $user->id,
                    'metric_created',
                    'New Metric Created',
                    "{$user->name} created metric: {$metricName}",
                    ['metric_id' => $newMetric->id, 'metric_name' => $metricName],
                    'bi-graph-up-arrow',
                    'success'
                );

                $createdCount++;
            }
        }

        if ($createdCount > 0) {
            return redirect()->route('dashboard.metrics')->with('success', $createdCount . ' metrics berhasil ditambahkan!');
        } else {
            return redirect()->route('dashboard.metrics')->with('info', 'Metrics yang dipilih sudah ada.');
        }
    }

    public function edit($id)
    {
        /** @var User $user */
        $user = Auth::user();

        // Get user's primary business or business they're associated with
        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        $businessMetric = BusinessMetric::where('business_id', $business->id)
                                      ->findOrFail($id);

        return view('dashboard-metrics.edit', compact('businessMetric'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'current_value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500'
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Get user's primary business or business they're associated with
        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        $businessMetric = BusinessMetric::where('business_id', $business->id)
                                      ->findOrFail($id);

        // Store current value as previous value
        $businessMetric->update([
            'previous_value' => $businessMetric->current_value,
            'current_value' => $request->current_value,
            'description' => $request->description
        ]);

        return redirect()->route('dashboard.metrics')->with('success', 'Metric berhasil diupdate!');
    }

    public function destroy($id)
    {
        /** @var User $user */
        $user = Auth::user();

        // Only Business Owner and Administrator can delete metrics
        if (!$user->canDeleteMetrics()) {
            abort(403, 'You do not have permission to delete metrics.');
        }

        // Get user's primary business or business they're associated with
        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found. Please complete setup first or ask your business owner for access.');
        }

        $businessMetric = BusinessMetric::where('business_id', $business->id)
                                      ->findOrFail($id);

        $businessMetric->delete();

        // Check if request expects JSON (AJAX) or regular redirect
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Metric berhasil dihapus!',
                'redirect' => route('dashboard.metrics')
            ]);
        }

        return redirect()->route('dashboard.metrics')->with('success', 'Metric berhasil dihapus!');
    }
}
