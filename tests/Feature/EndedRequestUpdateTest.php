<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

it('does not allow an external user to change an ended request', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facilities::create(['Facility_Name' => 'Test Hall']);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->subDay()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Ended',
        'Purpose' => 'Original purpose',
    ]);

    $this->actingAs($user)
        ->post(route('waiting.list.update', $facilityRequest), [
            'Proposed_Date' => now()->addWeek()->toDateString(),
            'Proposed_End_Date' => now()->addWeek()->toDateString(),
            'Proposed_Start_Time' => '10:00',
            'Proposed_End_Time' => '11:00',
            'Purpose' => 'Changed purpose',
        ])
        ->assertRedirect(route('dashboard', ['request' => $facilityRequest->RID]))
        ->assertSessionHas('warning');

    expect($facilityRequest->fresh())
        ->Purpose->toBe('Original purpose')
        ->Status->toBe('Ended');
});
