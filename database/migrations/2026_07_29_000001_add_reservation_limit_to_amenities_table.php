<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->unsignedInteger('reservation_limit')->nullable()->after('Status');
        });

        DB::table('amenities')
            ->whereRaw('LOWER(name) = ?', ['sound system'])
            ->update(['reservation_limit' => 5]);
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropColumn('reservation_limit');
        });
    }
};
