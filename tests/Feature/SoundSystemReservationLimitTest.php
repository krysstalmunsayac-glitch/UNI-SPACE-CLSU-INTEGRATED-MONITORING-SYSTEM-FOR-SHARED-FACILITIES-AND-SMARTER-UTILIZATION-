<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prevents a sixth overlapping sound system reservation', function () {
    $user = User::factory()->create(['user_type' => 'user']);

    $facility = Facilities::query()->create([
        'Facility_Name' => 'Testing Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);

    $soundSystem = Amenities::query()->create([
        'name' => 'Sound System',
        'Status' => 'Available',
        'reservation_limit' => 5,
    ]);

    $facility->amenities()->attach($soundSystem->AID);

    foreach (range(1, 5) as $index) {
        $existingUser = User::factory()->create(['user_type' => 'user']);

        $reservation = Requests::query()->create([
            'User_ID' => $existingUser->id,
            'Facility_ID' => $facility->FID,
            'Proposed_Date' => now()->addWeek()->toDateString(),
            'Proposed_Start_Time' => '09:00',
            'Proposed_End_Time' => '11:00',
            'Status' => $index === 1 ? 'Approved' : 'Pending',
            'Purpose' => "Reservation {$index}",
        ]);

        $reservation->amenities()->attach($soundSystem->AID);
    }

    expect($soundSystem->isFullyReserved(
        now()->addWeek()->toDateString(),
        '10:00',
        '12:00',
    ))->toBeTrue();

    $this->actingAs($user)
        ->from(route('requests.create', $facility))
        ->post(route('requests.store', $facility), [
            'Amenity_ID' => [$soundSystem->AID],
            'Event_Title' => 'Sixth event',
            'Description' => 'Should not reserve a sixth sound system.',
            'Type_Event' => 'Meeting',
            'Proposed_Date' => now()->addWeek()->toDateString(),
            'Proposed_Start_Time' => '10:00',
            'Proposed_End_Time' => '12:00',
            'Purpose' => 'Limit test',
        ])
        ->assertRedirect(route('requests.create', $facility))
        ->assertSessionHasErrors('Amenity_ID');

    expect(Requests::query()->count())->toBe(5);
});

it('allows the sound system when the requested time does not overlap', function () {
    $soundSystem = Amenities::query()->create([
        'name' => 'Sound System',
        'Status' => 'Available',
        'reservation_limit' => 5,
    ]);

    expect($soundSystem->isFullyReserved(
        now()->addWeek()->toDateString(),
        '11:00',
        '12:00',
    ))->toBeFalse();
});
