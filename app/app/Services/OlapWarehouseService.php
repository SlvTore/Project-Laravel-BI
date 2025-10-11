<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DataFeed;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\StagingSalesItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Olap\OlapDimensionService;
use App\Services\Olap\OlapFactService;
use App\Services\Olap\OlapETLService;

/**
 * Orchestrates OLAP warehouse operations
 * Delegates to specialized services for better maintainability
 *
 * @deprecated Consider using specialized services directly:
 * - OlapDimensionService for dimension management
 * - OlapFactService for fact operations
 * - OlapETLService for staging transformations
 */
class OlapWarehouseService
{
    public function __construct(
        private OlapDimensionService $dimensionService,
        private OlapFactService $factService,
        private OlapETLService $etlService
    ) {}

    /**
     * @deprecated Use OlapDimensionService::getOrCreateDateDimension()
     */
    public function ensureDateDim(string $date): int
    {
        return $this->dimensionService->getOrCreateDateDimension($date)->id;
    }

    /**
     * @deprecated Use OlapDimensionService::getOrCreateProductDimension()
     */
    public function ensureProductDim(int $businessId, ?Product $product, string $fallbackName, ?string $unit = null, ?string $category = null): int
    {
        return $this->dimensionService->getOrCreateProductDimension(
            $businessId,
            $product?->id,
            $product?->name ?? $fallbackName,
            $category ?? $product?->category
        )->id;
    }

    /**
     * @deprecated Use OlapDimensionService::getOrCreateCustomerDimension()
     */
    public function ensureCustomerDim(int $businessId, ?Customer $customer, ?string $fallbackName): ?int
    {
        if (!$customer && !$fallbackName) {
            return $this->dimensionService->getOrCreateCustomerDimension(
                $businessId,
                null,
                'Unknown',
                null,
                null
            )->id;
        }

        return $this->dimensionService->getOrCreateCustomerDimension(
            $businessId,
            $customer?->id,
            $customer?->customer_name ?? $fallbackName,
            $customer?->customer_type ?? null,
            $customer?->phone
        )->id;
    }

    /**
     * @deprecated Use OlapDimensionService::getOrCreateCustomerDimension() with null values
     */
    public function ensureUnknownCustomer(int $businessId): int
    {
        return $this->dimensionService->getOrCreateCustomerDimension(
            $businessId,
            null,
            'Unknown',
            null,
            null
        )->id;
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
                    'data_feed_id' => null,
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


    /**
     * Load facts from staging tables using ETL service
     *
     * @deprecated Use OlapETLService::loadFactsFromStaging() directly
     */
    public function loadFactsFromStaging(DataFeed $feed): array
    {
        $stats = $this->etlService->loadFactsFromStaging($feed->id);

        return [
            'records' => $stats['inserted'],
            'gross_revenue' => 0, // Stats no longer tracked here - use repository queries
            'cogs_amount' => 0,
            'gross_margin_amount' => 0,
        ];
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
