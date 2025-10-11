<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 64);
            $table->string('action', 32);
            $table->string('scope', 32)->nullable();
            $table->string('code', 160)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['resource','action','scope']);
            $table->index('resource');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
