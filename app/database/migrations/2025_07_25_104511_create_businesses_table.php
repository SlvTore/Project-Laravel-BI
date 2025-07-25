<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('industry');
            $table->text('description')->nullable();
            $table->date('founded_date')->nullable();
            $table->string('website')->nullable();
            $table->decimal('initial_revenue', 15, 2)->nullable();
            $table->integer('initial_customers')->nullable();
            $table->json('goals')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
