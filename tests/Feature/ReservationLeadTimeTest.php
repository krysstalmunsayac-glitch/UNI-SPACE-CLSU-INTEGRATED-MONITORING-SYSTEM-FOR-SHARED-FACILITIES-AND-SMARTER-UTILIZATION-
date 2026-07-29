<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects reservations made only one or two days before the event', function (int $daysAhead) {
    $user = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Lead Time Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);

    $this->actingAs($user)
        ->from(route('requests.create', $facility))
        ->post(route('requests.store', $facility), [
            'Event_Title' => 'Too-soon event',
            'Description' => 'This reservation is inside the booking cutoff.',
            'Type_Event' => 'Meeting',
            'Proposed_Date' => now()->addDays($daysAhead)->toDateString(),
            'Proposed_Start_Time' => '09:00',
            'Proposed_End_Time' => '11:00',
            'Purpose' => 'Test the three-day booking rule',
        ])
        ->assertRedirect(route('requests.create', $facility))
        ->assertSessionHasErrors('Proposed_Date');

    expect(Requests::query()->count())->toBe(0);
})->with([1, 2]);

it('allows a reservation exactly three days before the event', function () {
    $user = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Three Day Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);

    $this->actingAs($user)
        ->post(route('requests.store', $facility), [
            'Event_Title' => 'Allowed event',
            'Description' => 'This reservation meets the cutoff.',
            'Type_Event' => 'Meeting',
            'Proposed_Date' => now()->addDays(3)->toDateString(),
            'Proposed_Start_Time' => '09:00',
            'Proposed_End_Time' => '11:00',
            'Purpose' => 'Test the boundary',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    expect(Requests::query()->count())->toBe(1);
});
