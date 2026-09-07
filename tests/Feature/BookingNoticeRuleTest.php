<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingPolicy;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->travelTo(now()->startOfDay()->setTime(6, 0));
});

it('uses the Manila date after local midnight while UTC is still on the previous day', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-09-07 17:13:00', 'UTC'));
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);

    expect(config('app.timezone'))->toBe('Asia/Manila')
        ->and(now()->format('Y-m-d H:i'))->toBe('2026-09-08 01:13')
        ->and(app(BookingPolicy::class)->earliestDate($superAdmin))->toBe('2026-09-08')
        ->and(app(BookingPolicy::class)->earliestDate())->toBe('2026-09-11');

    expect(fn () => app(BookingPolicy::class)->validateFutureStart('2026-09-07', '10:00', 'Start_Time'))
        ->toThrow(ValidationException::class);
});

it('defaults to three days and persists a super admin change across sessions', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    expect(app(BookingPolicy::class)->noticeDays())->toBe(3);
    $this->actingAs($superAdmin);
    Volt::test('schedule.schedule')->set('noticeDays', 2)->call('saveBookingRules')->assertHasNoErrors();
    Volt::test('schedule.schedule')->assertSet('noticeDays', 2);
    expect(app(BookingPolicy::class)->earliestDate())->toBe(today()->addDays(2)->toDateString());
});

it('prevents other roles from changing the booking rule', function (string $role) {
    $this->actingAs(User::factory()->create(['user_type' => $role]));
    Volt::test('schedule.schedule')->assertDontSee('Save booking rule')
        ->set('noticeDays', 0)->call('saveBookingRules')->assertForbidden();
    expect(app(BookingPolicy::class)->noticeDays())->toBe(3);
})->with(['admin', 'user']);

it('rejects invalid notice periods without changing the saved rule', function ($days) {
    $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
    Volt::test('schedule.schedule')->set('noticeDays', $days)->call('saveBookingRules')->assertHasErrors('noticeDays');
    expect(app(BookingPolicy::class)->noticeDays())->toBe(3);
})->with([-1, 366, 1.5, 'invalid', '']);

it('enforces the saved notice period in availability and reservation forms', function () {
    $policy = app(BookingPolicy::class);
    $policy->saveNoticeDays(User::factory()->create(['user_type' => 'super_admin']), 2);
    $this->actingAs(User::factory()->create(['user_type' => 'user', 'is_active' => true]));
    $facility = Facilities::create(['Facility_Name' => 'Notice Hall', 'Status' => 'Available']);
    $date = today()->addDays(2)->toDateString();
    $this->getJson(route('requests.availability', ['facility' => $facility, 'from' => $date, 'to' => $date]))->assertOk();
    $tomorrow = today()->addDay()->toDateString();
    $this->getJson(route('requests.availability', ['facility' => $facility, 'from' => $tomorrow, 'to' => $tomorrow]))
        ->assertUnprocessable()->assertJsonValidationErrors('from');
    $this->get(route('requests.create', $facility))->assertOk()
        ->assertSee('min="'.$date.'"', false)->assertSee('Bookings require at least 2 days');
    $this->postJson(route('requests.store', $facility), ['Proposed_Date' => $tomorrow])
        ->assertUnprocessable()->assertJsonValidationErrors('Proposed_Date');
    $this->postJson(route('requests.store', $facility), ['Proposed_Date' => $date])
        ->assertUnprocessable()->assertJsonMissingValidationErrors('Proposed_Date');
});

it('allows super admins inside the notice period while enforcing it for office admins', function (string $role, int $days, bool $allowed) {
    $admin = User::factory()->create(['user_type' => $role]);
    $this->actingAs($admin);
    app(BookingPolicy::class)->saveNoticeDays(User::factory()->create(['user_type' => 'super_admin']), 2);
    $facility = Facilities::create(['Facility_Name' => 'Schedule Notice Hall']);
    $admin->facilities()->attach($facility->FID);
    $request = Requests::create([
        'Facility_ID' => $facility->FID, 'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '10:00', 'Proposed_End_Time' => '11:00', 'Status' => 'Approved',
        'Purpose' => 'Booking notice test',
    ]);
    $schedule = Schedule::create([
        'Request_ID' => $request->RID, 'Date' => today()->addWeek()->toDateString(),
        'Start_Time' => '10:00', 'End_Time' => '11:00', 'Status' => 'Booked',
    ]);
    $date = today()->addDays($days)->toDateString();
    $component = Volt::test('schedule.schedule')->call('edit', $schedule->SID)->set('Date', $date)->call('save');
    if ($allowed) {
        $component->assertHasNoErrors();
        expect($schedule->fresh()->Date->toDateString())->toBe($date);
    } else {
        $component->assertHasErrors('Date');
        expect($schedule->fresh()->Date->toDateString())->toBe(today()->addWeek()->toDateString());
    }
})->with([
    ['super_admin', 0, true], ['super_admin', 1, true], ['super_admin', -1, false],
    ['admin', 1, false], ['admin', 2, true],
]);

it('allows zero days but rejects an already elapsed start time', function () {
    $policy = app(BookingPolicy::class);
    $policy->saveNoticeDays(User::factory()->create(['user_type' => 'super_admin']), 0);
    expect($policy->earliestDate())->toBe(today()->toDateString());
    $policy->validateFutureStart(today()->toDateString(), '07:00', 'Start_Time');
    expect(fn () => $policy->validateFutureStart(today()->toDateString(), '06:00', 'Start_Time'))
        ->toThrow(ValidationException::class);
});
