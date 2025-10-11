<?php

namespace App\Services\Olap;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for managing OLAP dimension tables
 * Handles dimension lookups, inserts, and default records
 */
class OlapDimensionService
{
    /**
     * Get or create date dimension record
     *
     * @param string $date Date in Y-m-d format
     * @return int Date dimension ID
     */
    public function getOrCreateDateDimension(string $date): int
    {
        $carbonDate = Carbon::parse($date);
        
        $existing = DB::table('dim_date')->where('date', $carbonDate->format('Y-m-d'))->first();
        
        if ($existing) {
            return $existing->id;
        }

        return DB::table('dim_date')->insertGetId([
            'date' => $carbonDate->format('Y-m-d'),
            'day' => $carbonDate->day,
            'day_of_month' => $carbonDate->day,
            'month' => $carbonDate->month,
            'year' => $carbonDate->year,
            'quarter' => $carbonDate->quarter,
            'month_name' => $carbonDate->format('F'),
            'day_name' => $carbonDate->format('l'),
            'week_of_year' => $carbonDate->weekOfYear,
            'day_of_week' => $carbonDate->dayOfWeek + 1,
            'is_weekend' => $carbonDate->isWeekend(),
            'fiscal_period' => $carbonDate->format('Y-m'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create product dimension record
     *
     * @param int $businessId
     * @param int|null $productId Product ID from products table
     * @param string|null $productName Fallback product name
     * @return int Product dimension ID
     */
    public function getOrCreateProductDimension(int $businessId, ?int $productId, ?string $productName = null): int
    {
        // If no product, return unknown product dimension
        if (!$productId && !$productName) {
            return $this->getUnknownProductDimension($businessId);
        }

        $naturalKey = $productId ? "PROD_{$productId}" : "NAME_" . md5($productName);

        $existing = DB::table('dim_product')
            ->where('business_id', $businessId)
            ->where('product_nk', $naturalKey)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Get product details if productId provided
        $product = null;
        if ($productId) {
            $product = DB::table('products')->where('id', $productId)->first();
        }

        return DB::table('dim_product')->insertGetId([
            'business_id' => $businessId,
            'product_nk' => $naturalKey,
            'name' => $product->name ?? $productName ?? 'Unknown Product',
            'category' => $product->category ?? null,
            'unit' => $product->unit ?? null,
            'cost_price' => $product->cost_price ?? null,
            'selling_price' => $product->selling_price ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create customer dimension record
     *
     * @param int $businessId
     * @param int|null $customerId Customer ID from customers table
     * @param string|null $customerName Fallback customer name
     * @return int Customer dimension ID
     */
    public function getOrCreateCustomerDimension(int $businessId, ?int $customerId, ?string $customerName = null): int
    {
        // If no customer, return unknown customer dimension
        if (!$customerId && !$customerName) {
            return $this->getUnknownCustomerDimension($businessId);
        }

        $naturalKey = $customerId ? "CUST_{$customerId}" : "NAME_" . md5($customerName);

        $existing = DB::table('dim_customer')
            ->where('business_id', $businessId)
            ->where('customer_nk', $naturalKey)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Get customer details if customerId provided
        $customer = null;
        if ($customerId) {
            $customer = DB::table('customers')->where('id', $customerId)->first();
        }

        return DB::table('dim_customer')->insertGetId([
            'business_id' => $businessId,
            'customer_nk' => $naturalKey,
            'name' => $customer->customer_name ?? $customerName ?? 'Unknown Customer',
            'email' => $customer->email ?? null,
            'phone' => $customer->phone ?? null,
            'customer_type' => $customer->customer_type ?? 'unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create channel dimension record
     *
     * @param int $businessId
     * @param string|null $channelName
     * @return int Channel dimension ID
     */
    public function getOrCreateChannelDimension(int $businessId, ?string $channelName = null): int
    {
        if (!$channelName) {
            return $this->getDefaultChannelDimension($businessId);
        }

        $naturalKey = 'CHANNEL_' . strtoupper(str_replace(' ', '_', $channelName));

        $existing = DB::table('dim_channel')
            ->where('business_id', $businessId)
            ->where('channel_nk', $naturalKey)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('dim_channel')->insertGetId([
            'business_id' => $businessId,
            'channel_nk' => $naturalKey,
            'name' => $channelName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create promotion dimension record
     *
     * @param int $businessId
     * @param string|null $promotionNk Natural key for promotion
     * @return int Promotion dimension ID
     */
    public function getOrCreatePromotionDimension(int $businessId, ?string $promotionNk = null): int
    {
        if (!$promotionNk) {
            return $this->getNoPromotionDimension($businessId);
        }

        $existing = DB::table('dim_promotion')
            ->where('business_id', $businessId)
            ->where('promotion_nk', $promotionNk)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Return no promotion if not found
        return $this->getNoPromotionDimension($businessId);
    }

    /**
     * Ensure default dimensions exist for a business
     *
     * @param int $businessId
     * @return void
     */
    public function ensureDefaultDimensions(int $businessId): void
    {
        $this->getUnknownProductDimension($businessId);
        $this->getUnknownCustomerDimension($businessId);
        $this->getDefaultChannelDimension($businessId);
        $this->getNoPromotionDimension($businessId);
    }

    /**
     * Get or create unknown product dimension
     *
     * @param int $businessId
     * @return int
     */
    private function getUnknownProductDimension(int $businessId): int
    {
        $existing = DB::table('dim_product')
            ->where('business_id', $businessId)
            ->where('product_nk', 'UNKNOWN')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('dim_product')->insertGetId([
            'business_id' => $businessId,
            'product_nk' => 'UNKNOWN',
            'name' => 'Unknown Product',
            'category' => 'Unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create unknown customer dimension
     *
     * @param int $businessId
     * @return int
     */
    private function getUnknownCustomerDimension(int $businessId): int
    {
        $existing = DB::table('dim_customer')
            ->where('business_id', $businessId)
            ->where('customer_nk', 'UNKNOWN')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('dim_customer')->insertGetId([
            'business_id' => $businessId,
            'customer_nk' => 'UNKNOWN',
            'name' => 'Unknown Customer',
            'customer_type' => 'unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create default channel dimension
     *
     * @param int $businessId
     * @return int
     */
    private function getDefaultChannelDimension(int $businessId): int
    {
        $existing = DB::table('dim_channel')
            ->where('business_id', $businessId)
            ->where('channel_nk', 'DEFAULT')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('dim_channel')->insertGetId([
            'business_id' => $businessId,
            'channel_nk' => 'DEFAULT',
            'name' => 'Direct',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create no promotion dimension
     *
     * @param int $businessId
     * @return int
     */
    private function getNoPromotionDimension(int $businessId): int
    {
        $existing = DB::table('dim_promotion')
            ->where('business_id', $businessId)
            ->where('promotion_nk', 'NO_PROMOTION')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('dim_promotion')->insertGetId([
            'business_id' => $businessId,
            'promotion_nk' => 'NO_PROMOTION',
            'name' => 'No Promotion',
            'start_date' => '2020-01-01',
            'end_date' => '2099-12-31',
            'discount_percent' => 0,
            'discount_amount' => 0,
            'promotion_type' => 'none',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
