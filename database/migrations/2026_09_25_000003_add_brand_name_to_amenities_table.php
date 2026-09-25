<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->string('brand_name', 150)->nullable()->after('name');
        });

        DB::table('amenities')
            ->orderBy('AID')
            ->eachById(function ($amenity): void {
                DB::table('amenities')
                    ->where('AID', $amenity->AID)
                    ->update([
                        'brand_name' => 'CLSU-LEGACY-'.str_pad((string) $amenity->AID, 5, '0', STR_PAD_LEFT),
                    ]);
            }, 100, 'AID');

        Schema::table('amenities', function (Blueprint $table): void {
            $table->string('brand_name', 150)->nullable(false)->change();
            $table->unique('brand_name', 'amenities_brand_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->dropUnique('amenities_brand_name_unique');
            $table->dropColumn('brand_name');
        });
    }
};
