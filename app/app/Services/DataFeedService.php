<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\Business;
use App\Models\DataFeed;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\StagingCost;
use App\Models\StagingSalesItem;
use App\Services\Exceptions\DataFeedCommitException;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DataFeedService
{
    public function processManualSalesInput(array $payload)
    {
        try {
            DB::beginTransaction();

            // Validate payload
            if (!isset($payload['items']) || empty($payload['items'])) {
                throw new Exception('Item penjualan tidak boleh kosong');
            }

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not authenticated');
            }

            $business = $user->primaryBusiness()->first();
            if (!$business) {
                throw new Exception('Business context not found');
            }

            // Create DataFeed record
            $dataFeed = DataFeed::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'source' => 'manual_input',
                'data_type' => 'sales',
                'record_count' => count($payload['items']),
                'status' => 'processing'
            ]);

            $recordCount = 0;
            foreach ($payload['items'] as $item) {
                // Validate item data
                if (empty($item['product_id']) || $item['quantity'] <= 0 || $item['price'] <= 0) {
                    continue;
                }

                // Get product details
                $product = Product::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                // Create staging record
                StagingSalesItem::create([
                    'data_feed_id' => $dataFeed->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_at_transaction' => $product->unit,
                    'selling_price_at_transaction' => $item['price'],
                    'discount_per_item' => $item['discount'] ?? 0,
                    'transaction_date' => $payload['transaction_date'] ?? now(),
                    'notes' => $payload['notes']
                ]);

                $recordCount++;
            }

            // Update DataFeed status
            $dataFeed->update([
                'record_count' => $recordCount,
                'status' => $recordCount > 0 ? 'completed' : 'failed',
                'log_message' => $recordCount > 0 ? "Successfully processed $recordCount items" : 'No valid items found'
            ]);

            if ($recordCount > 0) {
                \App\Jobs\ProcessDataFeedJob::dispatch($dataFeed->id);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => "Successfully processed $recordCount sales items",
                'record_count' => $recordCount,
                'data_feed_id' => $dataFeed->id
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Manual sales input failed: ' . $e->getMessage());

            if (isset($dataFeed)) {
                $dataFeed->update([
                    'status' => 'failed',
                    'log_message' => $e->getMessage()
                ]);
            }

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function generatePreview(Business $business, UploadedFile $file, string $dataType = 'sales'): array
    {
        // Handle universal format
        if ($dataType === 'universal') {
            return $this->generateUniversalPreview($business, $file);
        }

        $dataType = $dataType === 'costs' ? 'costs' : 'sales';

        $extension = strtolower($file->getClientOriginalExtension() ?? '');
        if ($extension !== 'csv') {
            throw ValidationException::withMessages([
                'file' => 'Saat ini hanya file CSV yang dapat digunakan untuk pratinjau.',
            ]);
        }

        $maxSizeMb = (int) config('data_feeds.preview.max_file_size_mb', 10);
        if ($file->getSize() > ($maxSizeMb * 1024 * 1024)) {
            throw ValidationException::withMessages([
                'file' => "Ukuran file melebihi batas {$maxSizeMb}MB.",
            ]);
        }

        $sampleSize = (int) config('data_feeds.preview.sample_size', 50);
        $tokenTtl = (int) config('data_feeds.preview.token_ttl_minutes', 60);

        $token = (string) Str::uuid();
        $directory = "tmp/data-feeds/{$business->id}";
        $storedPath = $file->storeAs($directory, "{$token}.{$extension}");
        $absolutePath = Storage::path($storedPath);

        [$headers, $rows, $summary] = $this->buildPreviewData($business, $absolutePath, $dataType, $sampleSize);

        Cache::put($this->previewCacheKey($token), [
            'business_id' => $business->id,
            'data_type' => $dataType,
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now()->toISOString(),
        ], now()->addMinutes($tokenTtl));

        return [
            'upload_token' => $token,
            'file_name' => $file->getClientOriginalName(),
            'data_type' => $dataType,
            'headers' => $headers,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    protected function generateUniversalPreview(Business $business, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?? '');
        if ($extension !== 'csv') {
            throw ValidationException::withMessages([
                'file' => 'Saat ini hanya file CSV yang dapat digunakan untuk pratinjau.',
            ]);
        }

        $maxSizeMb = (int) config('data_feeds.preview.max_file_size_mb', 10);
        if ($file->getSize() > ($maxSizeMb * 1024 * 1024)) {
            throw ValidationException::withMessages([
                'file' => "Ukuran file melebihi batas {$maxSizeMb}MB.",
            ]);
        }

        $sampleSize = (int) config('data_feeds.preview.sample_size', 50);
        $tokenTtl = (int) config('data_feeds.preview.token_ttl_minutes', 60);

        $token = (string) Str::uuid();
        $directory = "tmp/data-feeds/{$business->id}";
        $storedPath = $file->storeAs($directory, "{$token}.{$extension}");
        $absolutePath = Storage::path($storedPath);

        [$headers, $rows, $summary] = $this->buildUniversalPreviewData($business, $absolutePath, $sampleSize);

        Cache::put($this->previewCacheKey($token), [
            'business_id' => $business->id,
            'data_type' => 'universal',
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now()->toISOString(),
        ], now()->addMinutes($tokenTtl));

        return [
            'message' => 'Preview data universal berhasil dibuat',
            'preview' => $rows,
            'summary' => $summary,
            'issues' => $summary['issues'] ?? [],
            'token' => $token
        ];
    }

    protected function buildUniversalPreviewData(Business $business, string $filePath, int $sampleSize): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception('Cannot open file for reading');
        }

        $headers = [];
        $rows = [];
        $summary = [
            'total_rows' => 0,
            'transactions_count' => 0,
            'unique_customers' => 0,
            'unique_products' => 0,
            'total_revenue' => 0,
            'new_product_candidates' => [],
            'issues' => []
        ];

        $customers = [];
        $products = [];
        $transactions = [];
        $newProducts = [];
        $rowIndex = 0;

        // Read header
        if (($headerRow = fgetcsv($handle)) !== false) {
            $headers = $headerRow;
        }

        // Expected universal CSV headers
        $expectedHeaders = [
            'transaction_date', 'customer_name', 'customer_email', 'customer_phone',
            'product_name', 'product_category', 'quantity', 'unit', 'selling_price',
            'discount', 'tax_amount', 'shipping_cost', 'payment_method', 'notes',
            'product_cost_price', 'material_name', 'material_quantity',
            'material_unit', 'material_cost_per_unit'
        ];

        // Validate headers
        $missingHeaders = array_diff($expectedHeaders, $headers);
        if (!empty($missingHeaders)) {
            $summary['issues'][] = [
                'type' => 'warning',
                'message' => 'Kolom yang tidak ditemukan: ' . implode(', ', $missingHeaders)
            ];
        }

        // Process rows
        while (($row = fgetcsv($handle)) !== false && count($rows) < $sampleSize) {
            $rowIndex++;
            $summary['total_rows']++;

            if (count($row) < count($headers)) {
                continue; // Skip incomplete rows
            }

            $rowData = array_combine($headers, $row);

            // Track unique customers
            if (!empty($rowData['customer_name'])) {
                $customerKey = strtolower(trim($rowData['customer_name']));
                if (!in_array($customerKey, $customers)) {
                    $customers[] = $customerKey;
                }
            }

            // Track unique products and check if they exist
            if (!empty($rowData['product_name'])) {
                $productName = trim($rowData['product_name']);
                $productKey = strtolower($productName);

                if (!in_array($productKey, $products)) {
                    $products[] = $productKey;

                    // Check if product exists in database
                    $existingProduct = Product::where('business_id', $business->id)
                        ->where('name', 'LIKE', '%' . $productName . '%')
                        ->first();

                    if (!$existingProduct) {
                        $newProducts[] = [
                            'name' => $productName,
                            'category' => $rowData['product_category'] ?? 'Lainnya',
                            'selling_price' => $rowData['selling_price'] ?? 0,
                            'cost_price' => $rowData['product_cost_price'] ?? 0,
                            'unit' => $rowData['unit'] ?? 'Pcs'
                        ];
                    }
                }
            }

            // Track revenue
            $quantity = floatval($rowData['quantity'] ?? 0);
            $sellingPrice = floatval($rowData['selling_price'] ?? 0);
            $discount = floatval($rowData['discount'] ?? 0);
            $itemTotal = ($quantity * $sellingPrice) - $discount;
            $summary['total_revenue'] += $itemTotal;

            // Check for validation issues
            $issues = [];
            $isValid = true;

            if (empty($rowData['product_name'])) {
                $issues[] = ['type' => 'error', 'message' => 'Nama produk tidak boleh kosong'];
                $isValid = false;
            }

            if (empty($rowData['customer_name'])) {
                $issues[] = ['type' => 'warning', 'message' => 'Nama customer kosong'];
            }

            if ($quantity <= 0) {
                $issues[] = ['type' => 'warning', 'message' => 'Quantity harus lebih dari 0'];
            }

            // Format row for display with all universal CSV fields
            $rows[] = [
                'row_index' => $rowIndex,
                'valid' => $isValid,
                'issues' => $issues,
                'original' => $rowData,
                'normalized' => [
                    'transaction_date' => $rowData['transaction_date'] ?? '',
                    'customer_name' => $rowData['customer_name'] ?? '',
                    'customer_email' => $rowData['customer_email'] ?? '',
                    'customer_phone' => $rowData['customer_phone'] ?? '',
                    'product_name' => $rowData['product_name'] ?? '',
                    'product_category' => $rowData['product_category'] ?? '',
                    'quantity' => $quantity,
                    'unit' => $rowData['unit'] ?? 'Pcs',
                    'selling_price' => $sellingPrice,
                    'discount' => floatval($rowData['discount'] ?? 0),
                    'tax_amount' => floatval($rowData['tax_amount'] ?? 0),
                    'shipping_cost' => floatval($rowData['shipping_cost'] ?? 0),
                    'payment_method' => $rowData['payment_method'] ?? '',
                    'notes' => $rowData['notes'] ?? '',
                    'product_cost_price' => floatval($rowData['product_cost_price'] ?? 0),
                    'material_name' => $rowData['material_name'] ?? '',
                    'material_quantity' => floatval($rowData['material_quantity'] ?? 0),
                    'material_unit' => $rowData['material_unit'] ?? '',
                    'material_cost_per_unit' => floatval($rowData['material_cost_per_unit'] ?? 0)
                ],
                'product_match' => [
                    'status' => 'ok' // Will be updated when checking existing products
                ]
            ];

            // Track unique transactions
            $transactionKey = ($rowData['transaction_date'] ?? '') . '_' . ($rowData['customer_name'] ?? '');
            if (!in_array($transactionKey, $transactions)) {
                $transactions[] = $transactionKey;
            }
        }

        fclose($handle);

        $summary['unique_customers'] = count($customers);
        $summary['unique_products'] = count($products);
        $summary['transactions_count'] = count($transactions);
        $summary['new_product_candidates'] = $newProducts;

        // Calculate valid and invalid rows for frontend display
        $validRows = array_filter($rows, fn($row) => $row['valid'] ?? true);
        $summary['valid_rows'] = count($validRows);
        $summary['invalid_rows'] = $summary['total_rows'] - $summary['valid_rows'];

        return [$headers, $rows, $summary];
    }

    public function commitPreview(Business $business, string $token, array $options = []): array
    {
        $cacheKey = $this->previewCacheKey($token);
        $cached = Cache::get($cacheKey);

        if (!$cached) {
            throw new DataFeedCommitException('Upload token tidak ditemukan atau sudah kedaluwarsa.', 410);
        }

        if (($cached['business_id'] ?? null) !== $business->id) {
            throw new DataFeedCommitException('Token tidak sesuai dengan bisnis Anda.', 403);
        }

        $storagePath = $cached['path'] ?? null;
        if (!$storagePath || !Storage::exists($storagePath)) {
            Cache::forget($cacheKey);
            throw new DataFeedCommitException('File untuk token ini tidak ditemukan. Silakan ulangi proses upload.', 410);
        }

        $dataType = $cached['data_type'] ?? 'sales';

        // Handle universal format
        if ($dataType === 'universal') {
            return $this->commitUniversalPreview($business, $cached, $options);
        }

        $dataType = $dataType === 'costs' ? 'costs' : 'sales';
        $autoCreateProducts = (bool) ($options['auto_create_products'] ?? false);

        $absolutePath = Storage::path($storagePath);
        $handle = fopen($absolutePath, 'r');
        if (!$handle) {
            throw new DataFeedCommitException('Tidak dapat membuka file untuk commit.', 500);
        }

        $rowsForInsert = [];
        $invalidRows = [];
        $missingProducts = [];
        $fuzzyMatches = [];
        $lineNumber = 1;

        try {
            $rawHeaders = fgetcsv($handle);
            if (!$rawHeaders) {
                throw new DataFeedCommitException('File CSV tampak kosong atau tidak valid.', 422);
            }

            $headers = $this->normalizeHeaders($rawHeaders);
            $this->assertRequiredColumns($headers, $dataType);

            $products = $dataType === 'sales'
                ? $this->getProductsForBusiness($business->id)
                : collect();

            while (($values = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($this->rowIsEmpty($values)) {
                    continue;
                }

                if (count($values) < count($headers)) {
                    $values = array_pad($values, count($headers), null);
                }

                $row = array_combine($headers, $values);
                if ($row === false) {
                    continue;
                }

                if ($dataType === 'sales') {
                    $evaluation = $this->evaluateSalesPreviewRow($row, $products);
                    $matchStatus = $evaluation['product_match']['status'] ?? 'missing';
                    $normalizedName = $evaluation['normalized']['product_name'] ?? null;

                    if (!$evaluation['valid']) {
                        $invalidRows[] = [
                            'row' => $lineNumber,
                            'issues' => $evaluation['issues'],
                            'data' => $evaluation['original'],
                        ];
                        continue;
                    }

                    if ($matchStatus === 'fuzzy') {
                        $fuzzyMatches[] = [
                            'row' => $lineNumber,
                            'product_name' => $normalizedName,
                            'suggestions' => $evaluation['product_match']['suggestions'] ?? [],
                        ];
                    }

                    if ($matchStatus === 'missing' && $normalizedName) {
                        $missingProducts[$normalizedName] = [
                            'product_name' => $normalizedName,
                        ];
                    }

                    $rowsForInsert[] = [
                        'type' => 'sales',
                        'evaluation' => $evaluation,
                        'notes' => $this->cleanString($this->firstValue($row, ['notes', 'catatan'])),
                        'row' => $lineNumber,
                    ];
                } else {
                    $evaluation = $this->evaluateCostPreviewRow($row);

                    if (!$evaluation['valid']) {
                        $invalidRows[] = [
                            'row' => $lineNumber,
                            'issues' => $evaluation['issues'],
                            'data' => $evaluation['original'],
                        ];
                        continue;
                    }

                    $rowsForInsert[] = [
                        'type' => 'costs',
                        'evaluation' => $evaluation,
                        'metadata' => [
                            'vendor' => $this->cleanString($this->firstValue($row, ['vendor', 'supplier'])),
                            'invoice_number' => $this->cleanString($this->firstValue($row, ['invoice_number', 'no_invoice'])),
                        ],
                        'row' => $lineNumber,
                    ];
                }
            }
        } finally {
            fclose($handle);
        }

        if (!empty($invalidRows)) {
            throw new DataFeedCommitException(
                'Masih terdapat baris yang tidak valid. Perbaiki data sebelum melakukan commit.',
                422,
                ['invalid_rows' => $invalidRows]
            );
        }

        if ($dataType === 'sales') {
            if (!empty($fuzzyMatches)) {
                throw new DataFeedCommitException(
                    'Terdapat produk yang memerlukan konfirmasi sebelum diproses.',
                    422,
                    ['unresolved_products' => $fuzzyMatches]
                );
            }

            if (!$autoCreateProducts && !empty($missingProducts)) {
                throw new DataFeedCommitException(
                    'Beberapa produk belum terdaftar. Buat produk terlebih dahulu atau aktifkan opsi auto-create.',
                    422,
                    ['missing_products' => array_values($missingProducts)]
                );
            }
        }

        if (empty($rowsForInsert)) {
            throw new DataFeedCommitException('Tidak ada baris yang dapat diproses dari file ini.', 422);
        }

        $createdProducts = [];
        $createdProductNames = [];

        DB::beginTransaction();
        try {
            $dataFeed = DataFeed::create([
                'business_id' => $business->id,
                'user_id' => Auth::id(),
                'source' => 'import:' . ($cached['original_name'] ?? 'unknown.csv'),
                'data_type' => $dataType,
                'record_count' => 0,
                'status' => 'processing',
            ]);

            $recordCount = 0;

            foreach ($rowsForInsert as $payload) {
                if ($payload['type'] === 'sales') {
                    $evaluation = $payload['evaluation'];
                    $normalized = $evaluation['normalized'];
                    $productMatch = $evaluation['product_match'] ?? [];
                    $productId = $productMatch['product_id'] ?? null;

                    if (!$productId && !empty($normalized['product_name'])) {
                        $key = mb_strtolower($normalized['product_name']);

                        if (($productMatch['status'] ?? null) === 'exact') {
                            $productId = $productMatch['product_id'];
                        } elseif (($productMatch['status'] ?? null) === 'missing' && $autoCreateProducts) {
                            if (!isset($createdProducts[$key])) {
                                $product = Product::create([
                                    'business_id' => $business->id,
                                    'name' => $normalized['product_name'],
                                    'unit' => $normalized['unit'] ?? 'pcs',
                                    'selling_price' => $normalized['price'] ?? 0,
                                    'cost_price' => 0,
                                    'status' => 'active',
                                ]);
                                $createdProducts[$key] = $product;
                                $createdProductNames[] = $product->name;
                                $products->push($product);
                            }

                            $productId = $createdProducts[$key]->id;
                        } elseif (($productMatch['status'] ?? null) === 'fuzzy') {
                            throw new DataFeedCommitException(
                                'Produk belum terkonfirmasi. Silakan perbaiki terlebih dahulu.',
                                422,
                                ['unresolved_products' => [[
                                    'row' => $payload['row'],
                                    'product_name' => $normalized['product_name'],
                                    'suggestions' => $productMatch['suggestions'] ?? [],
                                ]]]
                            );
                        }
                    }

                    $transactionDate = $this->parseDate($evaluation['original']['transaction_date'] ?? $normalized['transaction_date'] ?? null) ?? now();

                    StagingSalesItem::create([
                        'data_feed_id' => $dataFeed->id,
                        'product_id' => $productId,
                        'product_name' => $normalized['product_name'] ?? $evaluation['original']['product_name'],
                        'quantity' => $normalized['quantity'] ?? 0,
                        'unit_at_transaction' => $normalized['unit'] ?? null,
                        'selling_price_at_transaction' => $normalized['price'] ?? 0,
                        'discount_per_item' => $normalized['discount'] ?? 0,
                        'transaction_date' => $transactionDate->toDateTimeString(),
                        'notes' => $payload['notes'],
                    ]);

                    $recordCount++;
                } else {
                    $evaluation = $payload['evaluation'];
                    $normalized = $evaluation['normalized'];
                    $meta = $payload['metadata'] ?? [];
                    $costDate = $this->parseDate($evaluation['original']['cost_date'] ?? $normalized['cost_date'] ?? null) ?? now();

                    StagingCost::create([
                        'data_feed_id' => $dataFeed->id,
                        'category' => $normalized['category'] ?? '',
                        'description' => $normalized['description'] ?? '',
                        'amount' => $normalized['amount'] ?? 0,
                        'vendor' => $meta['vendor'] ?? null,
                        'invoice_number' => $meta['invoice_number'] ?? null,
                        'cost_date' => $costDate->toDateString(),
                    ]);

                    $recordCount++;
                }
            }

            if ($recordCount === 0) {
                $dataFeed->update([
                    'status' => 'failed',
                    'log_message' => 'Tidak ada baris valid untuk diproses.',
                ]);

                throw new DataFeedCommitException('Tidak ada baris valid untuk diproses.', 422);
            }

            $dataFeed->update([
                'record_count' => $recordCount,
                'status' => 'queued',
                'log_message' => "Queued for processing ({$recordCount} baris)",
            ]);

            \App\Jobs\ProcessDataFeedJob::dispatch($dataFeed->id);

            DB::commit();

            Cache::forget($cacheKey);
            Storage::delete($storagePath);

            return [
                'data_feed_id' => $dataFeed->id,
                'record_count' => $recordCount,
                'status' => 'queued',
                'new_products' => array_values(array_unique($createdProductNames)),
            ];
        } catch (DataFeedCommitException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Data feed commit failed: ' . $e->getMessage(), [
                'business_id' => $business->id,
                'token' => $token,
            ]);
            throw new DataFeedCommitException('Terjadi kesalahan saat memproses commit data feed.', 500);
        }
    }

    public function processUploadedFile(UploadedFile $file, array $options = [])
    {
        try {
            DB::beginTransaction();

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not authenticated');
            }

            $business = $user->primaryBusiness()->first();
            if (!$business) {
                throw new Exception('Business context not found');
            }

            // Create DataFeed record
            $dataFeed = DataFeed::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'source' => 'import:' . $file->getClientOriginalName(),
                'data_type' => $options['data_type'] ?? 'sales',
                'record_count' => 0,
                'status' => 'processing'
            ]);

            $extension = strtolower($file->getClientOriginalExtension());
            $recordCount = 0;

            if ($extension === 'csv') {
                $recordCount = $this->processCsvFile($file, $dataFeed, $options);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                throw new Exception('XLSX processing will be implemented in next iteration');
            } else {
                throw new Exception('Unsupported file format. Please use CSV or XLSX files.');
            }

            // Update DataFeed status
            $dataFeed->update([
                'record_count' => $recordCount,
                'status' => $recordCount > 0 ? 'completed' : 'failed',
                'log_message' => $recordCount > 0 ? "Successfully processed $recordCount records" : 'No valid records found'
            ]);

            if ($recordCount > 0) {
                \App\Jobs\ProcessDataFeedJob::dispatch($dataFeed->id);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => "File processed successfully. $recordCount records imported.",
                'record_count' => $recordCount,
                'data_feed_id' => $dataFeed->id
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('File upload processing failed: ' . $e->getMessage());

            if (isset($dataFeed)) {
                $dataFeed->update([
                    'status' => 'failed',
                    'log_message' => $e->getMessage()
                ]);
            }

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function processCsvFile(UploadedFile $file, DataFeed $dataFeed, array $options): int
    {
        $recordCount = 0;
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            throw new Exception('Cannot read CSV file');
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new Exception('CSV file appears to be empty or invalid');
        }

        // Normalize headers (lowercase, trim)
        $headers = array_map(function($h) {
            return strtolower(trim($h));
        }, $headers);

        $dataType = $options['data_type'] ?? 'sales';

        // Process data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows
            }

            $data = array_combine($headers, $row);

            try {
                if ($dataType === 'sales') {
                    $this->processSalesRow($data, $dataFeed);
                } else {
                    $this->processCostRow($data, $dataFeed);
                }
                $recordCount++;
            } catch (Exception $e) {
                // Log row-level errors but continue processing
                Log::warning("Failed to process row: " . $e->getMessage(), $data);
                continue;
            }
        }

        fclose($handle);
        return $recordCount;
    }

    private function processSalesRow(array $data, DataFeed $dataFeed): void
    {
        // Expected CSV columns: product_name, quantity, price, discount, transaction_date
        $productName = trim($data['product_name'] ?? $data['nama_produk'] ?? '');
        $quantity = floatval($data['quantity'] ?? $data['qty'] ?? $data['kuantitas'] ?? 0);
        $price = floatval($data['price'] ?? $data['harga'] ?? $data['selling_price'] ?? 0);
        $discount = floatval($data['discount'] ?? $data['diskon'] ?? 0);
        $transactionDate = $data['transaction_date'] ?? $data['tanggal'] ?? now();

        if (empty($productName) || $quantity <= 0 || $price <= 0) {
            throw new Exception('Invalid sales data: missing product name, quantity, or price');
        }

        // Try to find existing product
        $product = Product::where('business_id', $dataFeed->business_id)
            ->where('name', 'LIKE', "%$productName%")
            ->first();

        StagingSalesItem::create([
            'data_feed_id' => $dataFeed->id,
            'product_id' => $product?->id,
            'product_name' => $productName,
            'quantity' => $quantity,
            'unit_at_transaction' => $data['unit'] ?? $data['satuan'] ?? 'Pcs',
            'selling_price_at_transaction' => $price,
            'discount_per_item' => $discount,
            'transaction_date' => $transactionDate,
            'notes' => $data['notes'] ?? $data['catatan'] ?? null
        ]);
    }

    private function processCostRow(array $data, DataFeed $dataFeed): void
    {
        // Expected CSV columns: category, description, amount, vendor, invoice_number, cost_date
        $category = trim($data['category'] ?? $data['kategori'] ?? '');
        $description = trim($data['description'] ?? $data['deskripsi'] ?? '');
        $amount = floatval($data['amount'] ?? $data['jumlah'] ?? 0);
        $costDate = $data['cost_date'] ?? $data['tanggal'] ?? now();

        if (empty($category) || empty($description) || $amount <= 0) {
            throw new Exception('Invalid cost data: missing category, description, or amount');
        }

        StagingCost::create([
            'data_feed_id' => $dataFeed->id,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
            'vendor' => $data['vendor'] ?? $data['supplier'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? $data['no_invoice'] ?? null,
            'cost_date' => $costDate
        ]);
    }

    public function createProduct(array $data): Product
    {
        // Add basic validation and business context
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            throw new Exception('User not authenticated');
        }

        $business = $user->primaryBusiness()->first();
        if (!$business) {
            throw new Exception('Business context required');
        }

        $data['business_id'] = $business->id;

        // Set defaults
        $data['unit'] = $data['unit'] ?? 'Pcs';
        $data['selling_price'] = floatval($data['selling_price'] ?? 0);
        $data['cost_price'] = floatval($data['cost_price'] ?? 0);
        $data['card_id'] = $data['card_id'] ?? ('product-card-' . Str::uuid()->toString());

        return Product::create($data);
    }

    public function getFeedsHistoryForDataTable(int $businessId, array $filters = [])
    {
        $query = DataFeed::where('business_id', $businessId)
            ->with('user:id,name');

        // Apply filters if provided
        if (!empty($filters['data_type'])) {
            $query->where('data_type', $filters['data_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()
            ->limit(100) // Reasonable limit for performance
            ->get();
    }

    public function getProductsForBusiness(int $businessId)
    {
        return Product::where('business_id', $businessId)
            ->orderBy('name')
            ->get();
    }

    protected function buildPreviewData(Business $business, string $absolutePath, string $dataType, int $sampleSize): array
    {
        $handle = fopen($absolutePath, 'r');
        if (!$handle) {
            throw new Exception('Tidak dapat membuka file untuk pratinjau.');
        }

        try {
            $rawHeaders = fgetcsv($handle);
            if (!$rawHeaders) {
                throw ValidationException::withMessages([
                    'file' => 'File CSV tampak kosong atau tidak valid.',
                ]);
            }

            $headers = $this->normalizeHeaders($rawHeaders);
            $this->assertRequiredColumns($headers, $dataType);

            $rows = [];
            $totalRows = 0;
            $validRows = 0;
            $invalidRows = 0;
            $newProductCandidates = [];

            $products = $dataType === 'sales' ? $this->getProductsForBusiness($business->id) : collect();

            while (($values = fgetcsv($handle)) !== false) {
                if ($this->rowIsEmpty($values)) {
                    continue;
                }

                if (count($values) < count($headers)) {
                    $values = array_pad($values, count($headers), null);
                }

                $row = array_combine($headers, $values);
                if ($row === false) {
                    continue;
                }

                if ($dataType === 'sales') {
                    $evaluation = $this->evaluateSalesPreviewRow($row, $products);
                    if ($evaluation['product_match']['status'] === 'missing' && $evaluation['normalized']['product_name']) {
                        $newProductCandidates[$evaluation['normalized']['product_name']] = true;
                    }
                } else {
                    $evaluation = $this->evaluateCostPreviewRow($row);
                }

                $totalRows++;
                if ($evaluation['valid']) {
                    $validRows++;
                } else {
                    $invalidRows++;
                }

                if (count($rows) < $sampleSize) {
                    $rows[] = $evaluation;
                }
            }

            return [
                $headers,
                $rows,
                [
                    'total_rows' => $totalRows,
                    'valid_rows' => $validRows,
                    'invalid_rows' => $invalidRows,
                    'new_product_candidates' => array_values(array_keys($newProductCandidates)),
                ],
            ];
        } finally {
            fclose($handle);
        }
    }

    protected function assertRequiredColumns(array $headers, string $dataType): void
    {
        $requirements = [
            'sales' => [
                'product_name' => ['product_name', 'nama_produk', 'produk'],
                'quantity' => ['quantity', 'qty', 'kuantitas', 'jumlah'],
                'price' => ['price', 'harga', 'selling_price', 'harga_satuan'],
                'transaction_date' => ['transaction_date', 'tanggal', 'date'],
            ],
            'costs' => [
                'category' => ['category', 'kategori'],
                'description' => ['description', 'deskripsi'],
                'amount' => ['amount', 'jumlah', 'nilai'],
                'cost_date' => ['cost_date', 'tanggal'],
            ],
        ];

        $map = $requirements[$dataType] ?? $requirements['sales'];

        foreach ($map as $field => $aliases) {
            if (!$this->headerContainsAny($headers, $aliases)) {
                throw ValidationException::withMessages([
                    'file' => "Kolom wajib '{$field}' tidak ditemukan di file Anda.",
                ]);
            }
        }
    }

    protected function headerContainsAny(array $headers, array $aliases): bool
    {
        $normalized = array_map(fn ($alias) => strtolower($alias), $aliases);
        foreach ($headers as $header) {
            if (in_array($header, $normalized, true)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = mb_strtolower(trim((string) $header));
            $header = str_replace(['.', '-', '/', '\\'], '_', $header);
            $header = preg_replace('/\s+/', '_', $header);
            $header = preg_replace('/[^a-z0-9_]/', '', $header);
            return preg_replace('/_+/', '_', $header);
        }, $headers);
    }

    protected function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function evaluateSalesPreviewRow(array $row, Collection $products): array
    {
        $originalProductName = $this->firstValue($row, ['product_name', 'nama_produk', 'produk']);
        $originalQuantity = $this->firstValue($row, ['quantity', 'qty', 'kuantitas', 'jumlah']);
        $originalPrice = $this->firstValue($row, ['price', 'harga', 'selling_price', 'harga_satuan']);
        $originalDiscount = $this->firstValue($row, ['discount', 'diskon', 'discount_per_item']);
        $originalDate = $this->firstValue($row, ['transaction_date', 'tanggal', 'date']);
        $originalUnit = $this->firstValue($row, ['unit', 'satuan']);

        $productName = $this->cleanString($originalProductName);
        $quantity = $this->parseNumber($originalQuantity);
        $price = $this->parseNumber($originalPrice);
        $discount = $this->parseNumber($originalDiscount) ?? 0.0;
        $transactionDate = $this->parseDate($originalDate);
        $unit = $this->cleanString($originalUnit) ?: 'pcs';

        $issues = [];
        if (!$productName) {
            $issues[] = 'missing_product_name';
        }
        if ($quantity === null || $quantity <= 0) {
            $issues[] = 'invalid_quantity';
        }
        if ($price === null || $price < 0) {
            $issues[] = 'invalid_price';
        }
        if ($discount < 0) {
            $issues[] = 'invalid_discount';
        }
        if (!$transactionDate) {
            $issues[] = 'invalid_transaction_date';
        }

        $productMatch = $this->matchProduct($products, $productName);

        return [
            'original' => [
                'product_name' => $originalProductName,
                'quantity' => $originalQuantity,
                'price' => $originalPrice,
                'discount' => $originalDiscount,
                'transaction_date' => $originalDate,
                'unit' => $originalUnit,
            ],
            'normalized' => [
                'product_name' => $productName,
                'quantity' => $quantity !== null ? round($quantity, 3) : null,
                'price' => $price !== null ? round($price, 2) : null,
                'discount' => round($discount, 2),
                'transaction_date' => $transactionDate?->format('Y-m-d'),
                'unit' => $unit,
            ],
            'valid' => empty($issues),
            'issues' => $issues,
            'product_match' => $productMatch,
        ];
    }

    protected function evaluateCostPreviewRow(array $row): array
    {
        $originalCategory = $this->firstValue($row, ['category', 'kategori']);
        $originalDescription = $this->firstValue($row, ['description', 'deskripsi']);
        $originalAmount = $this->firstValue($row, ['amount', 'jumlah', 'nilai']);
        $originalDate = $this->firstValue($row, ['cost_date', 'tanggal']);
        $originalVendor = $this->firstValue($row, ['vendor', 'supplier']);

        $category = $this->cleanString($originalCategory);
        $description = $this->cleanString($originalDescription);
        $amount = $this->parseNumber($originalAmount);
        $costDate = $this->parseDate($originalDate);

        $issues = [];
        if (!$category) {
            $issues[] = 'missing_category';
        }
        if (!$description) {
            $issues[] = 'missing_description';
        }
        if ($amount === null || $amount <= 0) {
            $issues[] = 'invalid_amount';
        }
        if (!$costDate) {
            $issues[] = 'invalid_cost_date';
        }

        return [
            'original' => [
                'category' => $originalCategory,
                'description' => $originalDescription,
                'amount' => $originalAmount,
                'cost_date' => $originalDate,
                'vendor' => $originalVendor,
            ],
            'normalized' => [
                'category' => $category,
                'description' => $description,
                'amount' => $amount !== null ? round($amount, 2) : null,
                'cost_date' => $costDate?->format('Y-m-d'),
                'vendor' => $this->cleanString($originalVendor),
            ],
            'valid' => empty($issues),
            'issues' => $issues,
            'product_match' => null,
        ];
    }

    protected function firstValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return (string) $row[$key];
            }
        }

        return null;
    }

    protected function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    protected function parseNumber(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = str_replace(["\xc2\xa0", ' '], '', trim($value));
        $clean = str_ireplace(['rp', 'idr'], '', $clean);

        if ($clean === '') {
            return null;
        }

        $clean = str_replace(['[', ']', ',00'], ['', '', ''], $clean);

        $commaCount = substr_count($clean, ',');
        $dotCount = substr_count($clean, '.');

        if ($commaCount > 0 && $dotCount > 0) {
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($commaCount > 0 && $dotCount === 0) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Exception $e) {
            return null;
        }
    }

    protected function matchProduct(Collection $products, ?string $productName): array
    {
        $fuzzyThreshold = (int) config('data_feeds.preview.product_match.fuzzy_threshold', 70);
        $exactThreshold = (int) config('data_feeds.preview.product_match.exact_threshold', 90);
        $maxSuggestions = (int) config('data_feeds.preview.product_match.max_suggestions', 3);

        if (!$productName) {
            return [
                'status' => 'missing',
                'product_id' => null,
                'product_name' => null,
                'confidence' => 0,
                'suggestions' => [],
            ];
        }

        $normalized = mb_strtolower($productName);
        foreach ($products as $product) {
            if (mb_strtolower($product->name) === $normalized) {
                return [
                    'status' => 'exact',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'confidence' => 100,
                    'suggestions' => [],
                ];
            }
        }

        $best = null;
        $suggestions = [];

        foreach ($products as $product) {
            similar_text($normalized, mb_strtolower($product->name), $percent);
            if ($percent >= $fuzzyThreshold) {
                $suggestions[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'confidence' => round($percent, 1),
                ];
            }

            if (!$best || $percent > $best['confidence']) {
                $best = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'confidence' => round($percent, 1),
                ];
            }
        }

        usort($suggestions, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
        $suggestions = array_slice($suggestions, 0, $maxSuggestions);

        if ($best && $best['confidence'] >= $exactThreshold) {
            return [
                'status' => 'fuzzy',
                'product_id' => $best['product_id'],
                'product_name' => $best['product_name'],
                'confidence' => $best['confidence'],
                'suggestions' => $suggestions,
            ];
        }

        return [
            'status' => 'missing',
            'product_id' => null,
            'product_name' => null,
            'confidence' => $best['confidence'] ?? 0,
            'suggestions' => $suggestions,
        ];
    }

    protected function previewCacheKey(string $token): string
    {
        return 'data-feed-preview:' . $token;
    }

    protected function commitUniversalPreview(Business $business, array $cached, array $options): array
    {
        $storagePath = $cached['path'];
        $absolutePath = Storage::path($storagePath);
        $autoCreateProducts = (bool) ($options['auto_create_products'] ?? false);

        DB::beginTransaction();

        try {
            // Create DataFeed record
            $dataFeed = DataFeed::create([
                'business_id' => $business->id,
                'user_id' => Auth::id(),
                'source' => 'csv_upload',
                'data_type' => 'universal',
                'original_name' => $cached['original_name'] ?? 'universal_data.csv',
                'status' => 'processing',
                'record_count' => 0
            ]);

            $handle = fopen($absolutePath, 'r');
            if (!$handle) {
                throw new DataFeedCommitException('Cannot open file for processing', 500);
            }

            // Skip header row
            fgetcsv($handle);

            $processedCount = 0;
            $customers = [];
            $products = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 19) continue; // Skip incomplete rows

                $data = [
                    'transaction_date' => $row[0],
                    'customer_name' => $row[1],
                    'customer_email' => $row[2],
                    'customer_phone' => $row[3],
                    'product_name' => $row[4],
                    'product_category' => $row[5],
                    'quantity' => floatval($row[6]),
                    'unit' => $row[7],
                    'selling_price' => floatval($row[8]),
                    'discount' => floatval($row[9]),
                    'tax_amount' => floatval($row[10]),
                    'shipping_cost' => floatval($row[11]),
                    'payment_method' => $row[12],
                    'notes' => $row[13],
                    'product_cost_price' => floatval($row[14]),
                    'material_name' => $row[15],
                    'material_quantity' => floatval($row[16]),
                    'material_unit' => $row[17],
                    'material_cost_per_unit' => floatval($row[18])
                ];

                // Skip empty rows
                if (empty($data['product_name']) || $data['quantity'] <= 0) {
                    continue;
                }

                // Handle customer (allow creation even if email missing; fallback to name key)
                $customerKey = strtolower(trim($data['customer_name'] ?: 'ANON'));
                $customerId = null;
                if (!isset($customers[$customerKey]) && !empty($data['customer_name'])) {
                    try {
                        $customer = $this->findOrCreateCustomer($business, $data);
                        $customers[$customerKey] = $customer;
                        $customerId = $customer->id ?? null;
                    } catch (\Exception $e) {
                        Log::warning('Failed to create customer: ' . $e->getMessage(), $data);
                        $customers[$customerKey] = null; // Mark as attempted but failed
                    }
                } elseif (isset($customers[$customerKey])) {
                    $customerId = $customers[$customerKey]->id ?? null;
                }

                // Handle product
                $productKey = strtolower(trim($data['product_name']));
                if (!isset($products[$productKey])) {
                    $product = Product::where('business_id', $business->id)
                        ->where('name', 'LIKE', '%' . $data['product_name'] . '%')
                        ->first();

                    if (!$product && $autoCreateProducts) {
                        $product = $this->createProductFromUniversalData($business, $data);
                    }

                    if ($product) {
                        $products[$productKey] = $product;
                    }
                }

                // Create staging record if product exists
                if (isset($products[$productKey])) {
                    $product = $products[$productKey];

                    $stagingData = [
                        'data_feed_id' => $dataFeed->id,
                        'product_id' => $product->id,
                        'customer_id' => $customerId,
                        'product_name' => $data['product_name'],
                        'quantity' => $data['quantity'],
                        'unit_at_transaction' => $data['unit'],
                        'selling_price_at_transaction' => $data['selling_price'],
                        'discount_per_item' => $data['discount'],
                        'tax_amount' => $data['tax_amount'],
                        'shipping_cost' => $data['shipping_cost'],
                        'payment_method' => $data['payment_method'],
                        'transaction_date' => Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                        'notes' => $data['notes']
                    ];

                    StagingSalesItem::create($stagingData);

                    // Create Bill of Material if material data exists
                    if (!empty($data['material_name']) && $data['material_quantity'] > 0 && $data['material_cost_per_unit'] > 0) {
                        // Check if BillOfMaterial already exists for this product and material
                        $existingBom = \App\Models\BillOfMaterial::where('product_id', $product->id)
                            ->where('material_name', $data['material_name'])
                            ->first();

                        if (!$existingBom) {
                            \App\Models\BillOfMaterial::create([
                                'product_id' => $product->id,
                                'material_name' => $data['material_name'],
                                'quantity' => $data['material_quantity'],
                                'unit' => $data['material_unit'] ?: 'Kg',
                                'cost_per_unit' => $data['material_cost_per_unit'],
                                'notes' => 'Auto-created from universal CSV import'
                            ]);
                        }
                    }

                    // Create Sales Transaction and Transaction Item
                    if ($customerId) {
                        $transactionDate = Carbon::parse($data['transaction_date']);

                        // Check if transaction already exists for this customer and date
                        $salesTransaction = \App\Models\SalesTransaction::where('customer_id', $customerId)
                            ->whereDate('transaction_date', $transactionDate->format('Y-m-d'))
                            ->first();

                        if (!$salesTransaction) {
                            // Initial calculation for first item
                            $itemSubtotal = $data['quantity'] * $data['selling_price'] - $data['discount'];
                            $totalAmount = $itemSubtotal + $data['tax_amount'] + $data['shipping_cost'];

                            $salesTransaction = \App\Models\SalesTransaction::create([
                                'business_id' => $business->id,
                                'data_feed_id' => $dataFeed->id,
                                'customer_id' => $customerId,
                                'transaction_date' => $transactionDate,
                                'subtotal' => $itemSubtotal,
                                'tax_amount' => $data['tax_amount'],
                                'shipping_cost' => $data['shipping_cost'],
                                'total_amount' => $totalAmount,
                                'status' => 'completed',
                                'notes' => $data['notes']
                            ]);
                        } else {
                            // Update existing transaction aggregates (accumulate)
                            $additionalSubtotal = $data['quantity'] * $data['selling_price'] - $data['discount'];
                            $salesTransaction->subtotal += $additionalSubtotal;
                            // For now tax & shipping add line values (assuming per-line provided); adjust if header-level
                            $salesTransaction->tax_amount += $data['tax_amount'];
                            $salesTransaction->shipping_cost += $data['shipping_cost'];
                            $salesTransaction->total_amount = $salesTransaction->subtotal + $salesTransaction->tax_amount + $salesTransaction->shipping_cost;
                            // Ensure linkage to feed (in case pre-existing without)
                            if (!$salesTransaction->data_feed_id) {
                                $salesTransaction->data_feed_id = $dataFeed->id;
                            }
                            $salesTransaction->save();
                        }

                        // Create transaction item
                        $itemSubtotal = $data['quantity'] * $data['selling_price'] - $data['discount'];

                        \App\Models\SalesTransactionItem::create([
                            'sales_transaction_id' => $salesTransaction->id,
                            'product_id' => $product->id,
                            'product_name' => $data['product_name'],
                            'quantity' => $data['quantity'],
                            'selling_price' => $data['selling_price'],
                            'discount' => $data['discount'],
                            'subtotal' => $itemSubtotal
                        ]);
                    }

                    $processedCount++;
                }
            }

            fclose($handle);

            // Update DataFeed record
            $dataFeed->update([
                'record_count' => $processedCount,
                'status' => $processedCount > 0 ? 'completed' : 'failed',
                'log_message' => $processedCount > 0
                    ? "Successfully processed $processedCount universal data rows"
                    : 'No valid data rows found'
            ]);

            // Queue processing job if successful
            if ($processedCount > 0) {
                \App\Jobs\ProcessDataFeedJob::dispatch($dataFeed->id);
            }

            DB::commit();

            // Clean up cache and file
            Cache::forget($this->previewCacheKey($cached['upload_token'] ?? ''));
            if (Storage::exists($storagePath)) {
                Storage::delete($storagePath);
            }

            return [
                'status' => 'success',
                'message' => "Successfully queued $processedCount universal data rows for processing",
                'record_count' => $processedCount,
                'data_feed_id' => $dataFeed->id
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Universal CSV commit failed: ' . $e->getMessage());
            throw new DataFeedCommitException('Failed to process universal CSV data: ' . $e->getMessage(), 500);
        }
    }

    protected function findOrCreateCustomer(Business $business, array $data)
    {
        // Check if Customer model exists, if not, return dummy object
        if (!class_exists('App\\Models\\Customer')) {
            return (object) [
                'id' => null,
                'customer_name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone']
            ];
        }

        try {
            // Use email if available, otherwise use name as unique key
            $searchCriteria = !empty($data['customer_email'])
                ? ['business_id' => $business->id, 'email' => $data['customer_email']]
                : ['business_id' => $business->id, 'customer_name' => $data['customer_name']];

            return \App\Models\Customer::firstOrCreate(
                $searchCriteria,
                [
                    'customer_name' => $data['customer_name'],
                    'phone' => $data['customer_phone'] ?: null,
                    'email' => $data['customer_email'] ?: null,
                    'first_purchase_date' => isset($data['transaction_date'])
                        ? \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d')
                        : now()->format('Y-m-d'),
                    'total_purchases' => 1,
                    'total_spent' => 0,
                    'customer_type' => 'new'
                ]
            );
        } catch (\Exception $e) {
            // Log error but continue without customer
            Log::warning('Customer creation failed, continuing without customer', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return (object) [
                'id' => null,
                'customer_name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone']
            ];
        }
    }

    protected function createProductFromUniversalData(Business $business, array $data): Product
    {
        return Product::create([
            'business_id' => $business->id,
            'card_id' => 'product-card-' . Str::uuid(),
            'name' => $data['product_name'],
            'category' => $data['product_category'] ?: 'Lainnya',
            'selling_price' => $data['selling_price'],
            'cost_price' => $data['product_cost_price'],
            'unit' => $data['unit'] ?: 'Pcs',
            'description' => 'Auto-created from universal CSV import'
        ]);
    }
}
