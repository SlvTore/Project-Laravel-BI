<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductionCost;
use App\Models\DataFeed;
use App\Models\StagingSalesItem;
use App\Models\StagingCost;
use Illuminate\Support\Facades\DB;
use App\Services\DataFeedService;
use App\Services\Exceptions\DataFeedCommitException;
use App\Services\OlapWarehouseService;
use App\Jobs\ProcessDataFeedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function preview(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
            ], [
                'file.required' => 'File harus dipilih',
                'file.mimes' => 'Format file harus CSV'
            ]);

            $file = $request->file('file');

            $business = $request->user()->primaryBusiness()->first();
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business context not found.',
                ], 422);
            }

            // Universal CSV format - no data type selection needed
            $result = $this->service->generatePreview($business, $file, 'universal');

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Preview generated successfully',
                'rows' => $result['preview'] ?? [],
                'summary' => $result['summary'] ?? [],
                'issues' => $result['issues'] ?? [],
                'upload_token' => $result['token'] ?? null,
                'data_type' => 'universal',
                'file_name' => $file->getClientOriginalName()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Data feed preview failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat pratinjau data.',
            ], 500);
        }
    }

    public function commit(Request $request)
    {
        $request->validate([
            'upload_token' => 'required|string',
            'auto_create_products' => 'sometimes|boolean',
        ], [
            'upload_token.required' => 'Token upload diperlukan untuk commit.',
            'data_type.in' => 'Tipe data tidak valid.',
        ]);

        $business = $request->user()->primaryBusiness()->first();
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business context not found.',
            ], 422);
        }

        try {
            $token = $request->input('upload_token');
            $result = $this->service->commitPreview($business, $token, [
                'auto_create_products' => $request->boolean('auto_create_products', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data feed berhasil diantrikan untuk diproses.',
                'data' => $result,
            ]);
        } catch (DataFeedCommitException $e) {
            $status = $e->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 422;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
            ], $status);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Data feed commit failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'upload_token' => $request->input('upload_token'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses commit data feed.',
            ], 500);
        }
    }

    public function autoCreateProducts(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:150',
            'products.*.category' => 'nullable|string|max:150',
            'products.*.unit' => 'nullable|string|max:50',
            'products.*.selling_price' => 'nullable|numeric|min:0',
            'products.*.cost_price' => 'nullable|numeric|min:0',
        ], [
            'products.required' => 'Daftar produk tidak boleh kosong.',
            'products.*.name.required' => 'Nama produk wajib diisi.',
        ]);

        $business = $request->user()->primaryBusiness()->first();
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business context not found.',
            ], 422);
        }

        $results = [];

        foreach ($validated['products'] as $payload) {
            $name = trim($payload['name']);
            if ($name === '') {
                continue;
            }

            $existing = Product::where('business_id', $business->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($existing) {
                $updates = array_filter([
                    'category' => $payload['category'] ?? null,
                    'unit' => $payload['unit'] ?? null,
                    'selling_price' => $payload['selling_price'] ?? null,
                    'cost_price' => $payload['cost_price'] ?? null,
                    'status' => 'active',
                ], fn ($value) => $value !== null);

                if (!$existing->card_id) {
                    $updates['card_id'] = 'product-card-' . Str::uuid()->toString();
                }

                if (!empty($updates)) {
                    $existing->fill($updates);
                    if ($existing->isDirty()) {
                        $existing->save();
                    }
                }

                $existing->refresh();

                $results[] = [
                    'product_id' => $existing->id,
                    'card_id' => $existing->card_id,
                    'name' => $existing->name,
                    'status' => 'existing',
                    'product' => $existing->toArray(),
                ];
                continue;
            }

            try {
                $product = $this->service->createProduct([
                    'name' => $name,
                    'category' => $payload['category'] ?? null,
                    'unit' => $payload['unit'] ?? null,
                    'selling_price' => $payload['selling_price'] ?? null,
                    'cost_price' => $payload['cost_price'] ?? null,
                    'status' => 'active',
                    'card_id' => 'product-card-' . Str::uuid()->toString(),
                ]);

                $results[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'card_id' => $product->card_id,
                    'status' => 'created',
                    'product' => $product->toArray(),
                ];
            } catch (\Throwable $e) {
                Log::error('Auto-create product failed: ' . $e->getMessage(), [
                    'business_id' => $business->id,
                    'payload' => $payload,
                ]);

                $results[] = [
                    'name' => $name,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        if (empty($results)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada produk yang dapat dibuat.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'results' => $results,
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
                $feed = \App\Models\DataFeed::find($dataFeedId);
                if ($feed) {
                    $feed->update([
                        'status' => 'queued',
                        'log_message' => 'Transform job dispatched',
                        'summary' => $this->mergeSummary($feed->summary, [
                            'stage' => 'queued',
                            'queued_at' => now()->toISOString(),
                            'error' => null,
                        ]),
                    ]);
                }
                ProcessDataFeedJob::dispatch($dataFeedId);
                return response()->json([
                    'success' => true,
                    'message' => 'Transform job dispatched',
                ]);
            }

            $feed = \App\Models\DataFeed::findOrFail($dataFeedId);
            $feed->update(['status' => 'transforming', 'log_message' => 'Starting OLAP transform']);
            $feed->update([
                'summary' => $this->mergeSummary($feed->summary, [
                    'stage' => 'transforming',
                    'transform_started_at' => now()->toISOString(),
                    'error' => null,
                ]),
            ]);
            $summary = $this->warehouse->loadFactsFromStaging($feed);
            $rows = $summary['records'] ?? 0;
            $feed->update([
                'status' => 'transformed',
                'log_message' => sprintf(
                    'Transformed %d rows into fact_sales | Revenue Rp%s | HPP Rp%s | Margin Rp%s',
                    $rows,
                    number_format($summary['gross_revenue'] ?? 0, 0, ',', '.'),
                    number_format($summary['cogs_amount'] ?? 0, 0, ',', '.'),
                    number_format($summary['gross_margin_amount'] ?? 0, 0, ',', '.')
                )
            ]);
            $feed->update([
                'summary' => $this->mergeSummary($feed->fresh()->summary, [
                    'stage' => 'transformed',
                    'transform_finished_at' => now()->toISOString(),
                    'metrics' => [
                        'records' => (int) ($summary['records'] ?? 0),
                        'gross_revenue' => $summary['gross_revenue'] ?? null,
                        'cogs_amount' => $summary['cogs_amount'] ?? null,
                        'gross_margin_amount' => $summary['gross_margin_amount'] ?? null,
                        'gross_margin_percent' => $summary['gross_margin_percent'] ?? null,
                    ],
                ]),
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Transform completed',
                'rows' => $rows,
                'metrics' => $summary,
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
            'summary' => $feed->summary,
        ]);
    }

    // Download universal template
    public function downloadUniversalTemplate(Request $request)
    {
        $format = $request->input('format', 'csv');

        if ($format !== 'csv') {
            return response()->json([
                'success' => false,
                'message' => 'Only CSV format is supported for universal template'
            ], 400);
        }

        $templatePath = storage_path('templates/universal_data_template.csv');

        // Always create/update the template with fresh data
        $headers = [
            'transaction_date',
            'customer_name',
            'customer_email',
            'customer_phone',
            'product_name',
            'product_category',
            'quantity',
            'unit',
            'selling_price',
            'discount',
            'tax_amount',
            'shipping_cost',
            'payment_method',
            'notes',
            'product_cost_price',
            'material_name',
            'material_quantity',
            'material_unit',
            'material_cost_per_unit'
        ];

        $sampleData = [
            [
                '2024-01-15',
                'John Doe',
                'john@example.com',
                '081234567890',
                'Nasi Goreng Spesial',
                'Makanan',
                '2',
                'Porsi',
                '25000',
                '0',
                '2500',
                '5000',
                'Cash',
                'Pesanan untuk acara kantor',
                '18000',
                'Beras',
                '0.5',
                'Kg',
                '12000'
            ],
            [
                '2024-01-15',
                'John Doe',
                'john@example.com',
                '081234567890',
                'Nasi Goreng Spesial',
                'Makanan',
                '2',
                'Porsi',
                '25000',
                '0',
                '2500',
                '5000',
                'Cash',
                'Pesanan untuk acara kantor',
                '18000',
                'Ayam',
                '0.3',
                'Kg',
                '35000'
            ],
            [
                '2024-01-16',
                'Jane Smith',
                'jane@example.com',
                '087654321098',
                'Kopi Arabica Premium',
                'Minuman',
                '1',
                'Cup',
                '15000',
                '1000',
                '0',
                '0',
                'Transfer',
                'Kopi pagi',
                '8000',
                'Kopi Arabica',
                '20',
                'Gram',
                '500'
            ],
            [
                '2024-01-17',
                'Ahmad Rahman',
                'ahmad@example.com',
                '089876543210',
                'Kaos Polos Cotton',
                'Fashion',
                '3',
                'Pcs',
                '85000',
                '10000',
                '0',
                '15000',
                'Cash',
                'Ukuran M L XL',
                '45000',
                'Kain Cotton',
                '0.5',
                'Meter',
                '60000'
            ]
        ];

        // Ensure directory exists
        if (!file_exists(dirname($templatePath))) {
            mkdir(dirname($templatePath), 0755, true);
        }

        $content = [];
        $content[] = implode(',', $headers);
        foreach ($sampleData as $row) {
            $content[] = implode(',', $row);
        }

        file_put_contents($templatePath, implode("\n", $content));

        return response()->download($templatePath, 'template_data_universal.csv');
    }

    // Backfill OLAP fact_sales from existing transactions
    public function backfillFacts(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $summary = $this->warehouse->loadFactsFromTransactions($business->id);
        return response()->json([
            'success' => true,
            'message' => "Backfilled {$summary['records']} fact rows from existing transactions",
            'metrics' => $summary,
        ]);
    }

    /**
     * List recent uploaded data feeds for the authenticated business
     */
    public function listUploads(Request $request)
    {
        $business = $request->user()->primaryBusiness()->first();
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business context not found.'
            ], 422);
        }

        $limit = (int) $request->input('limit', 25);
        // Some historical records may not have stored an original_name column (not in schema).
        // We alias source as original_name for frontend expectations.
        $feeds = DataFeed::where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->limit($limit > 0 && $limit <= 200 ? $limit : 25)
            ->get([
                'id',
                'original_name',
                'data_type',
                'source',
                'record_count',
                'status',
                'created_at',
                'log_message',
                'summary',
            ]);

        return response()->json([
            'success' => true,
            'data' => $feeds,
        ]);
    }

    /**
     * Delete a data feed (and its staging rows) if owned by business and not processing.
     */
    public function deleteFeed(Request $request, int $id)
    {
        $business = $request->user()->primaryBusiness()->first();
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business context not found.'
            ], 422);
        }

        /** @var DataFeed|null $feed */
        $feed = DataFeed::where('business_id', $business->id)->where('id', $id)->first();
        if (!$feed) {
            return response()->json([
                'success' => false,
                'message' => 'Data feed tidak ditemukan.'
            ], 404);
        }

        if (in_array($feed->status, ['processing','transforming'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data feed sedang diproses dan tidak dapat dihapus saat ini.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            DB::table('fact_sales')->where('data_feed_id', $feed->id)->delete();
            // Hapus staging rows yang terkait
            StagingSalesItem::where('data_feed_id', $feed->id)->delete();
            StagingCost::where('data_feed_id', $feed->id)->delete();

            $feed->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data feed berhasil dihapus.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete data feed failed: '.$e->getMessage(), ['feed_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data feed.'
            ], 500);
        }
    }

    protected function mergeSummary(?array $current, array $changes): array
    {
        $base = [
            'stage' => null,
            'queued_at' => null,
            'transform_started_at' => null,
            'transform_finished_at' => null,
            'metrics' => null,
            'issues' => [],
            'error' => null,
        ];

        $summary = $current ? array_replace_recursive($base, $current) : $base;

        return array_replace_recursive($summary, $changes);
    }
}
