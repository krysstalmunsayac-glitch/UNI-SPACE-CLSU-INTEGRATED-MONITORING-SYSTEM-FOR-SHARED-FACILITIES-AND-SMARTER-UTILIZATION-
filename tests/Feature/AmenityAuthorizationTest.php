<?php

use App\Livewire\Amenities\AmenityManagement;
use App\Models\Amenity;
use App\Models\Facility;
use App\Models\User;
use Livewire\Livewire;

function amenityAuthorizationFixture(): array
{
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);

    $facility = Facility::query()->create([
        'Facility_Name' => 'Authorization Test Hall',
        'Status' => 'Available',
    ]);

    $amenity = Amenity::query()->create([
        'created_by' => $administrator->id,
        'name' => 'Authorization Test Projector',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $amenity->facilities()->attach($facility->FID);

    return [$administrator, $amenity];
}

it('denies the amenities component to ordinary users', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(AmenityManagement::class)->assertForbidden();
});

it('blocks a status change when an administrator is demoted after loading the component', function () {
    [$administrator, $amenity] = amenityAuthorizationFixture();
    $this->actingAs($administrator);

    $component = Livewire::test(AmenityManagement::class)
        ->call('requestToggleStatus', $amenity->AID)
        ->set('deactivationConfirmation', 'DEACTIVATE');

    $administrator->update(['user_type' => 'user']);
    $this->actingAs($administrator->fresh());

    $component
        ->call('confirmToggleStatus')
        ->assertForbidden();

    expect($amenity->fresh()->Status)->toBe('Available');
});

it('blocks lifecycle mutations when an administrator is demoted after loading the component', function (string $method, bool $startsArchived) {
    [$administrator, $amenity] = amenityAuthorizationFixture();

    if ($startsArchived) {
        $amenity->delete();
    }

    $this->actingAs($administrator);
    $component = Livewire::test(AmenityManagement::class);

    $administrator->update(['user_type' => 'user']);
    $this->actingAs($administrator->fresh());

    $component
        ->call($method, $amenity->AID)
        ->assertForbidden();

    $storedAmenity = Amenity::withTrashed()->find($amenity->AID);

    expect($storedAmenity)->not->toBeNull()
        ->and($storedAmenity->trashed())->toBe($startsArchived);
})->with([
    'archive' => ['delete', false],
    'restore' => ['restore', true],
    'permanent deletion' => ['forceDelete', true],
]);
