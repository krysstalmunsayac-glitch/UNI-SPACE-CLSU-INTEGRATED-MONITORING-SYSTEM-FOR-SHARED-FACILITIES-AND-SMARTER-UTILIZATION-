<?php

use App\Models\AuditLog;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

function guestRequestPayload(string $date): array
{
    return [
        'Guest_Name' => 'Dr. Maria Santos',
        'Guest_Organization' => 'Partner University',
        'Guest_Email' => 'maria@example.org',
        'Guest_Contact' => '+63 917 555 0100',
        'Event_Title' => 'Partner Delegation Meeting',
        'Description' => 'A coordination meeting for the visiting partner delegation.',
        'Type_Event' => 'Meeting',
        'Event_Scope' => 'External',
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Daily_Schedules' => [['date' => $date, 'start' => '09:00', 'end' => '10:00']],
        'Purpose_Categories' => ['Meeting or Conference'],
        'Reservation_Frequency' => 'First time',
        'Facility_Importance' => 'Very Important',
        'Requirements_Fit' => 'Yes, completely',
        'Reserve_Again_Intent' => 'Definitely Yes',
        'Capacity' => 12,
    ];
}

it('lets an office admin request only an assigned available facility', function () {
    $admin = User::factory()->create(['user_type' => 'admin', 'is_active' => true]);
    $assigned = Facilities::create(['Facility_Name' => 'Assigned Hall', 'Status' => 'Available', 'Capacity' => 50]);
    $unassigned = Facilities::create(['Facility_Name' => 'Other Hall', 'Status' => 'Available', 'Capacity' => 50]);
    $admin->facilities()->attach($assigned->FID);

    $this->actingAs($admin);

    $this->get(route('Request'))
        ->assertOk()
        ->assertDontSee('Request Facility');

    $this
        ->get(route('admin.requests.create', $assigned))
        ->assertOk()
        ->assertSee('Guest facility reservation')
        ->assertSee('Guest name');

    $this->get(route('admin.requests.create', $unassigned))->assertForbidden();
    $this->post(route('admin.requests.store', $unassigned), guestRequestPayload(today()->addDays(5)->toDateString()))
        ->assertForbidden();
});

it('lets a super admin request any available facility on behalf of a guest', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'VIP Hall', 'Status' => 'Available', 'Capacity' => 100]);
    $date = today()->addDays(5)->toDateString();

    $this->actingAs($admin);

    $facilityPage = $this->get(route('Facility.SuperAdmin'))
        ->assertOk()
        ->assertSee('Request Facility')
        ->assertSee('VIP Hall');
    expect(substr_count($facilityPage->getContent(), 'Request Facility'))->toBe(1);

    $this->get(route('Request'))
        ->assertOk()
        ->assertDontSee('Request Facility');

    $this
        ->post(route('admin.requests.store', $facility), guestRequestPayload($date))
        ->assertRedirect(route('Request', ['request' => 1]));

    $request = Requests::with(['event', 'creator'])->firstOrFail();
    expect($request->Is_Guest_Booking)->toBeTrue()
        ->and($request->User_ID)->toBeNull()
        ->and($request->Guest_Name)->toBe('Dr. Maria Santos')
        ->and($request->Guest_Organization)->toBe('Partner University')
        ->and($request->Guest_Email)->toBe('maria@example.org')
        ->and($request->Guest_Contact)->toBe('+63 917 555 0100')
        ->and($request->Created_By)->toBe($admin->id)
        ->and($request->creator->is($admin))->toBeTrue()
        ->and($request->Status)->toBe('Pending')
        ->and($request->event->Event_Title)->toBe('Partner Delegation Meeting');

    expect(AuditLog::where('auditable_id', $request->RID)
        ->where('actor_id', $admin->id)
        ->where('action', 'request_submitted')
        ->exists())->toBeTrue();
});

it('prevents end users from accessing the administrative guest request flow', function () {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'Restricted Hall', 'Status' => 'Available']);

    $this->actingAs($user)->get(route('admin.requests.create', $facility))->assertForbidden();
    $this->post(route('admin.requests.store', $facility), guestRequestPayload(today()->addDays(5)->toDateString()))
        ->assertForbidden();
});

it('applies existing approved-booking conflict validation to guest requests', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'Busy Hall', 'Status' => 'Available', 'Capacity' => 100]);
    $date = today()->addDays(5)->toDateString();
    Requests::withoutEvents(fn () => Requests::create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Daily_Schedules' => [['date' => $date, 'start' => '09:00', 'end' => '10:00']],
        'Status' => 'Approved',
        'Purpose' => 'Existing approved booking',
    ]));

    $this->actingAs($admin)
        ->post(route('admin.requests.store', $facility), guestRequestPayload($date))
        ->assertSessionHasErrors('Daily_Schedules');

    expect(Requests::count())->toBe(1);
});

it('accepts a booking that ends at midnight', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'Evening Hall', 'Status' => 'Available', 'Capacity' => 100]);
    $date = today()->addDays(5)->toDateString();
    $payload = guestRequestPayload($date);
    $payload['Daily_Schedules'] = [['date' => $date, 'start' => '23:00', 'end' => '24:00']];

    $this->actingAs($admin)
        ->post(route('admin.requests.store', $facility), $payload)
        ->assertRedirect(route('Request', ['request' => 1]));

    $request = Requests::firstOrFail();
    expect($request->Daily_Schedules[0])->toMatchArray(['start' => '23:00', 'end' => '24:00'])
        ->and(substr((string) $request->getRawOriginal('Proposed_End_Time'), 0, 5))->toBe('24:00');

    Volt::test('request.request')->call('approve', $request->RID)->assertHasNoErrors();
    $schedule = Schedule::firstOrFail();
    expect(substr((string) $schedule->getRawOriginal('End_Time'), 0, 5))->toBe('24:00');
});
