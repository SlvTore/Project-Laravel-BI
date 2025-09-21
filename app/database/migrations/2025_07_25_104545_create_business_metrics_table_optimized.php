<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // This migration was a duplicate of create_business_metrics_table.
    // Make it a no-op to avoid table recreation conflicts.
    public function up(): void
    {
        // no-op
    }

    public function down(): void
    {
        // no-op
    }
};
