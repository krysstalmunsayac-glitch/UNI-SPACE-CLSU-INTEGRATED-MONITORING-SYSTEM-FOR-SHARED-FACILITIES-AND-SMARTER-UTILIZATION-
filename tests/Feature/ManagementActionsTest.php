<?php

use App\Actions\Amenities\SaveAmenity;
use App\Actions\Amenities\ToggleAmenityStatus;
use App\Actions\Facilities\SaveFacility;
use App\Actions\Lifecycle\ArchiveRecord;
use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates and updates a facility through the facility action', function () {
    $action = app(SaveFacility::class);
    $facility = $action->handle(null, [
        'Facility_Name' => 'Test Hall',
        'Office' => 'OSA',
        'Description' => 'A test facility.',
        'Capacity' => 100,
        'facility_type' => 'auditorium',
        'Status' => 'Available',
    ]);

    $updated = $action->handle($facility, ['Facility_Name' => 'Updated Hall']);

    expect($updated->FID)->toBe($facility->FID)
        ->and($updated->Facility_Name)->toBe('Updated Hall');
});

it('archives, restores, and permanently deletes soft-deletable records through lifecycle actions', function () {
    $facility = Facility::query()->create(['Facility_Name' => 'Lifecycle Hall']);

    app(ArchiveRecord::class)->handle($facility);
    expect(Facility::withTrashed()->findOrFail($facility->FID)->trashed())->toBeTrue();

    app(RestoreRecord::class)->handle($facility);
    expect($facility->fresh()->trashed())->toBeFalse();

    app(ArchiveRecord::class)->handle($facility);
    app(PermanentlyDeleteRecord::class)->handle($facility);
    expect(Facility::withTrashed()->find($facility->FID))->toBeNull();
});

it('creates, assigns, and toggles an amenity through actions', function () {
    $actor = User::factory()->create(['user_type' => 'admin']);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Test Room',
        'Status' => 'Available',
    ]);

    $amenity = app(SaveAmenity::class)->handle(null, [
        'name' => 'Projector',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 2,
    ], [$facility->FID], $actor->id);

    $amenity = app(ToggleAmenityStatus::class)->handle($amenity);

    expect($amenity->Status)->toBe('Unavailable')
        ->and($amenity->facilities()->pluck('facilities.FID')->all())->toBe([$facility->FID]);
});
