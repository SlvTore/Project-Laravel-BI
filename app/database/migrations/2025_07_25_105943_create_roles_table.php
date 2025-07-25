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
                'name' => 'owner',
                'display_name' => 'Business Owner',
                'description' => 'Pemilik bisnis dengan akses penuh ke semua fitur dan data',
                'permissions' => json_encode(['all']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Mengelola operasional harian dan input data bisnis',
                'permissions' => json_encode(['manage_data', 'view_reports', 'manage_team']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'mentor',
                'display_name' => 'Mentor/Advisor',
                'description' => 'Memberikan bimbingan dan memantau progress bisnis',
                'permissions' => json_encode(['view_reports', 'give_feedback']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'investigator',
                'display_name' => 'Data Investigator',
                'description' => 'Menganalisis data dan membuat laporan khusus',
                'permissions' => json_encode(['view_analytics', 'export_data', 'custom_reports']),
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
