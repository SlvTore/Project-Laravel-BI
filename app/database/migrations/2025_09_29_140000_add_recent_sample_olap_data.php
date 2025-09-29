<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // Add some recent sample data to show in metrics dashboard
        $businessId = DB::table('businesses')->value('id');
        if (!$businessId) return;

        // Add recent dimension dates
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            $dates[] = [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'month' => $date->month,
                'year' => $date->year,
                'quarter' => $date->quarter,
                'month_name' => $date->format('F'),
                'day_name' => $date->format('l'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert dates if they don't exist
        foreach ($dates as $dateData) {
            DB::table('dim_date')->updateOrInsert(
                ['date' => $dateData['date']],
                $dateData
            );
        }

        // Get a sample product
        $productId = DB::table('dim_product')->where('business_id', $businessId)->value('id');
        if (!$productId) {
            $productId = DB::table('dim_product')->insertGetId([
                'business_id' => $businessId,
                'product_nk' => null,
                'name' => 'Sample Product',
                'category' => 'Default',
                'unit' => 'pcs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add recent fact_sales data
        $existingRecent = DB::table('fact_sales')
            ->join('dim_date', 'dim_date.id', '=', 'fact_sales.date_id')
            ->where('fact_sales.business_id', $businessId)
            ->where('dim_date.date', '>=', Carbon::now()->subDays(7)->toDateString())
            ->exists();

        if (!$existingRecent) {
            for ($i = 0; $i < 7; $i++) {
                $date = Carbon::now()->subDays($i);
                $dateId = DB::table('dim_date')->where('date', $date->toDateString())->value('id');

                if ($dateId) {
                    $revenue = rand(50000, 200000);
                    $cogs = $revenue * 0.6;
                    $margin = $revenue - $cogs;
                    $quantity = rand(1, 5);

                    DB::table('fact_sales')->insert([
                        'business_id' => $businessId,
                        'date_id' => $dateId,
                        'product_id' => $productId,
                        'customer_id' => null,
                        'channel_id' => null,
                        'data_feed_id' => null,
                        'sales_transaction_id' => null,
                        'sales_transaction_item_id' => null,
                        'quantity' => $quantity,
                        'unit_price' => $revenue / $quantity,
                        'discount' => 0,
                        'subtotal' => $revenue,
                        'tax_amount' => 0,
                        'shipping_cost' => 0,
                        'total_amount' => $revenue,
                        'gross_revenue' => $revenue,
                        'cogs_amount' => $cogs,
                        'gross_margin_amount' => $margin,
                        'gross_margin_percent' => ($margin / $revenue) * 100,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Remove recent test data if needed
        $businessId = DB::table('businesses')->value('id');
        if ($businessId) {
            DB::table('fact_sales')
                ->join('dim_date', 'dim_date.id', '=', 'fact_sales.date_id')
                ->where('fact_sales.business_id', $businessId)
                ->where('dim_date.date', '>=', Carbon::now()->subDays(7)->toDateString())
                ->delete();
        }
    }
};
