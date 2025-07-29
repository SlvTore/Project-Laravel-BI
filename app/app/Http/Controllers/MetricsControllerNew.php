<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessMetric;
use App\Models\Business;
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

    public function index()
    {
        $user = Auth::user();
        $business = Business::where('user_id', $user->id)->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'Silakan buat business terlebih dahulu.');
        }

        $businessMetrics = BusinessMetric::where('business_id', $business->id)
                                        ->active()
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        return view('dashboard-metrics.index', compact('businessMetrics', 'business'));
    }

    public function create()
    {
        $user = Auth::user();
        $business = Business::where('user_id', $user->id)->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'Silakan buat business terlebih dahulu.');
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
        $request->validate([
            'selected_metrics' => 'required|array|min:1',
            'selected_metrics.*' => 'string'
        ]);

        $user = Auth::user();
        $business = Business::where('user_id', $user->id)->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'Silakan buat business terlebih dahulu.');
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

                BusinessMetric::create([
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
        $user = Auth::user();
        $business = Business::where('user_id', $user->id)->first();

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

        $user = Auth::user();
        $business = Business::where('user_id', $user->id)->first();

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
        $user = Auth::user();
        $business = Business::where('user_id', $user->id)->first();

        $businessMetric = BusinessMetric::where('business_id', $business->id)
                                      ->findOrFail($id);

        $businessMetric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Metric berhasil dihapus!'
        ]);
    }
}
