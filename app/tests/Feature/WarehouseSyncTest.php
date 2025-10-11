<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\DataWarehouseSyncService;
use Illuminate\Support\Facades\DB;

class WarehouseSyncTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_runs_sync_without_errors_for_business_with_no_metrics(): void
    {
        // No business seeded yet; expect graceful handling
        $service = app(DataWarehouseSyncService::class);
        $result = $service->refreshBusinessMetrics(1); // business id that does not exist
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics_refreshed', $result);
        $this->assertEquals(0, $result['metrics_refreshed']);
    }

    /** @test */
    public function warehouse_validation_command_reports_missing_channel_dimension(): void
    {
        // Migrate schema, insert minimal rows missing channel dimension
        DB::table('dim_date')->insert([
            'date' => now()->toDateString(),
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'day' => (int) now()->format('d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fact_sales')->insert([
            'business_id' => 1,
            'date_id' => DB::table('dim_date')->first()->id,
            'product_id' => null,
            'customer_id' => null,
            'channel_id' => 999999, // invalid
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = $this->artisan('warehouse:validate');
        $exitCode->assertExitCode(2); // issues found
    }
}
