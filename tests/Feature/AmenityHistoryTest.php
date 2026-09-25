<?php

use App\Models\Amenity;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\AdminReportExporter;

it('keeps archived amenities in historical requests and report exports', function () {
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Historical Amenity Hall',
        'Status' => 'Available',
    ]);
    $amenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Archived Projector',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 5,
    ]);
    $amenity->facilities()->attach($facility->FID);

    $facilityRequest = FacilityRequest::query()->create([
        'User_ID' => $administrator->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addDays(7),
        'Proposed_End_Date' => today()->addDays(7),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Approved',
        'Purpose' => 'Historical amenity test',
    ]);
    $facilityRequest->amenities()->attach($amenity->AID, ['quantity' => 3]);

    $amenity->delete();

    $historicalRequest = $facilityRequest->fresh()->load('amenities');
    $exporter = app(AdminReportExporter::class);
    $amenityColumn = array_search('Amenity', $exporter->requestHeaders(), true);
    $exportRow = $exporter->requestRow($historicalRequest);

    expect($historicalRequest->amenities)->toHaveCount(1)
        ->and($historicalRequest->amenities->first()->name)->toBe('Archived Projector')
        ->and((int) $historicalRequest->amenities->first()->pivot->quantity)->toBe(3)
        ->and($amenityColumn)->not->toBeFalse()
        ->and($exportRow[$amenityColumn])->toBe('Archived Projector (3 units)');
});
