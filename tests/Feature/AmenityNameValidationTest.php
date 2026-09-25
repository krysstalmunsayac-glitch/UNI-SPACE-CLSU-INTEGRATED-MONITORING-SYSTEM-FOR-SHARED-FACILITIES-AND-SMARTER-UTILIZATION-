<?php

use App\Livewire\Amenities\AmenityManagement;
use App\Models\Amenity;
use App\Models\Facility;
use App\Models\User;
use Livewire\Livewire;

it('allows the same amenity name for different facilities', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $firstFacility = Facility::query()->create(['Facility_Name' => 'First Amenity Hall', 'Status' => 'Available']);
    $secondFacility = Facility::query()->create(['Facility_Name' => 'Second Amenity Hall', 'Status' => 'Available']);

    $existingAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Projector',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $existingAmenity->facilities()->attach($firstFacility->FID);

    $this->actingAs($administrator);

    Livewire::test(AmenityManagement::class)
        ->call('create')
        ->set('name', 'Projector')
        ->set('facilityIds', [$secondFacility->FID])
        ->call('save', true)
        ->assertHasNoErrors();

    expect(Amenity::query()->where('name', 'Projector')->count())->toBe(2);
});

it('keeps amenity editing functional after an amenity with the same name is archived', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facility::query()->create(['Facility_Name' => 'Archived Amenity Hall', 'Status' => 'Available']);

    $archivedAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Sound System',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $archivedAmenity->facilities()->attach($facility->FID);
    $archivedAmenity->delete();

    $activeAmenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Sound System',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $activeAmenity->facilities()->attach($facility->FID);

    $this->actingAs($administrator);

    Livewire::test(AmenityManagement::class)
        ->call('edit', $activeAmenity->AID)
        ->set('Description', 'Updated without using the removed brand field.')
        ->call('save')
        ->assertHasNoErrors();

    expect($activeAmenity->fresh()->Description)->toBe('Updated without using the removed brand field.');
});
