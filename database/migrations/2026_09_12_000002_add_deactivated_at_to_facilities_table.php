<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dateTime('Deactivated_At')->nullable()->after('Available_At');
        });

        DB::table('facilities')
            ->where('Status', 'Unavailable')
            ->whereNull('Deactivated_At')
            ->update(['Deactivated_At' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('Deactivated_At');
        });
    }
};
