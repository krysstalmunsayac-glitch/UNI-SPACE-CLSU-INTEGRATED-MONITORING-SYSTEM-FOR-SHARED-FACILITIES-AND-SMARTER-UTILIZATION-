<?php

use App\Actions\Requests\EndWaitingRequest;
use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Notifications\RequestFeedbackRequested;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('expires pending requests whose event time passed while ending accepted requests', function () {
    Notification::fake();
    $this->travelTo(now()->setDate(2026, 9, 25)->setTime(12, 0));

    $user = User::factory()->create();
    $facility = Facility::query()->create([
        'Facility_Name' => 'Lifecycle Test Hall',
        'Status' => 'Available',
    ]);

    $makeRequest = fn (string $status, string $start, string $end): FacilityRequest => FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => '2026-09-25',
        'Proposed_Start_Time' => $start,
        'Proposed_End_Time' => $end,
        'Status' => $status,
        'Purpose' => 'Request lifecycle test',
    ]);

    $pastPending = $makeRequest('Pending', '09:00', '10:00');
    $justPassedPending = $makeRequest('Pending', '11:00', '12:00');
    $futurePending = $makeRequest('Pending', '13:00', '14:00');
    $pastAwaitingPayment = $makeRequest('Awaiting Payment', '08:00', '09:00');
    $pastApproved = $makeRequest('Approved', '07:00', '08:00');

    expect(FacilityRequest::markPastRequestsAsEnded())->toBe(4)
        ->and(FacilityRequest::withTrashed()->findOrFail($pastPending->RID)->Status)->toBe('Expired')
        ->and(FacilityRequest::withTrashed()->findOrFail($justPassedPending->RID)->Status)->toBe('Expired')
        ->and(FacilityRequest::withTrashed()->findOrFail($pastAwaitingPayment->RID)->Status)->toBe('Expired')
        ->and(FacilityRequest::withTrashed()->findOrFail($pastApproved->RID)->Status)->toBe('Ended')
        ->and($futurePending->fresh()->Status)->toBe('Pending')
        ->and(FacilityRequest::allowedTransitionsFrom('Pending'))->not->toContain('Expired')
        ->and(FacilityRequest::allowedTransitionsFrom('Expired'))->toBe([]);

    $this->assertSoftDeleted('requests', ['RID' => $pastPending->RID]);
    $this->assertSoftDeleted('requests', ['RID' => $justPassedPending->RID]);
    $this->assertSoftDeleted('requests', ['RID' => $pastAwaitingPayment->RID]);
    $this->assertSoftDeleted('requests', ['RID' => $pastApproved->RID]);
    $this->assertNotSoftDeleted('requests', ['RID' => $futurePending->RID]);

    expect(AuditLog::query()
        ->where('auditable_id', $pastPending->RID)
        ->where('action', 'request_expired')
        ->exists())->toBeTrue();

    Notification::assertSentToTimes($user, RequestFeedbackRequested::class, 1);
    Notification::assertSentToTimes($user, RequestStatusUpdated::class, 3);
    Notification::assertSentTo(
        $user,
        RequestStatusUpdated::class,
        fn (RequestStatusUpdated $notification): bool => $notification->toArray($user)['status'] === 'Expired'
            && $notification->toArray($user)['status_label'] === 'Expired',
    );
});

it('only allows an approved reservation to be completed after its final scheduled end time', function () {
    Notification::fake();
    $this->travelTo('2026-10-10 15:00:00');

    $user = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'student',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Completion Guard Hall',
        'Status' => 'Available',
    ]);
    $request = FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => '2026-10-09',
        'Proposed_End_Date' => '2026-10-10',
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '16:00',
        'Daily_Schedules' => [
            ['date' => '2026-10-09', 'start' => '09:00', 'end' => '12:00'],
            ['date' => '2026-10-10', 'start' => '13:00', 'end' => '16:00'],
        ],
        'Status' => 'Approved',
        'Purpose' => 'Completion timing test',
    ]);

    expect(fn () => app(EndWaitingRequest::class)->handle($request))
        ->toThrow(ValidationException::class, 'scheduled end time');

    $this->actingAs($user)
        ->post(route('requests.waiting.end', $request))
        ->assertRedirect(route('dashboard', ['request' => $request->RID]))
        ->assertSessionHas('warning', 'You can complete this event after its scheduled end time. Cancel the booking instead if it will not proceed.');

    expect($request->fresh()->Status)->toBe('Approved');
    $this->assertNotSoftDeleted('requests', ['RID' => $request->RID]);

    $this->travelTo('2026-10-10 16:01:00');

    $this->post(route('requests.waiting.end', $request))
        ->assertRedirect(route('dashboard', ['request' => $request->RID]))
        ->assertSessionHas('success');

    expect(FacilityRequest::withTrashed()->findOrFail($request->RID)->Status)->toBe('Ended');
    $this->assertSoftDeleted('requests', ['RID' => $request->RID]);
});

