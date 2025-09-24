<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend enum to include 'review'
        DB::statement("ALTER TABLE sales_transactions MODIFY COLUMN status ENUM('pending','completed','review','cancelled') NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE sales_transactions MODIFY COLUMN status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed'");
    }
};
