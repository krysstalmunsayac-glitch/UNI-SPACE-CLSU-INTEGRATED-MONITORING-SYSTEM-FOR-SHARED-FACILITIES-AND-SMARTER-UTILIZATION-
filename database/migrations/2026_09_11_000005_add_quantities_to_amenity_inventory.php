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
            $table->unsignedInteger('inventory_quantity')->default(1)->after('reservation_limit');
        });

        DB::table('amenities')->update([
            'inventory_quantity' => DB::raw('COALESCE(reservation_limit, 1)'),
        ]);

        Schema::table('request_facility_amenities', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('Amenity_ID');
        });
    }

    public function down(): void
    {
        Schema::table('request_facility_amenities', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('amenities', function (Blueprint $table) {
            $table->dropColumn('inventory_quantity');
        });
    }
};
