<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DataFeed;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\StagingSalesItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function loadFactsFromTransactions(int $businessId): array
    {
        $txs = SalesTransaction::with(['items', 'customer'])
            ->where('business_id', $businessId)
            ->get();

        if ($txs->isEmpty()) {
            return [
                'records' => 0,
                'gross_revenue' => 0,
                'cogs_amount' => 0,
                'gross_margin_amount' => 0,
            ];
        }

        $productCostMap = $this->buildProductCostMap(
            $txs->flatMap(fn($tx) => $tx->items->pluck('product_id'))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );

        $summary = [
            'records' => 0,
            'gross_revenue' => 0.0,
            'cogs_amount' => 0.0,
            'gross_margin_amount' => 0.0,
        ];

        foreach ($txs as $tx) {
            $dateId = $this->ensureDateDim(optional($tx->transaction_date)->toDateString());
            $customerDimId = $this->ensureCustomerDim($businessId, $tx->customer, null);

            foreach ($tx->items as $item) {
                $product = $this->resolveProductModelFromMap($productCostMap, $item->product_id);
                $productDimId = $this->ensureProductDim($businessId, $product, $item->product_name);

                $metrics = $this->calculateRowMetrics(
                    (float) $item->quantity,
                    (float) $item->selling_price,
                    (float) ($item->discount ?? 0),
                    $this->resolveUnitCostFromMap($productCostMap, $item->product_id)
                );

                DB::table('fact_sales')->insert([
                    'business_id' => $businessId,
                    'date_id' => $dateId,
                    'product_id' => $productDimId,
                    'customer_id' => $customerDimId,
                    'channel_id' => null,
                    'sales_transaction_id' => $tx->id,
                    'sales_transaction_item_id' => $item->id,
                    'quantity' => $metrics['quantity'],
                    'unit_price' => $metrics['unit_price'],
                    'discount' => $metrics['discount'],
                    'subtotal' => $metrics['subtotal'],
                    'tax_amount' => $tx->tax_amount ?? 0,
                    'shipping_cost' => $tx->shipping_cost ?? 0,
                    'total_amount' => $metrics['total_amount'],
                    'gross_revenue' => $metrics['gross_revenue'],
                    'cogs_amount' => $metrics['cogs_amount'],
                    'gross_margin_amount' => $metrics['gross_margin_amount'],
                    'gross_margin_percent' => $metrics['gross_margin_percent'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $summary['records']++;
                $summary['gross_revenue'] += $metrics['gross_revenue'];
                $summary['cogs_amount'] += $metrics['cogs_amount'];
                $summary['gross_margin_amount'] += $metrics['gross_margin_amount'];
            }
        }

        return $this->finalizeSummary($summary);
    }

    public function loadFactsFromStaging(DataFeed $feed): array
    {
        $items = StagingSalesItem::where('data_feed_id', $feed->id)->get();

        if ($items->isEmpty()) {
            return [
                'records' => 0,
                'gross_revenue' => 0,
                'cogs_amount' => 0,
                'gross_margin_amount' => 0,
            ];
        }

    $productCostMap = $this->buildProductCostMap(
        $items->pluck('product_id')->filter()->unique()->values()->all()
    );

    $summary = [
        'records' => 0,
        'gross_revenue' => 0.0,
        'cogs_amount' => 0.0,
        'gross_margin_amount' => 0.0,
    ];

        foreach ($items as $s) {
            $dateId = $this->ensureDateDim(\Carbon\Carbon::parse($s->transaction_date)->toDateString());
            $product = $this->resolveProductModelFromMap($productCostMap, $s->product_id);
            $productDimId = $this->ensureProductDim($feed->business_id, $product, $s->product_name, $s->unit_at_transaction);

            // Handle customer dimension
            $customerDimId = null;
            if ($s->customer_id) {
                $customer = Customer::find($s->customer_id);
                $customerDimId = $this->ensureCustomerDim($feed->business_id, $customer, $customer->customer_name ?? 'Unknown Customer');
            }

            $metrics = $this->calculateRowMetrics(
                (float) $s->quantity,
                (float) $s->selling_price_at_transaction,
                (float) ($s->discount_per_item ?? 0),
                $this->resolveUnitCostFromMap($productCostMap, $s->product_id)
            );

            DB::table('fact_sales')->insert([
                'business_id' => $feed->business_id,
                'date_id' => $dateId,
                'product_id' => $productDimId,
                'customer_id' => $customerDimId,
                'channel_id' => null,
                'sales_transaction_id' => null,
                'sales_transaction_item_id' => null,
                'quantity' => $metrics['quantity'],
                'unit_price' => $metrics['unit_price'],
                'discount' => $metrics['discount'],
                'subtotal' => $metrics['subtotal'],
                'tax_amount' => (float) ($s->tax_amount ?? 0),
                'shipping_cost' => (float) ($s->shipping_cost ?? 0),
                'total_amount' => $metrics['total_amount'] + (float) ($s->tax_amount ?? 0) + (float) ($s->shipping_cost ?? 0),
                'gross_revenue' => $metrics['gross_revenue'],
                'cogs_amount' => $metrics['cogs_amount'],
                'gross_margin_amount' => $metrics['gross_margin_amount'],
                'gross_margin_percent' => $metrics['gross_margin_percent'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $summary['records']++;
            $summary['gross_revenue'] += $metrics['gross_revenue'];
            $summary['cogs_amount'] += $metrics['cogs_amount'];
            $summary['gross_margin_amount'] += $metrics['gross_margin_amount'];
        }

        StagingSalesItem::where('data_feed_id', $feed->id)->delete();

        return $this->finalizeSummary($summary);
    }

    /**
     * @param array<int, array{model: Product, unit_cost: float}> $costMap
     */
    protected function resolveUnitCostFromMap(array $costMap, ?int $productId): float
    {
        if (!$productId || !isset($costMap[$productId])) {
            return 0.0;
        }

        return (float) $costMap[$productId]['unit_cost'];
    }

    /**
     * @param array<int, array{model: Product, unit_cost: float}> $costMap
     */
    protected function resolveProductModelFromMap(array $costMap, ?int $productId): ?Product
    {
        if (!$productId || !isset($costMap[$productId])) {
            return null;
        }

        return $costMap[$productId]['model'];
    }

    /**
     * @param array $productIds
     * @return array<int, array{model: Product, unit_cost: float}>
     */
    protected function buildProductCostMap(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        /** @var Collection<int, Product> $products */
        $products = Product::with(['activeProductionCosts'])->whereIn('id', $productIds)->get()->keyBy('id');

        $map = [];
        foreach ($products as $id => $product) {
            $productionUnitCost = $product->activeProductionCosts->reduce(function ($carry, $cost) {
                $quantity = (float) ($cost->unit_quantity ?: 0);
                if ($quantity <= 0) {
                    return $carry + (float) $cost->amount;
                }

                return $carry + ((float) $cost->amount / max($quantity, 1));
            }, 0.0);

            $map[$id] = [
                'model' => $product,
                'unit_cost' => (float) $product->cost_price + (float) $productionUnitCost,
            ];
        }

        return $map;
    }

    /**
     * @return array{
     *     quantity: float,
     *     unit_price: float,
     *     discount: float,
     *     subtotal: float,
     *     total_amount: float,
     *     gross_revenue: float,
     *     cogs_amount: float,
     *     gross_margin_amount: float,
     *     gross_margin_percent: float
     * }
     */
    protected function calculateRowMetrics(float $quantity, float $unitPrice, float $discount, float $unitCost): array
    {
        $grossRevenue = $quantity * $unitPrice;
        $cogsAmount = $quantity * $unitCost;
        $netRevenue = $grossRevenue - $discount;
        $grossMarginAmount = $grossRevenue - $discount - $cogsAmount;
        $grossMarginPercent = $grossRevenue <= 0 ? 0 : ($grossMarginAmount / $grossRevenue) * 100;

        return [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'subtotal' => $netRevenue,
            'total_amount' => $netRevenue,
            'gross_revenue' => $grossRevenue,
            'cogs_amount' => $cogsAmount,
            'gross_margin_amount' => $grossMarginAmount,
            'gross_margin_percent' => $grossMarginPercent,
        ];
    }

    /**
     * @param array{records:int,gross_revenue:float,cogs_amount:float,gross_margin_amount:float} $summary
     */
    protected function finalizeSummary(array $summary): array
    {
        $grossRevenue = $summary['gross_revenue'] ?? 0;
        $margin = $summary['gross_margin_amount'] ?? 0;

        $summary['gross_margin_percent'] = $grossRevenue <= 0 ? 0 : ($margin / max($grossRevenue, 0.00001)) * 100;

        return $summary;
    }
}
