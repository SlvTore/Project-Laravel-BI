<?php

namespace App\Services;

use App\Models\DataFeed;
use App\Models\Product;
use App\Models\StagingCost;
use App\Models\StagingSalesItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

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

            // Create DataFeed record
            $dataFeed = DataFeed::create([
                'business_id' => auth()->user()->primaryBusiness()->first()->id,
                'user_id' => auth()->id(),
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

    public function processUploadedFile(UploadedFile $file, array $options = [])
    {
        try {
            DB::beginTransaction();

            $business = auth()->user()->primaryBusiness()->first();
            if (!$business) {
                throw new Exception('Business context not found');
            }

            // Create DataFeed record
            $dataFeed = DataFeed::create([
                'business_id' => $business->id,
                'user_id' => auth()->id(),
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
        $business = auth()->user()->primaryBusiness()->first();
        if (!$business) {
            throw new Exception('Business context required');
        }

        $data['business_id'] = $business->id;

        // Set defaults
        $data['unit'] = $data['unit'] ?? 'Pcs';
        $data['selling_price'] = floatval($data['selling_price'] ?? 0);
        $data['cost_price'] = floatval($data['cost_price'] ?? 0);

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
}
