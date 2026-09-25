<?php

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\FacilityAvailabilityService;
use App\Services\RequestWorkflowService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function paymentReservationRequest(
    User $user,
    Facility $facility,
    string $status,
    string $date,
    string $start = '09:00',
    string $end = '11:00',
): FacilityRequest {
    return FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Proposed_Start_Time' => $start,
        'Proposed_End_Time' => $end,
        'Daily_Schedules' => [['date' => $date, 'start' => $start, 'end' => $end]],
        'Status' => $status,
        'Payment_Amount' => $status === 'Awaiting Payment' ? 1500 : null,
        'Payment_Deadline' => $status === 'Awaiting Payment' ? now()->addDay() : null,
        'Purpose' => 'Payment reservation test',
    ]);
}

it('reserves a facility slot while payment is pending', function () {
    Notification::fake();

    $facility = Facility::query()->create([
        'Facility_Name' => 'Reserved Payment Hall',
        'Status' => 'Available',
    ]);
    $date = today()->addDays(4)->toDateString();
    $payingUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $awaitingPayment = paymentReservationRequest($payingUser, $facility, 'Awaiting Payment', $date);
    $pending = paymentReservationRequest($otherUser, $facility, 'Pending', $date);
    $schedule = [['date' => $date, 'start' => '09:00', 'end' => '11:00']];

    expect(app(FacilityAvailabilityService::class)->conflicts(
        $facility->FID,
        $schedule,
        $pending->RID,
        false,
        ['Awaiting Payment', 'Approved'],
    )->pluck('request_id')->all())->toBe([$awaitingPayment->RID]);

    expect(app(RequestWorkflowService::class)->approve($pending))->toBeNull();
    expect($pending->fresh()->Status)->toBe('Pending');

    expect(fn () => app(RequestWorkflowService::class)->requestPayment($pending, [
        'paymentAmount' => 1500,
        'paymentDeadline' => now()->addDay(),
    ]))->toThrow(ValidationException::class, 'already reserved');

    expect(fn () => app(FacilityAvailabilityService::class)->validateSchedules(
        $facility->FID,
        $date,
        $date,
        $schedule,
    ))->toThrow(ValidationException::class, 'already booked');
});

it('requires payment deadlines to fall before the event starts', function () {
    Notification::fake();

    $facility = Facility::query()->create([
        'Facility_Name' => 'Deadline Validation Hall',
        'Status' => 'Available',
    ]);
    $date = today()->addDays(4)->toDateString();
    $request = paymentReservationRequest(User::factory()->create(), $facility, 'Pending', $date);

    expect(fn () => app(RequestWorkflowService::class)->requestPayment($request, [
        'paymentAmount' => 1500,
        'paymentDeadline' => $request->scheduledStartAt()->addMinute(),
    ]))->toThrow(ValidationException::class, 'before the event starts');

    expect($request->fresh()->Status)->toBe('Pending')
        ->and($request->fresh()->Payment_Deadline)->toBeNull();
});
