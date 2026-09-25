<?php

use App\Livewire\Amenities\AmenityManagement;
use App\Models\Amenity;
use App\Models\Facility;
use App\Models\User;
use Livewire\Livewire;

it('shows validation instead of a server error for active and archived duplicate brand names', function (bool $archiveExisting) {
    $administrator = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facility::query()->create(['Facility_Name' => 'Duplicate Brand Test Hall', 'Status' => 'Available']);
    $existingAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Shared Projector',
        'brand_name' => 'CLSU-PROJECTOR-001',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 2,
    ]);
    $existingAmenity->facilities()->attach($facility->FID);

    if ($archiveExisting) {
        $existingAmenity->delete();
    }

    $this->actingAs($administrator);

    Livewire::test(AmenityManagement::class)
        ->call('create')
        ->set('name', 'Shared Projector')
        ->set('brand_name', '  CLSU-PROJECTOR-001  ')
        ->set('Description', 'Attempted duplicate brand.')
        ->set('facilityIds', [$facility->FID])
        ->call('save', true)
        ->assertHasErrors(['brand_name']);

    expect(Amenity::withTrashed()->where('brand_name', 'CLSU-PROJECTOR-001')->count())->toBe(1);
})->with(['active duplicate' => false, 'archived duplicate' => true]);

it('rejects changing an amenity to another brand name but allows keeping its own brand name', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facility::query()->create(['Facility_Name' => 'Rename Test Hall', 'Status' => 'Available']);
    $firstAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Projector',
        'brand_name' => 'CLSU-PROJECTOR-FIRST',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $secondAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Projector',
        'brand_name' => 'CLSU-PROJECTOR-SECOND',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $firstAmenity->facilities()->attach($facility->FID);
    $secondAmenity->facilities()->attach($facility->FID);

    $this->actingAs($administrator);

    Livewire::test(AmenityManagement::class)
        ->call('edit', $secondAmenity->AID)
        ->call('save')
        ->assertHasNoErrors('brand_name')
        ->call('edit', $secondAmenity->AID)
        ->set('brand_name', 'CLSU-PROJECTOR-FIRST')
        ->call('save')
        ->assertHasErrors(['brand_name']);

    expect($secondAmenity->fresh()->brand_name)->toBe('CLSU-PROJECTOR-SECOND');
});

it('allows duplicate amenity names in the same facility when brand names are unique', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facility::query()->create(['Facility_Name' => 'Duplicate Name Hall', 'Status' => 'Available']);
    $firstAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Portable Projector',
        'brand_name' => 'CLSU-PROJECTOR-A',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $firstAmenity->facilities()->attach($facility->FID);

    $this->actingAs($administrator);

    Livewire::test(AmenityManagement::class)
        ->call('create')
        ->set('name', 'Portable Projector')
        ->set('brand_name', 'CLSU-PROJECTOR-B')
        ->set('facilityIds', [$facility->FID])
        ->call('save', true)
        ->assertHasNoErrors(['name', 'brand_name']);

    $duplicates = Amenity::query()->where('name', 'Portable Projector')->with('facilities')->orderBy('AID')->get();

    expect($duplicates)->toHaveCount(2)
        ->and($duplicates->pluck('brand_name')->all())->toBe(['CLSU-PROJECTOR-A', 'CLSU-PROJECTOR-B'])
        ->and($duplicates[0]->facilities->modelKeys())->toBe([$facility->FID])
        ->and($duplicates[1]->facilities->modelKeys())->toBe([$facility->FID]);
});
