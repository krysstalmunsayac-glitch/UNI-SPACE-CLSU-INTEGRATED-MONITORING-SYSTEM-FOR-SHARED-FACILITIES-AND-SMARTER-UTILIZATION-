<?php

use App\Actions\Requests\RescheduleWaitingRequest;
use App\Models\Amenity;
use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\BookingRequestValidator;
use App\Services\FacilityAvailabilityService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

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
            'Request_Details' => '-----',
        ])
        ->assertSessionHasErrors(['Event_Title', 'Purpose_Categories', 'Request_Details', 'Capacity']);
});

it('shows the facility attendee capacity and request letter requirements', function () {
    $facility = Facility::query()->create([
        'Facility_Name' => 'Capacity Hall',
        'Capacity' => 250,
        'Status' => 'Available',
    ]);

    $this->actingAs(requestFormUser())
        ->get(route('requests.create', ['facilitySlug' => $facility->slug]))
        ->assertOk()
        ->assertSee('Facility capacity: 250 people')
        ->assertSee('This facility can accommodate up to 250 attendees.')
        ->assertSee('Choose a PDF request letter')
        ->assertSee('PDF only, maximum file size 5 MB');
});

it('uses a clean facility slug in the booking URL and redirects legacy links', function () {
    $facility = Facility::query()->create([
        'Facility_Name' => 'Alumni Social Hall',
        'Status' => 'Available',
    ]);
    $user = requestFormUser();
    $cleanUrl = route('requests.create', ['facilitySlug' => $facility->slug]);

    expect($facility->slug)->toBe('alumni-social-hall')
        ->and($cleanUrl)->toContain('/reserve/alumni-social-hall')
        ->and($cleanUrl)->not->toContain('/requests/create/')
        ->and($cleanUrl)->not->toEndWith('/'.$facility->FID);

    $this->actingAs($user)
        ->get(route('requests.create.legacy', $facility))
        ->assertRedirect($cleanUrl);
});

it('stores purpose of request and request details separately', function () {
    Notification::fake();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Two Field Hall',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);
    $date = today()->addDays(4)->toDateString();

    $this->actingAs(requestFormUser())
        ->post(route('requests.store', $facility), [
            'Event_Title' => 'Research Colloquium',
            'Type_Event' => 'Conference',
            'Event_Scope' => 'Internal',
            'Purpose_Categories' => ['Meeting or Conference', 'Other'],
            'Other_Purpose' => 'Research presentation',
            'Request_Details' => 'The room needs a presentation area and seating arranged before the program.',
            'Proposed_Date' => $date,
            'Proposed_End_Date' => $date,
            'Daily_Schedules' => [[
                'date' => $date,
                'start' => '09:00',
                'end' => '11:00',
            ]],
            'Capacity' => 50,
        ])
        ->assertRedirect(route('dashboard'));

    $stored = FacilityRequest::query()->latest('RID')->firstOrFail();

    expect($stored->Purpose)->toBe('Meeting or Conference, Research presentation')
        ->and($stored->Purpose_Categories)->toBe(['Meeting or Conference', 'Other'])
        ->and($stored->Other_Purpose)->toBe('Research presentation')
        ->and($stored->Request_Details)->toBe('The room needs a presentation area and seating arranged before the program.')
        ->and($stored->event?->Description)->toBe($stored->Request_Details);
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

it('keeps amenity units unchanged across overlapping bookings and filters by status', function () {
    $date = today()->addDays(4)->toDateString();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Amenity Test Hall',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);
    $amenity = Amenity::query()->create([
        'name' => 'Portable Chairs',
        'Description' => 'Additional chairs.',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 10,
    ]);
    $unavailableAmenity = Amenity::query()->create([
        'name' => 'Unavailable Projector',
        'Description' => 'Temporarily unavailable.',
        'Status' => 'Unavailable',
        'inventory_type' => 'countable',
        'inventory_quantity' => 2,
    ]);
    $facility->amenities()->attach([$amenity->AID, $unavailableAmenity->AID]);
    $request = FacilityRequest::query()->create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '17:00',
        'Daily_Schedules' => [['date' => $date, 'start' => '13:00', 'end' => '15:00']],
        'Status' => 'Approved',
        'Purpose' => 'Testing inventory availability',
    ]);
    $request->amenities()->attach($amenity->AID, ['quantity' => 4]);

    app(BookingRequestValidator::class)->validateAmenityAvailability(
        [$amenity->AID => 10],
        $date,
        $date,
        '14:00',
        '16:00',
    );

    $this->actingAs(requestFormUser())
        ->getJson(route('requests.availability', [
            'facility' => $facility,
            'from' => $date,
            'to' => $date,
            'schedules' => [[
                'date' => $date,
                'start' => '14:00',
                'end' => '16:00',
            ]],
        ]))
        ->assertOk()
        ->assertJsonPath("amenities.{$amenity->AID}", 10)
        ->assertJsonMissingPath("amenities.{$unavailableAmenity->AID}");
});

it('keeps awaiting payment requests read only against direct update posts', function () {
    $user = requestFormUser();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Payment Guard Hall',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);
    $originalDate = today()->addDays(4)->toDateString();
    $attemptedDate = today()->addDays(5)->toDateString();
    $paymentDeadline = now()->addDays(2)->startOfMinute();

    $facilityRequest = FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $originalDate,
        'Proposed_End_Date' => $originalDate,
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Daily_Schedules' => [[
            'date' => $originalDate,
            'start' => '09:00',
            'end' => '11:00',
        ]],
        'Status' => 'Awaiting Payment',
        'Payment_Amount' => 2500,
        'Payment_Deadline' => $paymentDeadline,
        'Payment_Proof_Path' => 'payment-proofs/original.pdf',
        'Payment_Proof_Uploaded_At' => now(),
        'Purpose' => 'Original purpose',
        'Request_Details' => 'Original request details',
        'Purpose_Categories' => ['Meeting or Conference'],
        'Capacity' => 25,
    ]);

    $this->actingAs($user)
        ->post(route('requests.waiting.update', $facilityRequest), [
            'Request_Details' => 'Attempted changed request details',
            'Proposed_Date' => $attemptedDate,
            'Proposed_End_Date' => $attemptedDate,
            'Proposed_Start_Time' => '13:00',
            'Proposed_End_Time' => '15:00',
            'Purpose_Categories' => ['Training Session'],
            'Capacity' => 50,
        ])
        ->assertRedirect(route('dashboard', ['request' => $facilityRequest->RID]))
        ->assertSessionHas('warning', 'This request is awaiting payment. Its submitted information is read-only.');

    $facilityRequest->refresh();

    expect($facilityRequest->Status)->toBe('Awaiting Payment')
        ->and($facilityRequest->Proposed_Date->toDateString())->toBe($originalDate)
        ->and($facilityRequest->Proposed_Start_Time->format('H:i'))->toBe('09:00')
        ->and($facilityRequest->Purpose)->toBe('Original purpose')
        ->and($facilityRequest->Request_Details)->toBe('Original request details')
        ->and($facilityRequest->Payment_Amount)->toBe('2500.00')
        ->and($facilityRequest->Payment_Deadline->equalTo($paymentDeadline))->toBeTrue()
        ->and($facilityRequest->Payment_Proof_Path)->toBe('payment-proofs/original.pdf');

    expect(fn () => app(RescheduleWaitingRequest::class)->handle(
        $facilityRequest,
        $user->id,
        [],
        [],
        [],
        null,
        app(FacilityAvailabilityService::class),
    ))->toThrow(ValidationException::class, 'This request is read-only and can no longer be changed.');
});
