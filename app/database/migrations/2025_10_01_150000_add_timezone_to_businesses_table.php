<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('website');
            $table->tinyInteger('fiscal_year_start_month')->default(1)->after('timezone')->comment('1=January, 4=April, etc');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'fiscal_year_start_month']);
        });
    }
};
