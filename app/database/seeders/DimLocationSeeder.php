<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DimLocationSeeder extends Seeder
{
    /**
     * Seed Indonesian location hierarchy for UMKM analytics.
     *
     * This provides common locations that UMKM businesses operate in.
     * Can be expanded with full BPS data for production.
     */
    public function run(): void
    {
        // Common Indonesian locations for UMKM
        $locations = [
            // DKI Jakarta
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Pusat', 'kecamatan' => 'Gambir'],
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Pusat', 'kecamatan' => 'Tanah Abang'],
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Selatan', 'kecamatan' => 'Kebayoran Baru'],
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Selatan', 'kecamatan' => 'Tebet'],
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Barat', 'kecamatan' => 'Grogol Petamburan'],
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Timur', 'kecamatan' => 'Jatinegara'],
            ['provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Utara', 'kecamatan' => 'Kelapa Gading'],

            // Jawa Barat
            ['provinsi' => 'Jawa Barat', 'kota' => 'Bandung', 'kecamatan' => 'Lengkong'],
            ['provinsi' => 'Jawa Barat', 'kota' => 'Bandung', 'kecamatan' => 'Coblong'],
            ['provinsi' => 'Jawa Barat', 'kota' => 'Bandung', 'kecamatan' => 'Cicendo'],
            ['provinsi' => 'Jawa Barat', 'kota' => 'Bogor', 'kecamatan' => 'Bogor Tengah'],
            ['provinsi' => 'Jawa Barat', 'kota' => 'Bekasi', 'kecamatan' => 'Bekasi Barat'],
            ['provinsi' => 'Jawa Barat', 'kota' => 'Depok', 'kecamatan' => 'Beji'],

            // Jawa Tengah
            ['provinsi' => 'Jawa Tengah', 'kota' => 'Semarang', 'kecamatan' => 'Semarang Tengah'],
            ['provinsi' => 'Jawa Tengah', 'kota' => 'Solo', 'kecamatan' => 'Laweyan'],
            ['provinsi' => 'Jawa Tengah', 'kota' => 'Yogyakarta', 'kecamatan' => 'Gondokusuman'],

            // Jawa Timur
            ['provinsi' => 'Jawa Timur', 'kota' => 'Surabaya', 'kecamatan' => 'Tegalsari'],
            ['provinsi' => 'Jawa Timur', 'kota' => 'Surabaya', 'kecamatan' => 'Gubeng'],
            ['provinsi' => 'Jawa Timur', 'kota' => 'Malang', 'kecamatan' => 'Klojen'],

            // Bali
            ['provinsi' => 'Bali', 'kota' => 'Denpasar', 'kecamatan' => 'Denpasar Selatan'],
            ['provinsi' => 'Bali', 'kota' => 'Badung', 'kecamatan' => 'Kuta'],

            // Sumatera
            ['provinsi' => 'Sumatera Utara', 'kota' => 'Medan', 'kecamatan' => 'Medan Kota'],
            ['provinsi' => 'Sumatera Barat', 'kota' => 'Padang', 'kecamatan' => 'Padang Barat'],
            ['provinsi' => 'Sumatera Selatan', 'kota' => 'Palembang', 'kecamatan' => 'Ilir Timur I'],

            // Kalimantan
            ['provinsi' => 'Kalimantan Timur', 'kota' => 'Balikpapan', 'kecamatan' => 'Balikpapan Kota'],
            ['provinsi' => 'Kalimantan Selatan', 'kota' => 'Banjarmasin', 'kecamatan' => 'Banjarmasin Tengah'],

            // Sulawesi
            ['provinsi' => 'Sulawesi Selatan', 'kota' => 'Makassar', 'kecamatan' => 'Makassar'],
            ['provinsi' => 'Sulawesi Utara', 'kota' => 'Manado', 'kecamatan' => 'Wenang'],

            // Unknown/Default location
            ['provinsi' => 'Unknown', 'kota' => 'Unknown', 'kecamatan' => 'Unknown'],
        ];

        foreach ($locations as $location) {
            DB::table('dim_location')->insertOrIgnore([
                'business_id' => null, // Global reference locations (not business-specific)
                'location_nk' => null,
                'kecamatan' => $location['kecamatan'],
                'kota' => $location['kota'],
                'provinsi' => $location['provinsi'],
                'kode_pos' => null,
                'latitude' => null,
                'longitude' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
