<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing roles
        DB::table('roles')->truncate();

        // Insert new roles as per requirements
        $roles = [
            [
                'name' => 'business-owner',
                'display_name' => 'Business Owner',
                'description' => 'Pemilik bisnis dengan akses penuh ke semua fitur dan data. Dapat mengelola tim dan mengundang staff.',
                'permissions' => json_encode(['all']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'administrator',
                'display_name' => 'Administrator',
                'description' => 'Administrator dengan hak akses tinggi. Dapat mengelola data, tim, dan melakukan import/delete metric.',
                'permissions' => json_encode([
                    'manage_data', 
                    'view_reports', 
                    'manage_team', 
                    'import_metrics', 
                    'delete_metrics',
                    'promote_staff',
                    'delete_staff'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Staff yang dapat menginput dan melihat data. Memiliki akses ke dashboard metrics dan feeds.',
                'permissions' => json_encode([
                    'create_data', 
                    'view_data', 
                    'view_feeds',
                    'edit_profile'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'business-investigator',
                'display_name' => 'Business Investigator',
                'description' => 'Investigator dengan akses view-only ke ringkasan statistik dashboard.',
                'permissions' => json_encode([
                    'view_statistics', 
                    'view_summary'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('roles')->insert($roles);
    }
}