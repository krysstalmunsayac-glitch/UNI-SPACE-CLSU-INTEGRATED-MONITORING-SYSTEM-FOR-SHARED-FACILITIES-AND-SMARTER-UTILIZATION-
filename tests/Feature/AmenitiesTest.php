<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('amenities can be created without an attached facility id', function () {
    $amenity = Amenities::create([
        'name' => 'Projector & Sound System',
        'Description' => 'Includes a projector, screen, and PA sound system.',
        'Status' => 'Available',
    ]);

    expect($amenity->AID)->not->toBeNull()
        ->and(Amenities::count())->toBe(1);
});

test('an admin can toggle an amenity availability status', function () {
    $admin = User::factory()->create(['user_type' => 'super_admin']);
    $amenity = Amenities::create([
        'name' => 'Projector',
        'Status' => 'Available',
    ]);

    Livewire::actingAs($admin)
        ->test('amenities.amenities')
        ->call('toggleStatus', $amenity->AID);

    expect($amenity->fresh()->Status)->toBe('Unavailable');

    Livewire::actingAs($admin)
        ->test('amenities.amenities')
        ->call('toggleStatus', $amenity->AID);

    expect($amenity->fresh()->Status)->toBe('Available');
});

test('an unavailable amenity cannot be added to a new facility request', function () {
    $user = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::create([
        'Facility_Name' => 'Amenity Test Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $amenity = Amenities::create([
        'name' => 'Unavailable Projector',
        'Status' => 'Unavailable',
    ]);
    $facility->amenities()->attach($amenity->AID);

    $this->actingAs($user)
        ->post(route('requests.store', $facility), [
            'Amenity_ID' => [$amenity->AID],
            'Proposed_Date' => now()->addDay()->toDateString(),
            'Proposed_Start_Time' => '09:00',
            'Proposed_End_Time' => '10:00',
            'Purpose' => 'Test request',
        ])
        ->assertSessionHasErrors('Amenity_ID.0');
});
