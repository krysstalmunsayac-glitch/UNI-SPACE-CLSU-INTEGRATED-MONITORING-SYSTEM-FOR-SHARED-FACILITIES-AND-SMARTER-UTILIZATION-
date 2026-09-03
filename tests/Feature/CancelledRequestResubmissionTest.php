<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('allows an external user to update a request that needs revision', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Resubmission Hall',
    ]);
    $facilityRequest = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_End_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Pending',
        'Review_Notes' => 'Please clarify the purpose and attendance.',
        'Review_Requested_At' => now(),
        'Purpose' => 'Original purpose',
        'Capacity' => 20,
    ]));

    $newDate = now()->addDays(10)->toDateString();

    $this->actingAs($user)
        ->post(route('waiting.list.update', $facilityRequest), [
            'Proposed_Date' => $newDate,
            'Proposed_End_Date' => $newDate,
            'Proposed_Start_Time' => '10:00',
            'Proposed_End_Time' => '11:00',
            'Purpose' => 'Updated purpose',
            'Capacity' => 30,
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($facilityRequest->fresh())
        ->Status->toBe('Pending')
        ->Review_Notes->toBeNull()
        ->Review_Requested_At->toBeNull()
        ->Purpose->toBe('Updated purpose')
        ->Capacity->toBe(30);
});

it('rejects a booking shorter than one hour', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Minimum Duration Hall',
    ]);
    $facilityRequest = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_End_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Pending',
        'Review_Notes' => 'Please correct the booking time.',
        'Review_Requested_At' => now(),
        'Purpose' => 'Original purpose',
    ]));

    $newDate = now()->addDays(10)->toDateString();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('waiting.list.update', $facilityRequest), [
            'Proposed_Date' => $newDate,
            'Proposed_End_Date' => $newDate,
            'Proposed_Start_Time' => '10:00',
            'Proposed_End_Time' => '10:59',
            'Purpose' => 'Updated purpose',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors([
            'Proposed_End_Time' => 'A booking must be at least 1 hour.',
        ]);
});

it('keeps decided requests read only', function (string $status) {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::query()->create(['Facility_Name' => "{$status} Request Hall"]);
    $facilityRequest = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_End_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => $status,
        'Purpose' => 'Original submitted purpose',
    ]));

    $this->actingAs($user)
        ->post(route('waiting.list.update', $facilityRequest), [
            'Proposed_Date' => now()->addDays(10)->toDateString(),
            'Proposed_End_Date' => now()->addDays(10)->toDateString(),
            'Proposed_Start_Time' => '10:00',
            'Proposed_End_Time' => '11:00',
            'Purpose' => 'Attempted update',
        ])
        ->assertRedirect(route('dashboard', ['request' => $facilityRequest->RID]))
        ->assertSessionHas('warning');

    expect($facilityRequest->fresh()->Purpose)->toBe('Original submitted purpose');
})->with(['Approved', 'Rejected', 'Cancelled', 'Ended']);

it('allows an external user to cancel their approved request', function () {
    Notification::fake();

    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::query()->create(['Facility_Name' => 'Approved Cancellation Hall']);
    $facilityRequest = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_End_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Approved',
        'Purpose' => 'Approved event',
    ]));

    $this->actingAs($user)
        ->post(route('waiting.list.cancel', $facilityRequest), [
            'Cancellation_Reason' => 'Change of plans',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($facilityRequest->fresh())
        ->Status->toBe('Cancelled')
        ->Cancellation_Reason->toBe('Change of plans');
});
