<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('submitting a facility request creates an event record and links it to the request', function () {
    $user = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Test Facility',
        'Price' => 100,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);

    actingAs($user);

    $response = $this->post(route('requests.store', $facility), [
        'Event_Title' => 'Launch Event',
        'Description' => 'A test event',
        'Type_Event' => 'Conference',
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Purpose' => 'Testing',
    ]);

    $response->assertRedirect();

    $event = \App\Models\Events::where('Event_Title', 'Launch Event')->first();
    $request = Requests::where('User_ID', $user->id)->latest('RID')->first();

    expect($event)->not->toBeNull()
        ->and($request)->not->toBeNull()
        ->and($request->Event_ID)->toBe($event->EID);
});

test('a custom other event type is required and recorded', function () {
    $user = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Custom Event Facility',
        'Price' => 100,
        'Status' => 'Available',
    ]);

    actingAs($user);

    $this->post(route('requests.store', $facility), [
        'Event_Title' => 'Recognition Day',
        'Type_Event' => 'Other',
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Purpose' => 'Student recognition',
    ])->assertSessionHasErrors('Other_Event_Type');

    $this->post(route('requests.store', $facility), [
        'Event_Title' => 'Recognition Day',
        'Type_Event' => 'Other',
        'Other_Event_Type' => 'Recognition Ceremony',
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Purpose' => 'Student recognition',
    ])->assertRedirect();

    expect(\App\Models\Events::where('Event_Title', 'Recognition Day')->sole()->Type_Event)
        ->toBe('Recognition Ceremony');
});
