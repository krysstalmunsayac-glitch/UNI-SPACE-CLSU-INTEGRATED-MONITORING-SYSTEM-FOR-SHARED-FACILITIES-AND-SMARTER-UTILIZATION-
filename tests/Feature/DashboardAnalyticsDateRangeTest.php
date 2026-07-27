<?php

use App\Models\Facilities;
use App\Models\Events;
use App\Models\Requests;
use App\Models\User;

function createAnalyticsRequest(User $requester, Facilities $facility, string $createdAt, string $eventType = 'Seminar'): Requests
{
    $event = Events::create([
        'Event_Title' => $eventType.' analytics event',
        'Type_Event' => $eventType,
        'Status' => 'Upcoming',
    ]);

    $request = Requests::create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Event_ID' => $event->EID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Approved',
        'Purpose' => 'Dashboard analytics test',
        'Capacity' => 25,
    ]);

    $request->forceFill(['Created_at' => $createdAt])->saveQuietly();

    return $request;
}

test('super admin request analytics use the selected date range and show facility type usage', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $requester = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::create([
        'Facility_Name' => 'Analytics Gym',
        'Price' => 0,
        'facility_type' => 'sports',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);

    createAnalyticsRequest($requester, $facility, '2026-07-10 10:00:00');

    $this->actingAs($superAdmin)
        ->get(route('dashboard.superadmin', ['date_from' => '2026-07-01', 'date_to' => '2026-07-15']))
        ->assertOk()
        ->assertSee('Request Analytics')
        ->assertSee('Most Used Facility Types')
        ->assertSee('Most Used Event Types')
        ->assertSee('Seminar')
        ->assertSee('Analytics Gym');
});

test('office admin analytics include only assigned facilities in the selected range', function () {
    $officeAdmin = User::factory()->create(['user_type' => 'admin']);
    $requester = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::create([
        'Facility_Name' => 'Assigned Conference Room',
        'Price' => 0,
        'facility_type' => 'conference',
        'Capacity' => 60,
        'Status' => 'Available',
    ]);
    $facility->assignedAdmins()->attach($officeAdmin->id);

    createAnalyticsRequest($requester, $facility, '2026-07-10 10:00:00');
    $unassignedFacility = Facilities::create([
        'Facility_Name' => 'Unassigned Analytics Hall',
        'Price' => 0,
        'facility_type' => 'auditorium',
        'Status' => 'Available',
    ]);
    createAnalyticsRequest($requester, $unassignedFacility, '2026-07-10 11:00:00', 'Workshop');

    $this->actingAs($officeAdmin)
        ->get(route('dashboard.officeadmin', ['date_from' => '2026-07-01', 'date_to' => '2026-07-15']))
        ->assertOk()
        ->assertSee('Request Analytics')
        ->assertSee('Most Used Facility Types')
        ->assertSee('Most Used Event Types')
        ->assertSee('Seminar')
        ->assertDontSee('Workshop')
        ->assertSee('Assigned Conference Room');
});
