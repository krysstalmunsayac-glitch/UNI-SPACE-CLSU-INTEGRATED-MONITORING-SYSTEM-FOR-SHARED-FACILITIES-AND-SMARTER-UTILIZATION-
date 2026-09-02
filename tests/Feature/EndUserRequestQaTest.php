<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

it('validates predefined and other cancellation reasons', function () {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'QA Hall']);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Pending',
        'Purpose' => 'QA test',
    ]);

    $this->actingAs($user)
        ->post(route('waiting.list.cancel', $facilityRequest), [
            'Cancellation_Reason' => 'Invalid reason',
        ])
        ->assertSessionHasErrors('Cancellation_Reason');

    $this->actingAs($user)
        ->post(route('waiting.list.cancel', $facilityRequest), [
            'Cancellation_Reason' => 'Other',
        ])
        ->assertSessionHasErrors('Other_Cancellation_Reason');

    $this->actingAs($user)
        ->post(route('waiting.list.cancel', $facilityRequest), [
            'Cancellation_Reason' => 'Schedule conflict',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($facilityRequest->fresh())
        ->Status->toBe('Cancelled')
        ->Cancellation_Reason->toBe('Schedule conflict');
});

it('prevents a repeated cancellation', function () {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Cancelled',
        'Cancellation_Reason' => 'Change of plans',
        'Purpose' => 'QA test',
    ]);

    $this->actingAs($user)
        ->post(route('waiting.list.cancel', $facilityRequest), [
            'Cancellation_Reason' => 'Event postponed',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('warning');

    expect($facilityRequest->fresh()->Cancellation_Reason)->toBe('Change of plans');
});

it('accepts an ended request rating without an optional comment and prevents duplicates', function () {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'QA Hall']);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->subDay()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Ended',
        'Purpose' => 'QA test',
    ]);

    $this->actingAs($user)
        ->post(route('facility-feedback.store', $facilityRequest), ['Rating' => 5])
        ->assertRedirect(route('dashboard').'#requests')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('feedbacks', [
        'Request_ID' => $facilityRequest->RID,
        'Rating' => 5,
        'Comment' => null,
    ]);

    $this->actingAs($user)
        ->post(route('facility-feedback.store', $facilityRequest), ['Rating' => 4])
        ->assertConflict();
});