it('expires overdue unpaid requests but keeps timely payment proofs open for review', function () {
    Notification::fake();
    Storage::fake('local');
    $this->travelTo('2026-10-01 12:00:00');

    $user = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'student',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Payment Deadline Hall',
        'Status' => 'Available',
    ]);
    $makeRequest = fn (?string $proof): FacilityRequest => FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => '2026-10-05',
        'Proposed_End_Date' => '2026-10-05',
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Daily_Schedules' => [['date' => '2026-10-05', 'start' => '09:00', 'end' => '11:00']],
        'Status' => 'Awaiting Payment',
        'Payment_Amount' => 1500,
        'Payment_Deadline' => now()->subMinute(),
        'Payment_Proof_Path' => $proof,
        'Payment_Proof_Uploaded_At' => $proof ? now()->subHour() : null,
        'Purpose' => 'Payment expiration test',
    ]);

    $unpaid = $makeRequest(null);
    $proofSubmitted = $makeRequest('payment-proofs/on-time.pdf');
    Storage::disk('local')->put('payment-proofs/on-time.pdf', 'receipt');

    expect(FacilityRequest::expireOverduePaymentRequests())->toBe(1)
        ->and(FacilityRequest::withTrashed()->findOrFail($unpaid->RID)->Status)->toBe('Expired')
        ->and($proofSubmitted->fresh()->Status)->toBe('Awaiting Payment');

    $this->assertSoftDeleted('requests', ['RID' => $unpaid->RID]);
    $this->assertNotSoftDeleted('requests', ['RID' => $proofSubmitted->RID]);

    $this->actingAs($user)
        ->post(route('requests.payment-proof.upload', $proofSubmitted), [
            'payment_proof' => UploadedFile::fake()->image('replacement.jpg'),
        ])
        ->assertStatus(409);

    Storage::disk('local')->assertExists('payment-proofs/on-time.pdf');
    expect($proofSubmitted->fresh()->Payment_Proof_Path)->toBe('payment-proofs/on-time.pdf');
});

it('allows a requester to cancel an awaiting-payment request', function () {
    Notification::fake();
    $user = User::factory()->create([
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Cancellation Payment Hall',
        'Status' => 'Available',
    ]);
    $request = FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addDays(5),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Awaiting Payment',
        'Payment_Amount' => 1500,
        'Payment_Deadline' => now()->addDay(),
        'Purpose' => 'Cancellation test',
    ]);

    $this->actingAs($user)
        ->post(route('requests.waiting.cancel', $request), [
            'Cancellation_Reason' => 'Change of plans',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($request->fresh()->Status)->toBe('Cancelled')
        ->and($request->fresh()->Cancellation_Reason)->toBe('Change of plans');
});

it('emails a guest requester when a pending request expires', function () {
    Notification::fake();

    $facility = Facility::query()->create([
        'Facility_Name' => 'Guest Expiration Hall',
        'Status' => 'Available',
    ]);
    $request = FacilityRequest::query()->create([
        'Facility_ID' => $facility->FID,
        'Is_Guest_Booking' => true,
        'Guest_Name' => 'Guest Requester',
        'Guest_Email' => 'guest@example.com',
        'Proposed_Date' => today()->subDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Guest expiration notification test',
    ]);

    expect(FacilityRequest::markPastRequestsAsEnded())->toBe(1)
        ->and(FacilityRequest::withTrashed()->findOrFail($request->RID)->Status)->toBe('Expired');

    Notification::assertSentOnDemand(
        RequestStatusUpdated::class,
        fn (RequestStatusUpdated $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'guest@example.com'
            && $notification->toArray($notifiable)['status'] === 'Expired',
    );
});
