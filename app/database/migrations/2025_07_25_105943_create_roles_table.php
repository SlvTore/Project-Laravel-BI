<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description');
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            [
                'name' => 'business-owner',
                'display_name' => 'Business Owner',
                'description' => 'Pemilik bisnis dengan akses penuh ke semua fitur dan data dashboard',
                'permissions' => json_encode(['all']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'administrator',
                'display_name' => 'Administrator',
                'description' => 'Administrator yang dipromosikan dari Staff dengan kemampuan mengelola pengguna dan import/delete metrik',
                'permissions' => json_encode(['manage_data', 'view_reports', 'manage_users', 'import_metrics', 'delete_metrics', 'promote_users']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Staff yang dapat menginput dan melihat data di dashboard metrics',
                'permissions' => json_encode(['create_data', 'view_data', 'view_feeds', 'view_profile']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'business-investigator',
                'display_name' => 'Business Investigator',
                'description' => 'Akses view-only ke ringkasan statistik dashboard tanpa melihat data mentah',
                'permissions' => json_encode(['view_summary', 'view_stats']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
