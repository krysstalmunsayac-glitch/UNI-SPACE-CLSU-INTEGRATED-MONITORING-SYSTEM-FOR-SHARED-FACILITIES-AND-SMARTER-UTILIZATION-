<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

it('allows an external user to edit and resubmit a cancelled request', function () {
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
        'Status' => 'Cancelled',
        'Cancellation_Reason' => 'Schedule conflict',
        'Purpose' => 'Original purpose',
        'Capacity' => 20,
    ]));

    $newDate = now()->addDays(10)->toDateString();

    $this->actingAs($user)
        ->post(route('waiting.list.update', $facilityRequest), [
            'Proposed_Date' => $newDate,
            'Proposed_End_Date' => $newDate,
            'Proposed_Start_Time' => '10:00',
            'Proposed_End_Time' => '12:00',
            'Purpose' => 'Updated purpose',
            'Capacity' => 30,
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($facilityRequest->fresh())
        ->Status->toBe('Pending')
        ->Cancellation_Reason->toBeNull()
        ->Purpose->toBe('Updated purpose')
        ->Capacity->toBe(30);
});
