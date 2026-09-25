<?php

use App\Models\Amenity;
use App\Models\Facility;
use Database\Seeders\ClsuFacilitySeeder;
use Database\Seeders\RebuildFacilityAmenitiesSeeder;
use Illuminate\Support\Facades\DB;

it('seeds facility-owned amenities with reusable names', function () {
    $this->seed(ClsuFacilitySeeder::class);

    $amenities = Amenity::query()->with('facilities')->get();

    expect($amenities)->not->toBeEmpty()
        ->and($amenities->every(fn (Amenity $amenity): bool => $amenity->facilities->count() === 1))->toBeTrue()
        ->and(Amenity::query()->where('name', 'Projector')->count())->toBeGreaterThan(1);

    $duplicateTypesWithinFacility = DB::table('facility_amenity as facility_amenities')
        ->join('amenities', 'amenities.AID', '=', 'facility_amenities.Amenity_ID')
        ->selectRaw('facility_amenities.Facility_ID, LOWER(amenities.name) as amenity_name')
        ->groupBy('facility_amenities.Facility_ID')
        ->groupByRaw('LOWER(amenities.name)')
        ->havingRaw('COUNT(*) > 1')
        ->count();

    expect($duplicateTypesWithinFacility)->toBe(0);
});

it('rebuilds amenities without overwriting current facility details', function () {
    $this->seed(ClsuFacilitySeeder::class);

    $facility = Facility::query()->firstOrFail();
    $facility->update(['Description' => 'Keep this current facility description.']);

    $this->seed(RebuildFacilityAmenitiesSeeder::class);

    expect($facility->fresh()->Description)->toBe('Keep this current facility description.')
        ->and(Amenity::query()->count())->toBeGreaterThan(1);
});
