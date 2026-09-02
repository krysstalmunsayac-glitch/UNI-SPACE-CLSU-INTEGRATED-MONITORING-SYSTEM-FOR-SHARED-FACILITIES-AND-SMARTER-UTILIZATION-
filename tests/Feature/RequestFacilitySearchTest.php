<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Livewire\Volt\Volt;

it('searches administrative requests by facility name', function () {
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $matchedFacility = Facilities::query()->create(['Facility_Name' => 'Research Auditorium']);
    $otherFacility = Facilities::query()->create(['Facility_Name' => 'Sports Gymnasium']);

    foreach ([$matchedFacility, $otherFacility] as $facility) {
        Requests::withoutEvents(fn () => Requests::query()->create([
            'User_ID' => $requester->id,
            'Facility_ID' => $facility->FID,
            'Proposed_Date' => now()->addWeek()->toDateString(),
            'Proposed_End_Date' => now()->addWeek()->toDateString(),
            'Proposed_Start_Time' => '08:00',
            'Proposed_End_Time' => '10:00',
            'Status' => 'Pending',
            'Purpose' => 'Facility search test',
        ]));
    }

    $this->actingAs($administrator);

    Volt::test('request.request')
        ->set('searchInput', 'Research Auditorium')
        ->call('applySearch')
        ->assertSee('Research Auditorium')
        ->assertDontSee('Sports Gymnasium');
});
