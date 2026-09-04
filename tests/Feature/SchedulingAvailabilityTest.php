<?php

use App\Models\Facilities;
use App\Models\FacilityBlackout;
use App\Models\Requests;
use App\Models\User;
use App\Services\FacilityAvailabilityService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function schedulingFixture(string $status = 'Approved', string $start = '09:00', string $end = '10:00'): array
{
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::query()->create(['Facility_Name' => 'Scheduling Hall', 'Status' => 'Available']);
    $date = now()->addWeek()->toDateString();
    $request = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $user->id, 'Facility_ID' => $facility->FID,
        'Proposed_Date' => $date, 'Proposed_End_Date' => $date,
        'Proposed_Start_Time' => $start, 'Proposed_End_Time' => $end,
        'Daily_Schedules' => [['date' => $date, 'start' => $start, 'end' => $end]],
        'Status' => $status, 'Purpose' => 'Availability test',
    ]));

    return [$user, $facility, $request, $date];
}

it('generates thirty minute booking slots within operating hours', function () {
    $slots = app(FacilityAvailabilityService::class)->slots();
    expect(array_slice($slots, 0, 3))->toBe(['07:00', '07:30', '08:00'])
        ->and(array_slice($slots, -3))->toBe(['18:00', '18:30', '19:00'])
        ->and(count($slots))->toBe(25);
});

it('blocks approved reservations including preparation and cleanup buffers', function () {
    [, $facility, , $date] = schedulingFixture('Approved');
    $service = app(FacilityAvailabilityService::class);

    expect(fn () => $service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '08:30', 'end' => '09:30']]))
        ->toThrow(ValidationException::class)
        ->and(fn () => $service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '10:00', 'end' => '11:00']]))
        ->toThrow(ValidationException::class);
});

it('returns pending overlaps as warnings without blocking submission', function () {
    [, $facility, , $date] = schedulingFixture('Pending');
    $service = app(FacilityAvailabilityService::class);
    $schedule = [['date' => $date, 'start' => '09:00', 'end' => '10:00']];

    expect($service->validateSchedules($facility->FID, $date, $date, $schedule))->toBe($schedule)
        ->and($service->conflicts($facility->FID, $schedule)->first()['status'])->toBe('pending');
});

it('allows an adjacent booking outside the cleanup buffer and excludes the edited request', function () {
    [, $facility, $request, $date] = schedulingFixture();
    $service = app(FacilityAvailabilityService::class);

    expect($service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '10:30', 'end' => '11:30']]))->toHaveCount(1)
        ->and($service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '09:00', 'end' => '10:00']], $request->RID))->toHaveCount(1);
});

it('enforces hours duration intervals complete date ranges and blackouts', function () {
    [, $facility, , $date] = schedulingFixture('Cancelled');
    $service = app(FacilityAvailabilityService::class);

    expect(fn () => $service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '06:30', 'end' => '08:00']]))->toThrow(ValidationException::class)
        ->and(fn () => $service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '07:15', 'end' => '08:15']]))->toThrow(ValidationException::class)
        ->and(fn () => $service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '07:00', 'end' => '07:30']]))->toThrow(ValidationException::class);

    FacilityBlackout::create(['facility_id' => $facility->FID, 'starts_on' => $date, 'ends_on' => $date, 'reason' => 'Maintenance']);
    expect(fn () => $service->validateSchedules($facility->FID, $date, $date, [['date' => $date, 'start' => '07:00', 'end' => '08:00']]))->toThrow(ValidationException::class);
});

it('returns private scheduling statuses only from an authenticated rate limited endpoint', function () {
    [$user, $facility, , $date] = schedulingFixture();
    $url = route('requests.availability', ['facility' => $facility, 'from' => $date, 'to' => $date]);

    $this->get($url)->assertRedirect(route('login'));
    $response = $this->actingAs($user)->getJson($url)->assertOk()
        ->assertJsonPath("days.{$date}.ranges.0.status", 'approved');
    expect($response->getContent())->not->toContain($user->name)->not->toContain('Availability test');
});

it('toggles facility availability and cancels active requests', function () {
    Notification::fake();
    [, $facility, $request] = schedulingFixture('Approved');
    $service = app(FacilityAvailabilityService::class);

    expect($service->toggle($facility))->toBe(1)
        ->and($facility->fresh()->Status)->toBe('Unavailable')
        ->and($request->fresh()->Status)->toBe('Cancelled')
        ->and($request->fresh()->Cancellation_Reason)->not->toBeEmpty()
        ->and($service->toggle($facility->fresh()))->toBe(0)
        ->and($facility->fresh()->Status)->toBe('Available');
});
