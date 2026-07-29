<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only one active reservation request per user per event date', function () {
    $user = User::factory()->create(['user_type' => 'user']);
    $firstFacility = Facilities::query()->create([
        'Facility_Name' => 'First Daily Limit Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);
    $secondFacility = Facilities::query()->create([
        'Facility_Name' => 'Second Daily Limit Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);
    $eventDate = now()->addDays(4)->toDateString();

    Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $firstFacility->FID,
        'Proposed_Date' => $eventDate,
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'First reservation request for this date',
    ]);

    $this->actingAs($user)
        ->from(route('requests.create', $secondFacility))
        ->post(route('requests.store', $secondFacility), [
            'Event_Title' => 'Second event on same day',
            'Description' => 'This request should exceed the daily limit.',
            'Type_Event' => 'Meeting',
            'Proposed_Date' => $eventDate,
            'Proposed_Start_Time' => '13:00',
            'Proposed_End_Time' => '15:00',
            'Purpose' => 'Attempt a second request on the same date',
        ])
        ->assertRedirect(route('requests.create', $secondFacility))
        ->assertSessionHasErrors([
            'Proposed_Date' => 'You may only submit one reservation request per event date. Please choose another date.',
        ]);

    expect(Requests::query()->count())->toBe(1);
});

it('allows the same user to request a different event date', function () {
    $user = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Different Date Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);

    Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDays(4)->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Existing reservation on another date',
    ]);

    $this->actingAs($user)
        ->post(route('requests.store', $facility), [
            'Event_Title' => 'Event on another date',
            'Description' => 'This request uses a different event date.',
            'Type_Event' => 'Meeting',
            'Proposed_Date' => now()->addDays(5)->toDateString(),
            'Proposed_Start_Time' => '13:00',
            'Proposed_End_Time' => '15:00',
            'Purpose' => 'Submit a request for a different date',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    expect(Requests::query()->count())->toBe(2);
});
