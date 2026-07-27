<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->decimal('Latitude', 10, 7)->nullable()->after('Location');
            $table->decimal('Longitude', 10, 7)->nullable()->after('Latitude');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['Latitude', 'Longitude']);
        });
    }
};
