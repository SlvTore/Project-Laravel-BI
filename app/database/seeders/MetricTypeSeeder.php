<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MetricType;

class MetricTypeSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $metricTypes = [
            [
                'name' => 'total_penjualan',
                'display_name' => 'Total Penjualan',
                'description' => 'Total nilai penjualan yang dihasilkan dalam periode tertentu',
                'category' => 'Penjualan',
                'icon' => 'bi-currency-dollar',
                'unit' => 'Rp',
                'format' => 'currency',
                'settings' => [
                    'target_type' => 'monthly',
                    'calculation' => 'sum'
                ],
                'sort_order' => 1
            ],
            [
                'name' => 'biaya_pokok_penjualan',
                'display_name' => 'Biaya Pokok Penjualan (COGS)',
                'description' => 'Total biaya langsung yang dikeluarkan untuk menghasilkan produk yang dijual',
                'category' => 'Keuangan',
                'icon' => 'bi-receipt',
                'unit' => 'Rp',
                'format' => 'currency',
                'settings' => [
                    'target_type' => 'monthly',
                    'calculation' => 'sum'
                ],
                'sort_order' => 2
            ],
            [
                'name' => 'margin_keuntungan',
                'display_name' => 'Margin Keuntungan (Profit Margin)',
                'description' => 'Persentase keuntungan dari penjualan setelah dikurangi biaya pokok penjualan',
                'category' => 'Keuangan',
                'icon' => 'bi-percent',
                'unit' => '%',
                'format' => 'percentage',
                'settings' => [
                    'target_type' => 'monthly',
                    'calculation' => 'percentage'
                ],
                'sort_order' => 3
            ],
            [
                'name' => 'penjualan_produk_terlaris',
                'display_name' => 'Penjualan Produk Terlaris',
                'description' => 'Jumlah unit produk terlaris yang terjual dalam periode tertentu',
                'category' => 'Produk',
                'icon' => 'bi-star-fill',
                'unit' => 'unit',
                'format' => 'number',
                'settings' => [
                    'target_type' => 'monthly',
                    'calculation' => 'count'
                ],
                'sort_order' => 4
            ],
            [
                'name' => 'jumlah_pelanggan_baru',
                'display_name' => 'Jumlah Pelanggan Baru',
                'description' => 'Jumlah pelanggan baru yang diperoleh dalam periode tertentu',
                'category' => 'Pelanggan',
                'icon' => 'bi-person-plus',
                'unit' => 'orang',
                'format' => 'number',
                'settings' => [
                    'target_type' => 'monthly',
                    'calculation' => 'count'
                ],
                'sort_order' => 5
            ],
            [
                'name' => 'jumlah_pelanggan_setia',
                'display_name' => 'Jumlah Pelanggan Setia',
                'description' => 'Jumlah pelanggan yang melakukan pembelian berulang dalam periode tertentu',
                'category' => 'Pelanggan',
                'icon' => 'bi-heart-fill',
                'unit' => 'orang',
                'format' => 'number',
                'settings' => [
                    'target_type' => 'monthly',
                    'calculation' => 'count'
                ],
                'sort_order' => 6
            ]
        ];

        foreach ($metricTypes as $metricType) {
            MetricType::updateOrCreate(
                ['name' => $metricType['name']],
                $metricType
            );
        }
    }
}Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MetricTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
    }
}
