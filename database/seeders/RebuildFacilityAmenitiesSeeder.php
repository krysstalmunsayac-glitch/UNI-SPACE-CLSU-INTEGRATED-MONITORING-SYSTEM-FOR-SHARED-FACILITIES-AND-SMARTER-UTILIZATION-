<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RebuildFacilityAmenitiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            DB::table('request_facility_amenities')->delete();
            DB::table('facility_amenity')->delete();
            DB::table('amenities')->delete();

            app(ClsuFacilitySeeder::class)->rebuildAmenitiesForExistingFacilities();
        }, 3);
    }
}
