<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Track ownership transfer history
            $table->foreignId('transferred_from_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('ownership_transferred_at')->nullable()->after('transferred_from_user_id');
            $table->string('transfer_reason')->nullable()->after('ownership_transferred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transferred_from_user_id');
            $table->dropColumn(['ownership_transferred_at', 'transfer_reason']);
        });
    }
};
