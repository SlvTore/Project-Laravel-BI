<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesTransactionController extends Controller
{
    /**
     * Get sales transactions with filters, sorting, and pagination
     */
    public function index(Request $request)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();

            $perPage = (int)($request->input('per_page', 10));
            $page = (int)($request->input('page', 1));
            $search = trim((string)$request->input('search', ''));
            $status = $request->input('status');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $sortBy = $request->input('sort_by', 'transaction_date');
            $dataFeedId = $request->input('data_feed_id');
            $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            $query = SalesTransaction::with(['customer', 'items'])
                ->where('business_id', $business->id);

            if (!empty($dataFeedId)) {
                $query->where('data_feed_id', $dataFeedId);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($qc) use ($search) {
                        $qc->where('customer_name', 'like', "%{$search}%");
                    })
                    ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($startDate)) {
                $query->whereDate('transaction_date', '>=', $startDate);
            }
            if (!empty($endDate)) {
                $query->whereDate('transaction_date', '<=', $endDate);
            }

            $sortable = ['transaction_date', 'total_amount'];
            if (!in_array($sortBy, $sortable)) {
                $sortBy = 'transaction_date';
            }
            $query->orderBy($sortBy, $sortDir);

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $transactions = collect($paginator->items())->map(function ($transaction) {
                $itemsCount = $transaction->items->count();
                $itemsSummary = $transaction->items->pluck('product_name')->take(2)->implode(', ');
                if ($itemsCount > 2) {
                    $itemsSummary .= ' +' . ($itemsCount - 2) . ' lainnya';
                }
                return [
                    'id' => $transaction->id,
                    'transaction_date' => optional($transaction->transaction_date)->format('d/m/Y H:i'),
                    'transaction_date_iso' => optional($transaction->transaction_date)->toDateTimeString(),
                    'customer_name' => optional($transaction->customer)->customer_name,
                    'items_count' => $itemsCount,
                    'items_summary' => $itemsSummary,
                    'total_amount' => (float)$transaction->total_amount,
                    'formatted_total' => 'Rp ' . number_format($transaction->total_amount, 0, ',', '.'),
                    'status' => $transaction->status ?? 'pending',
                    'notes' => $transaction->notes,
                ];
            });

            // stats
            $today = now()->toDateString();
            $weekStart = now()->startOfWeek()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();

            $dailySales = SalesTransaction::where('business_id', $business->id)
                ->whereDate('transaction_date', $today)
                ->sum('total_amount');
            $weeklySales = SalesTransaction::where('business_id', $business->id)
                ->whereDate('transaction_date', '>=', $weekStart)
                ->sum('total_amount');
            $monthlySales = SalesTransaction::where('business_id', $business->id)
                ->whereDate('transaction_date', '>=', $monthStart)
                ->sum('total_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'pagination' => [
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                    ],
                    'statistics' => [
                        'daily_sales' => (float)$dailySales,
                        'weekly_sales' => (float)$weeklySales,
                        'monthly_sales' => (float)$monthlySales,
                        'formatted_daily' => 'Rp ' . number_format($dailySales, 0, ',', '.'),
                        'formatted_weekly' => 'Rp ' . number_format($weeklySales, 0, ',', '.'),
                        'formatted_monthly' => 'Rp ' . number_format($monthlySales, 0, ',', '.'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading sales transactions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update transaction status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:completed,pending,review',
        ]);

        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $transaction = SalesTransaction::where('business_id', $business->id)->findOrFail($id);
            $transaction->status = $request->input('status');
            $transaction->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a sales transaction
     */
    public function destroy(Request $request, $id)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $transaction = SalesTransaction::with('items')
                ->where('business_id', $business->id)
                ->findOrFail($id);

            DB::transaction(function () use ($transaction) {
                $transaction->items()->delete();
                $transaction->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting transaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a transaction detail with items
     */
    public function show(Request $request, $id)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $transaction = SalesTransaction::with(['customer', 'items'])
                ->where('business_id', $business->id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'transaction_date' => optional($transaction->transaction_date)->toDateTimeLocalString(),
                    'customer' => [
                        'id' => optional($transaction->customer)->id,
                        'name' => optional($transaction->customer)->customer_name,
                        'phone' => optional($transaction->customer)->phone,
                    ],
                    'items' => $transaction->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'quantity' => (float)$item->quantity,
                            'selling_price' => (float)$item->selling_price,
                            'discount' => (float)($item->discount ?? 0),
                            'subtotal' => (float)$item->subtotal,
                        ];
                    }),
                    'tax_amount' => (float)($transaction->tax_amount ?? 0),
                    'shipping_cost' => (float)($transaction->shipping_cost ?? 0),
                    'notes' => $transaction->notes,
                    'status' => $transaction->status,
                    'total_amount' => (float)$transaction->total_amount,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Sales transaction not found: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching transaction detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transaction detail: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing transaction
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'customer_name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:completed,pending,review',
        ]);

        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();

            DB::transaction(function () use ($request, $business, $id) {
                $transaction = SalesTransaction::where('business_id', $business->id)->findOrFail($id);

                // Ensure customer exists/created
                $customer = Customer::firstOrCreate([
                    'customer_name' => $request->customer_name,
                    'business_id' => $business->id,
                ], [
                    'phone' => $request->customer_phone,
                ]);

                // Update header
                $transaction->customer_id = $customer->id;
                $transaction->transaction_date = $request->transaction_date;
                $transaction->tax_amount = $request->tax_amount ?? 0;
                $transaction->shipping_cost = $request->shipping_cost ?? 0;
                $transaction->notes = $request->notes;
                if ($request->filled('status')) {
                    $transaction->status = $request->status;
                }
                $transaction->save();

                // Rebuild items
                $transaction->items()->delete();

                $subtotal = 0;
                foreach ($request->items as $item) {
                    $qty = (float)$item['quantity'];
                    $price = (float)$item['selling_price'];
                    $disc = (float)($item['discount'] ?? 0);
                    $lineSubtotal = ($qty * $price) - $disc;
                    $subtotal += $lineSubtotal;

                    SalesTransactionItem::create([
                        'sales_transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'quantity' => $qty,
                        'selling_price' => $price,
                        'discount' => $disc,
                        'subtotal' => $lineSubtotal,
                    ]);
                }

                $total = $subtotal + ($transaction->tax_amount ?? 0) + ($transaction->shipping_cost ?? 0);
                $transaction->subtotal = $subtotal;
                $transaction->total_amount = $total;
                $transaction->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi diperbarui',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating transaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new sales transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'customer_name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();

            DB::beginTransaction();

            // Debug log
            Log::info('Creating sales transaction', [
                'customer_name' => $request->customer_name,
                'business_id' => $business->id,
                'items_count' => count($request->items ?? [])
            ]);

            // Find or create customer
            $customer = Customer::firstOrCreate([
                'customer_name' => $request->customer_name,
                'business_id' => $business->id,
            ], [
                'phone' => $request->customer_phone,
                'email' => $request->customer_email,
                'first_purchase_date' => now()->toDateString(),
            ]);

            Log::info('Customer found/created', ['customer_id' => $customer->id]);

            // Calculate totals
            $itemsTotal = 0;
            $items = [];

            foreach ($request->items as $itemData) {
                $product = Product::where('business_id', $business->id)
                    ->where('name', $itemData['product_name'])
                    ->first();

                if (!$product) {
                    // Create product if it doesn't exist
                    $product = Product::create([
                        'business_id' => $business->id,
                        'name' => $itemData['product_name'],
                        'selling_price' => $itemData['selling_price'],
                        'category' => 'General',
                        'unit' => 'pcs',
                    ]);
                }

                $quantity = (float) $itemData['quantity'];
                $price = (float) $itemData['selling_price'];
                $discount = (float) ($itemData['discount'] ?? 0);
                $subtotal = ($quantity * $price) - $discount;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'selling_price' => $price,
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                ];

                $itemsTotal += $subtotal;
            }

            $taxAmount = (float) ($request->tax_amount ?? 0);
            $shippingCost = (float) ($request->shipping_cost ?? 0);
            $totalAmount = $itemsTotal + $taxAmount + $shippingCost;

            // Create sales transaction
            $transaction = SalesTransaction::create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'transaction_date' => $request->transaction_date,
                'subtotal' => $itemsTotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'notes' => $request->notes,
            ]);

            // Create transaction items
            foreach ($items as $item) {
                SalesTransactionItem::create([
                    'sales_transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'selling_price' => $item['selling_price'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction saved successfully',
                'transaction_id' => $transaction->id,
                'total_amount' => $totalAmount,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Sales transaction error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error saving transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download sales template
     */
    public function downloadTemplate(Request $request)
    {
        $format = $request->get('format', 'csv');

        try {
            $headers = [
                'Date',
                'Customer Name',
                'Product Name',
                'Quantity',
                'Price',
                'Discount',
                'Tax',
                'Shipping Cost',
                'Notes'
            ];

            $sampleData = [
                '2025-09-23 10:00:00',
                'John Doe',
                'Sample Product',
                '2',
                '50000',
                '0',
                '0',
                '0',
                'Sample transaction'
            ];

            $filename = 'sales_template_' . date('Y-m-d') . '.csv';

            $handle = fopen('php://output', 'w');

            return response()->stream(function() use ($handle, $headers, $sampleData) {
                fputcsv($handle, $headers);
                fputcsv($handle, $sampleData);
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data (placeholder)
     */
    public function previewImport(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Import functionality akan segera tersedia. Silakan gunakan input manual untuk sementara.'
        ], 501);
    }

    /**
     * Process import data (placeholder)
     */
    public function processImport(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Import functionality akan segera tersedia. Silakan gunakan input manual untuk sementara.'
        ], 501);
    }
}
