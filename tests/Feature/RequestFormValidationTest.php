<?php

use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;

function requestFormUser(): User
{
    return User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'student',
        'is_active' => true,
    ]);
}

it('renders the separate event request form', function () {
    $event = Event::query()->create([
        'Event_Title' => 'Research Forum',
        'Description' => 'A research forum.',
        'Type_Event' => 'Conference',
    ]);

    $this->actingAs(requestFormUser())
        ->get(route('requests.event.create', $event))
        ->assertOk()
        ->assertSee('Research Forum')
        ->assertSee('Expected Number of Attendees');
});

it('rejects malformed event details and a missing attendee count', function () {
    $facility = Facility::query()->create([
        'Facility_Name' => 'Validation Hall',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);

    $this->actingAs(requestFormUser())
        ->post(route('requests.store', $facility), [
            'Event_Title' => '!!!',
            'Description' => '-----',
        ])
        ->assertSessionHasErrors(['Event_Title', 'Description', 'Capacity']);
});

it('rejects event request times outside booking hours', function () {
    $event = Event::query()->create([
        'Event_Title' => 'Research Forum',
        'Description' => 'A research forum.',
        'Type_Event' => 'Conference',
    ]);
    $date = today()->addDays(4)->toDateString();

    $this->actingAs(requestFormUser())
        ->post(route('requests.event.store', $event), [
            'Proposed_Date' => $date,
            'Proposed_End_Date' => $date,
            'Proposed_Start_Time' => '02:00',
            'Proposed_End_Time' => '03:00',
            'Purpose' => 'Research presentation',
            'Capacity' => 20,
        ])
        ->assertSessionHasErrors(['Proposed_Start_Time', 'Proposed_End_Time']);
});

it('allows up to three active requests on the same event date', function () {
    $user = requestFormUser();
    $date = today()->addDays(4)->toDateString();

    foreach (range(1, 2) as $number) {
        FacilityRequest::query()->create([
            'User_ID' => $user->id,
            'Proposed_Date' => $date,
            'Proposed_End_Date' => $date,
            'Proposed_Start_Time' => '09:00',
            'Proposed_End_Time' => '10:00',
            'Status' => 'Pending',
            'Purpose' => "Request {$number}",
        ]);
    }

    expect(FacilityRequest::userReachedRequestLimitOnDate($user->id, $date))->toBeFalse();

    FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Proposed_Start_Time' => '11:00',
        'Proposed_End_Time' => '12:00',
        'Status' => 'Pending',
        'Purpose' => 'Request 3',
    ]);

    expect(FacilityRequest::userReachedRequestLimitOnDate($user->id, $date))->toBeTrue();
});
