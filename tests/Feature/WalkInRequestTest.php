<?php

use App\Models\Facility;
use App\Models\User;
use App\Services\FacilityAvailabilityService;
use App\Services\RequestSubmissionService;

it('approves a walk-in request and creates its booked schedule immediately', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin']);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Walk-in Test Hall',
        'Status' => 'Available',
    ]);
    $date = today()->addDay()->toDateString();
    $schedules = [['date' => $date, 'start' => '09:00', 'end' => '10:00']];

    $request = app(RequestSubmissionService::class)->submitFacility(
        $facility,
        $administrator,
        [
            'Initial_Status' => 'Approved',
            'Guest_Name' => 'Walk-in Guest',
            'Guest_Organization' => 'Guest Organization',
            'Guest_Email' => 'guest@example.test',
            'Guest_Contact' => '09171234567',
            'Event_Title' => 'Walk-in Reservation',
            'Request_Details' => 'A reservation made at the facility office.',
            'Type_Event' => 'Meeting',
            'Event_Scope' => 'External',
            'Proposed_Date' => $date,
            'Proposed_End_Date' => $date,
            'Purpose' => 'Meeting or Conference',
            'Purpose_Categories' => ['Meeting or Conference'],
            'Capacity' => 10,
        ],
        [],
        $schedules,
        null,
        app(FacilityAvailabilityService::class),
        true,
    );

    expect($request->fresh()->Status)->toBe('Approved')
        ->and($request->fresh()->schedules)->toHaveCount(1)
        ->and($request->fresh()->schedules->first()->Status)->toBe('Booked');
});

it('uses the Request module route after a walk-in request is saved', function () {
    expect(route('requests.index', ['request' => 123]))->toContain('/requests?request=123');
});

it('can create a direct request as pending without reserving its schedule yet', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin']);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Pending Direct Request Hall',
        'Status' => 'Available',
    ]);
    $date = today()->addDays(2)->toDateString();

    $request = app(RequestSubmissionService::class)->submitFacility(
        $facility,
        $administrator,
        [
            'Initial_Status' => 'Pending',
            'Guest_Name' => 'Pending Guest',
            'Event_Title' => 'Pending Direct Reservation',
            'Request_Details' => 'A direct request that remains pending for review.',
            'Type_Event' => 'Meeting',
            'Event_Scope' => 'External',
            'Proposed_Date' => $date,
            'Proposed_End_Date' => $date,
            'Purpose' => 'Meeting or Conference',
            'Purpose_Categories' => ['Meeting or Conference'],
            'Capacity' => 10,
        ],
        [],
        [['date' => $date, 'start' => '09:00', 'end' => '10:00']],
        null,
        app(FacilityAvailabilityService::class),
        true,
    );

    expect($request->fresh()->Status)->toBe('Pending')
        ->and($request->fresh()->schedules)->toHaveCount(0);
});

it('always keeps an end-user facility request pending', function () {
    $user = User::factory()->create(['user_type' => 'user']);
    $facility = Facility::query()->create([
        'Facility_Name' => 'End-user Request Hall',
        'Status' => 'Available',
    ]);
    $date = today()->addDays(3)->toDateString();

    $request = app(RequestSubmissionService::class)->submitFacility(
        $facility,
        $user,
        [
            'Initial_Status' => 'Approved',
            'Event_Title' => 'End-user Reservation',
            'Request_Details' => 'A regular user request awaiting administrator review.',
            'Type_Event' => 'Meeting',
            'Event_Scope' => 'Internal',
            'Proposed_Date' => $date,
            'Proposed_End_Date' => $date,
            'Purpose' => 'Meeting or Conference',
            'Purpose_Categories' => ['Meeting or Conference'],
            'Capacity' => 10,
        ],
        [],
        [['date' => $date, 'start' => '09:00', 'end' => '10:00']],
        null,
        app(FacilityAvailabilityService::class),
        false,
    );

    expect($request->fresh()->Status)->toBe('Pending')
        ->and($request->fresh()->schedules)->toHaveCount(0);
});

it('stores a super-admin historical entry as completed with a calendar schedule', function () {
    $administrator = User::factory()->create(['user_type' => 'super_admin']);
    $facility = Facility::query()->create(['Facility_Name' => 'History Hall', 'Status' => 'Available']);
    $date = today()->subMonth()->toDateString();

    $request = app(RequestSubmissionService::class)->submitFacility(
        $facility, $administrator,
        [
            'Initial_Status' => 'Ended', 'Guest_Name' => 'Historical Guest',
            'Event_Title' => 'Completed Historical Event', 'Request_Details' => 'A completed event recorded for calendar history.',
            'Type_Event' => 'Meeting', 'Event_Scope' => 'Internal',
            'Proposed_Date' => $date, 'Proposed_End_Date' => $date,
            'Purpose' => 'Meeting or Conference', 'Purpose_Categories' => ['Meeting or Conference'], 'Capacity' => 10,
        ],
        [], [['date' => $date, 'start' => '09:00', 'end' => '10:00']], null,
        app(FacilityAvailabilityService::class), true,
    );

    expect($request->fresh()->Status)->toBe('Ended')
        ->and($request->fresh()->schedules)->toHaveCount(1);
});
