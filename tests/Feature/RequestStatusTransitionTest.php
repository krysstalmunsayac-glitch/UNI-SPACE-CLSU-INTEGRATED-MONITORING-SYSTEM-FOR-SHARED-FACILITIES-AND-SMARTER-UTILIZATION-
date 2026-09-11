<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\RequestAwaitingPayment;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

function administrativeRequest(string $status = 'Pending'): array
{
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facilities::query()->create(['Facility_Name' => 'Transition Hall']);
    $booking = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_End_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '10:00',
        'Status' => $status,
        'Purpose' => 'Status transition test',
    ]));

    return [$administrator, $booking];
}

it('allows pending requests to be approved', function () {
    [$administrator, $booking] = administrativeRequest();

    $this->actingAs($administrator);
    Volt::test('request.request')->call('approve', $booking->RID);

    expect($booking->fresh()->Status)->toBe('Approved');
});

it('rejects buffered competing pending requests when one request is approved', function () {
    [$administrator, $booking] = administrativeRequest();
    $competitor = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => User::factory()->create(['user_type' => 'user', 'is_active' => true])->id,
        'Facility_ID' => $booking->Facility_ID,
        'Proposed_Date' => $booking->Proposed_Date->toDateString(),
        'Proposed_End_Date' => $booking->Proposed_Date->toDateString(),
        'Proposed_Start_Time' => '10:00',
        'Proposed_End_Time' => '11:00',
        'Daily_Schedules' => [[
            'date' => $booking->Proposed_Date->toDateString(),
            'start' => '10:00',
            'end' => '11:00',
        ]],
        'Status' => 'Pending',
        'Purpose' => 'Buffered competing request',
    ]));

    $this->actingAs($administrator);
    Volt::test('request.request')->call('approve', $booking->RID);

    expect($booking->fresh()->Status)->toBe('Approved')
        ->and($competitor->fresh()->Status)->toBe('Rejected');
});

it('does not approve or reject an already rejected request', function () {
    [$administrator, $booking] = administrativeRequest('Rejected');
    $this->actingAs($administrator);

    Volt::test('request.request')->call('approve', $booking->RID);
    expect($booking->fresh()->Status)->toBe('Rejected');

    Volt::test('request.request')->call('openRejectModal', $booking->RID)
        ->assertSet('showRejectModal', false);

    expect($booking->fresh()->Status)->toBe('Rejected');
});

it('does not permanently delete an active request', function () {
    [$administrator, $booking] = administrativeRequest();
    $this->actingAs($administrator);

    expect(fn () => Volt::test('request.request')->call('forceDelete', $booking->RID))
        ->toThrow(ModelNotFoundException::class);

    expect(Requests::query()->whereKey($booking->RID)->exists())->toBeTrue();
});

it('keeps cancelled requests read only for administrators', function () {
    [$administrator, $booking] = administrativeRequest('Cancelled');
    $this->actingAs($administrator);

    Volt::test('request.request')
        ->call('edit', $booking->RID)
        ->assertSet('showModal', false)
        ->assertSet('editingId', null);

    expect($booking->fresh()->Purpose)->toBe('Status transition test');
});

it('removes request archiving from office administrators', function () {
    [, $booking] = administrativeRequest();
    $officeAdmin = User::factory()->create([
        'user_type' => 'admin',
        'is_active' => true,
    ]);

    $this->actingAs($officeAdmin);

    $this->get(route('dashboard.officeadmin'))
        ->assertOk()
        ->assertDontSee('Archives');

    Volt::test('request.request')
        ->assertDontSee('Archive request')
        ->call('delete', $booking->RID)
        ->assertForbidden();
});

it('lets an administrator choose whether to email when cancelling an approved request', function (bool $sendEmail) {
    Notification::fake();
    [$administrator, $booking] = administrativeRequest('Approved');
    $requester = $booking->user;
    $this->actingAs($administrator);

    Volt::test('request.request')
        ->call('openCancelModal', $booking->RID)
        ->assertSet('showCancelModal', true)
        ->set('adminCancellationReason', 'Facility maintenance is required.')
        ->set('emailCancellationNotice', $sendEmail)
        ->call('confirmCancellation')
        ->assertHasNoErrors()
        ->assertSet('showCancelModal', false);

    expect($booking->fresh())
        ->Status->toBe('Cancelled')
        ->Cancellation_Reason->toBe('Facility maintenance is required.');

    if ($sendEmail) {
        Notification::assertSentTo($requester, RequestStatusUpdated::class);
    } else {
        Notification::assertNotSentTo($requester, RequestStatusUpdated::class);
    }
})->with([true, false]);

it('sends payment instructions for a paid facility request', function () {
    Notification::fake();
    [$administrator, $booking] = administrativeRequest();
    $booking->facility->update(['Price' => 2500]);
    $requester = $booking->user;
    $this->actingAs($administrator);

    Volt::test('request.request')
        ->call('openPaymentModal', $booking->RID)
        ->assertSet('showPaymentModal', true)
        ->set('paymentAmount', '2750.00')
        ->set('paymentDeadline', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('requestPayment')
        ->assertHasNoErrors();

    expect($booking->fresh())
        ->Status->toBe('Awaiting Payment')
        ->Payment_Amount->toBe('2750.00');

    Notification::assertSentTo($requester, RequestAwaitingPayment::class);
});
