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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('first_purchase_date');
            $table->date('last_purchase_date')->nullable();
            $table->integer('total_purchases')->default(1);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->enum('customer_type', ['new', 'returning', 'loyal'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'first_purchase_date']);
            $table->index(['business_id', 'customer_type']);
            $table->index(['business_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
