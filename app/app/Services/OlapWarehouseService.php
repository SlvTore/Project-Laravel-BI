<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\DataFeed;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\StagingSalesItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OlapWarehouseService
{
    public function ensureDateDim(string $date): int
    {
        $row = DB::table('dim_date')->where('date', $date)->first();
        if ($row) return (int)$row->id;

        $dt = \Carbon\Carbon::parse($date);
        return (int) DB::table('dim_date')->insertGetId([
            'date' => $dt->toDateString(),
            'day' => (int)$dt->day,
            'month' => (int)$dt->month,
            'year' => (int)$dt->year,
            'quarter' => (int)$dt->quarter,
            'month_name' => $dt->format('F'),
            'day_name' => $dt->format('l'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function ensureProductDim(int $businessId, ?Product $product, string $fallbackName, ?string $unit = null, ?string $category = null): int
    {
        $nk = $product?->id;
        $existing = DB::table('dim_product')
            ->where('business_id', $businessId)
            ->when($nk, fn($q) => $q->where('product_nk', $nk))
            ->where('name', $product?->name ?? $fallbackName)
            ->first();
        if ($existing) return (int)$existing->id;

        return (int) DB::table('dim_product')->insertGetId([
            'business_id' => $businessId,
            'product_nk' => $nk,
            'name' => $product?->name ?? $fallbackName,
            'category' => $category ?? $product?->category,
            'unit' => $unit ?? $product?->unit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function ensureCustomerDim(int $businessId, ?Customer $customer, ?string $fallbackName): ?int
    {
        if (!$customer && !$fallbackName) return null;

        $nk = $customer?->id;
        $existing = DB::table('dim_customer')
            ->where('business_id', $businessId)
            ->when($nk, fn($q) => $q->where('customer_nk', $nk))
            ->where('name', $customer?->customer_name ?? $fallbackName)
            ->first();
        if ($existing) return (int)$existing->id;

        return (int) DB::table('dim_customer')->insertGetId([
            'business_id' => $businessId,
            'customer_nk' => $nk,
            'name' => $customer?->customer_name ?? $fallbackName,
            'phone' => $customer?->phone,
            'email' => $customer?->email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function loadFactsFromTransactions(int $businessId): int
    {
        $count = 0;

        $txs = SalesTransaction::with(['items', 'customer'])
            ->where('business_id', $businessId)
            ->get();

        foreach ($txs as $tx) {
            $dateId = $this->ensureDateDim(optional($tx->transaction_date)->toDateString());
            $customerDimId = $this->ensureCustomerDim($businessId, $tx->customer, null);

            foreach ($tx->items as $item) {
                $product = $item->product_id ? Product::find($item->product_id) : null;
                $productDimId = $this->ensureProductDim($businessId, $product, $item->product_name);

                DB::table('fact_sales')->insert([
                    'business_id' => $businessId,
                    'date_id' => $dateId,
                    'product_id' => $productDimId,
                    'customer_id' => $customerDimId,
                    'channel_id' => null,
                    'sales_transaction_id' => $tx->id,
                    'sales_transaction_item_id' => $item->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->selling_price,
                    'discount' => $item->discount ?? 0,
                    'subtotal' => $item->subtotal,
                    'tax_amount' => $tx->tax_amount ?? 0,
                    'shipping_cost' => $tx->shipping_cost ?? 0,
                    'total_amount' => $tx->total_amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function loadFactsFromStaging(DataFeed $feed): int
    {
        $count = 0;

        $items = StagingSalesItem::where('data_feed_id', $feed->id)->get();
        foreach ($items as $s) {
            $dateId = $this->ensureDateDim(\Carbon\Carbon::parse($s->transaction_date)->toDateString());
            $product = $s->product_id ? Product::find($s->product_id) : null;
            $productDimId = $this->ensureProductDim($feed->business_id, $product, $s->product_name, $s->unit_at_transaction);

            DB::table('fact_sales')->insert([
                'business_id' => $feed->business_id,
                'date_id' => $dateId,
                'product_id' => $productDimId,
                'customer_id' => null,
                'channel_id' => null,
                'sales_transaction_id' => null,
                'sales_transaction_item_id' => null,
                'quantity' => $s->quantity,
                'unit_price' => $s->selling_price_at_transaction,
                'discount' => $s->discount_per_item ?? 0,
                'subtotal' => ($s->quantity * $s->selling_price_at_transaction) - ($s->discount_per_item ?? 0),
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => ($s->quantity * $s->selling_price_at_transaction) - ($s->discount_per_item ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }
}
