<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Duplicate create users migration replaced with NOOP to prevent duplicate table creation.
return new class extends Migration {
    public function up(): void { /* NOOP duplicate */ }
    public function down(): void { /* NOOP duplicate */ }
};
