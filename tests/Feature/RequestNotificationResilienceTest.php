<?php

use App\Actions\Requests\EndWaitingRequest;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RequestWorkflowService;
use App\Services\ScheduleManagementService;
use Illuminate\Support\Facades\Notification;

function notificationResilienceRequest(string $status, string $date, User $user, Facility $facility): FacilityRequest
{
    return FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Daily_Schedules' => [[
            'date' => $date,
            'start' => '09:00',
            'end' => '11:00',
        ]],
        'Status' => $status,
        'Purpose' => 'Notification resilience test',
    ]);
}

it('keeps approval successful when its notification cannot be delivered', function () {
    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Approval Notification Hall',
        'Status' => 'Available',
    ]);
    $request = notificationResilienceRequest('Pending', today()->addDays(4)->toDateString(), $user, $facility);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Mail service unavailable'));

    $result = app(RequestWorkflowService::class)->approve($request);

    expect($result)->not->toBeNull()
        ->and($request->fresh()->Status)->toBe('Approved')
        ->and($request->schedules()->count())->toBe(1);
});

it('keeps a payment request successful when its notification cannot be delivered', function () {
    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Payment Notification Hall',
        'Status' => 'Available',
    ]);
    $request = notificationResilienceRequest('Pending', today()->addDays(4)->toDateString(), $user, $facility);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Mail service unavailable'));

    app(RequestWorkflowService::class)->requestPayment($request, [
        'paymentAmount' => 1500,
        'paymentDeadline' => now()->addDay(),
    ]);

    expect($request->fresh()->Status)->toBe('Awaiting Payment')
        ->and($request->fresh()->Payment_Amount)->toBe('1500.00');
});

it('keeps a schedule update successful when its notification cannot be delivered', function () {
    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Schedule Notification Hall',
        'Status' => 'Available',
    ]);
    $request = notificationResilienceRequest('Approved', today()->addDays(4)->toDateString(), $user, $facility);
    $schedule = Schedule::query()->create([
        'Request_ID' => $request->RID,
        'Date' => $request->Proposed_Date->toDateString(),
        'Start_Time' => '09:00',
        'End_Time' => '11:00',
        'Status' => 'Booked',
    ]);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Mail service unavailable'));

    $changed = app(ScheduleManagementService::class)->update($schedule, $request, [
        'Request_ID' => $request->RID,
        'Date' => $request->Proposed_Date->toDateString(),
        'Start_Time' => '12:00',
        'End_Time' => '13:00',
        'Status' => 'Booked',
    ]);

    expect($changed)->toBeTrue()
        ->and($schedule->fresh()->Start_Time->format('H:i'))->toBe('12:00')
        ->and($schedule->fresh()->End_Time->format('H:i'))->toBe('13:00');
});

it('still archives an automatically completed request when feedback delivery fails', function () {
    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Automatic Completion Hall',
        'Status' => 'Available',
    ]);
    $request = notificationResilienceRequest('Approved', today()->subDay()->toDateString(), $user, $facility);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Mail service unavailable'));

    expect(FacilityRequest::markPastRequestsAsEnded())->toBe(1)
        ->and(FacilityRequest::withTrashed()->findOrFail($request->RID)->Status)->toBe('Ended');

    $this->assertSoftDeleted('requests', ['RID' => $request->RID]);
});

it('still expires and archives a pending request when status delivery fails', function () {
    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Expiration Notification Hall',
        'Status' => 'Available',
    ]);
    $request = notificationResilienceRequest('Pending', today()->subDay()->toDateString(), $user, $facility);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Mail service unavailable'));

    expect(FacilityRequest::markPastRequestsAsEnded())->toBe(1)
        ->and(FacilityRequest::withTrashed()->findOrFail($request->RID)->Status)->toBe('Expired');

    $this->assertSoftDeleted('requests', ['RID' => $request->RID]);
});

it('still archives a manually completed request when feedback delivery fails', function () {
    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Manual Completion Hall',
        'Status' => 'Available',
    ]);
    $request = notificationResilienceRequest('Approved', today()->toDateString(), $user, $facility);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Mail service unavailable'));

    app(EndWaitingRequest::class)->handle($request);

    expect(FacilityRequest::withTrashed()->findOrFail($request->RID)->Status)->toBe('Ended');
    $this->assertSoftDeleted('requests', ['RID' => $request->RID]);
});
