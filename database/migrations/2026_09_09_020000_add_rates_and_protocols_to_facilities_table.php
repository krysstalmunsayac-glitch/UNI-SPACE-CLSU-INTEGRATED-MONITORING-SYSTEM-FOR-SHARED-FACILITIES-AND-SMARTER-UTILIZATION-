<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->longText('rates')->nullable()->after('Rate_Details');
            $table->longText('protocols_and_guidelines')->nullable()->after('Protocols');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['rates', 'protocols_and_guidelines']);
        });
    }
};
