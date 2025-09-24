<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductionCost;
use App\Services\DataFeedService;
use App\Services\OlapWarehouseService;
use App\Jobs\ProcessDataFeedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataFeedController extends Controller
{
    protected DataFeedService $service;
    protected OlapWarehouseService $warehouse;

    public function __construct(DataFeedService $service, OlapWarehouseService $warehouse)
    {
        $this->service = $service;
        $this->warehouse = $warehouse;
    }

    public function index(Request $request)
    {
        $business = $request->user()->primaryBusiness()->first();
        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'Business context not found.');
        }

        $products = $this->service->getProductsForBusiness($business->id);
        $feeds = $this->service->getFeedsHistoryForDataTable($business->id);

        return view('dashboard-data-feeds.index', [
            'business' => $business,
            'products' => $products,
            'feeds' => $feeds,
            'page_title' => 'Data Feeds',
        ]);
    }

    public function storeManualSales(Request $request)
    {
        try {
            $request->validate([
                'transaction_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.price' => 'required|numeric|min:0.01',
                'items.*.discount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:255'
            ], [
                'items.required' => 'Minimal satu item penjualan harus diisi',
                'items.*.product_id.required' => 'Produk harus dipilih',
                'items.*.product_id.exists' => 'Produk yang dipilih tidak valid',
                'items.*.quantity.required' => 'Kuantitas harus diisi',
                'items.*.quantity.min' => 'Kuantitas minimal 0.01',
                'items.*.price.required' => 'Harga jual harus diisi',
                'items.*.price.min' => 'Harga jual minimal 0.01'
            ]);

            $result = $this->service->processManualSalesInput($request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'record_count' => $result['record_count'],
                        'data_feed_id' => $result['data_feed_id']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Manual sales input error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data'
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt,xlsx',
                'data_type' => 'required|in:sales,costs'
            ], [
                'file.required' => 'File harus dipilih',
                'file.mimes' => 'Format file harus CSV atau XLSX',
                'data_type.required' => 'Tipe data harus dipilih',
                'data_type.in' => 'Tipe data harus sales atau costs'
            ]);

            $file = $request->file('file');
            $options = [
                'data_type' => $request->input('data_type', 'sales')
            ];

            $result = $this->service->processUploadedFile($file, $options);

            if ($result['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'record_count' => $result['record_count'],
                        'data_feed_id' => $result['data_feed_id']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses file'
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value');

        $query = \App\Models\DataFeed::where('business_id', $business->id);
        $recordsTotal = (clone $query)->count();

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('source', 'like', "%$searchValue%")
                  ->orWhere('data_type', 'like', "%$searchValue%")
                  ->orWhere('status', 'like', "%$searchValue%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $columns = ['id', 'source', 'data_type', 'record_count', 'status', 'created_at'];
        $order = $request->input('order', []);
        if (!empty($order)) {
            foreach ($order as $ord) {
                $colIdx = (int)($ord['column'] ?? 0);
                $dir = ($ord['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
                $column = $columns[$colIdx] ?? 'created_at';
                $query->orderBy($column, $dir);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function downloadTemplate(Request $request, string $type)
    {
        $templates = [
            'sales' => [
                'filename' => 'template_penjualan.csv',
                'display_name' => 'Template Data Penjualan.csv'
            ],
            'costs' => [
                'filename' => 'template_biaya.csv',
                'display_name' => 'Template Data Biaya.csv'
            ]
        ];

        if (!isset($templates[$type])) {
            return response()->json(['error' => 'Template tidak ditemukan'], 404);
        }

        $template = $templates[$type];
        $filePath = public_path('templates/' . $template['filename']);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File template tidak ditemukan'], 404);
        }

        return response()->download($filePath, $template['display_name'], [
            'Content-Type' => 'text/csv'
        ]);
    }

    public function storeProductionCost(Request $request, Product $product)
    {
        try {
            $request->validate([
                'category' => 'required|string|max:100',
                'description' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'unit_quantity' => 'nullable|numeric|min:0.01',
                'unit_type' => 'nullable|string|max:50'
            ], [
                'category.required' => 'Kategori biaya harus diisi',
                'description.required' => 'Deskripsi harus diisi',
                'amount.required' => 'Jumlah biaya harus diisi',
                'amount.min' => 'Jumlah biaya minimal 0.01'
            ]);

            $productionCost = $product->productionCosts()->create([
                'category' => $request->category,
                'description' => $request->description,
                'amount' => $request->amount,
                'unit_quantity' => $request->unit_quantity ?? 1,
                'unit_type' => $request->unit_type ?? $product->unit,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Biaya produksi berhasil ditambahkan',
                'data' => $productionCost
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Production cost creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan biaya produksi'
            ], 500);
        }
    }

    public function getProductionCosts(Product $product)
    {
        try {
            $costs = $product->activeProductionCosts()
                ->orderBy('category')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $costs,
                'total_cost' => $costs->sum('amount')
            ]);

        } catch (\Exception $e) {
            Log::error('Get production costs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data biaya produksi'
            ], 500);
        }
    }

    public function deleteProductionCost(ProductionCost $productionCost)
    {
        try {
            $productionCost->delete();

            return response()->json([
                'success' => true,
                'message' => 'Biaya produksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete production cost error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus biaya produksi'
            ], 500);
        }
    }

    // Trigger OLAP transform for a given data feed
    public function transform(Request $request, int $dataFeedId)
    {
        try {
            $async = filter_var($request->input('async', 'true'), FILTER_VALIDATE_BOOLEAN);
            if ($async) {
                ProcessDataFeedJob::dispatch($dataFeedId);
                return response()->json([
                    'success' => true,
                    'message' => 'Transform job dispatched',
                ]);
            }

            $feed = \App\Models\DataFeed::findOrFail($dataFeedId);
            $feed->update(['status' => 'transforming', 'log_message' => 'Starting OLAP transform']);
            $count = $this->warehouse->loadFactsFromStaging($feed);
            $feed->update(['status' => 'transformed', 'log_message' => "Transformed {$count} rows into fact_sales"]);
            return response()->json([
                'success' => true,
                'message' => 'Transform completed',
                'rows' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Check transform status for a given data feed
    public function transformStatus(int $dataFeedId)
    {
        $feed = \App\Models\DataFeed::find($dataFeedId);
        if (!$feed) return response()->json(['success' => false, 'message' => 'Feed not found'], 404);
        return response()->json([
            'success' => true,
            'status' => $feed->status,
            'log' => $feed->log_message,
        ]);
    }

    // Backfill OLAP fact_sales from existing transactions
    public function backfillFacts(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $rows = $this->warehouse->loadFactsFromTransactions($business->id);
        return response()->json([
            'success' => true,
            'message' => "Backfilled {$rows} fact rows from existing transactions",
        ]);
    }
}
