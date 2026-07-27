<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\Requests;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('requests resolve amenities from their facility without relying on legacy request amenities relations', function () {
    $facility = Facilities::create([
        'Facility_Name' => 'Test Facility',
        'Price' => 100,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);

    $amenity = Amenities::create([
        'name' => 'Test Amenity',
        'Description' => 'A test amenity',
        'Status' => 'Available',
    ]);

    $facility->amenities()->attach($amenity->AID);

    $request = Requests::create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->toDateString(),
        'Proposed_Start_Time' => '09:00:00',
        'Proposed_End_Time' => '10:00:00',
        'Status' => 'Pending',
        'Purpose' => 'Testing',
    ]);

    $requestAmenities = $request->fresh()->facility->amenities;

    expect($requestAmenities)->toHaveCount(1)
        ->and($requestAmenities->first()->AID)->toBe($amenity->AID);
});
