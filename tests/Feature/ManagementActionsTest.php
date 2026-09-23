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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('compresses facility uploads to a card-sized webp image', function () {
    Storage::fake('public');

    $facility = app(SaveFacility::class)->handle(null, [
        'Facility_Name' => 'Compressed Hall',
        'Status' => 'Available',
    ], [UploadedFile::fake()->image('large-facility.jpg', 2400, 1600)]);

    $path = $facility->images()->value('image_path');

    Storage::disk('public')->assertExists($path);
    expect($path)->toEndWith('.webp');

    [$width, $height, $type] = getimagesize(Storage::disk('public')->path($path));

    expect(max($width, $height))->toBe(1200)
        ->and($type)->toBe(IMAGETYPE_WEBP);
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
