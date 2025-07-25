<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Additional fields for business tracking
            $table->string('company_name')->nullable();
            $table->string('business_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->default('user'); // Temporary, will be changed to role_id later
            $table->boolean('is_active')->default(true);
            $table->json('business_metrics')->nullable(); // For storing custom metrics preferences
            $table->boolean('setup_completed')->default(false);
            $table->timestamp('setup_completed_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
