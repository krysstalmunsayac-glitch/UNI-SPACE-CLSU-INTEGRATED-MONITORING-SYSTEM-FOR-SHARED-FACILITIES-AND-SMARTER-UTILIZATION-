<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

it('lets the request owner end an approved event after it starts', function () {
    Notification::fake();

    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $otherUser = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facility = Facilities::create(['Facility_Name' => 'Active Event Hall']);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->toDateString(),
        'Proposed_End_Date' => now()->toDateString(),
        'Proposed_Start_Time' => now()->subHour()->format('H:i'),
        'Proposed_End_Time' => now()->addHour()->format('H:i'),
        'Status' => 'Approved',
        'Purpose' => 'Active user event',
    ]);

    $this->actingAs($otherUser)
        ->post(route('waiting.list.end', $facilityRequest))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('waiting.list.end', $facilityRequest))
        ->assertRedirect(route('dashboard', ['request' => $facilityRequest->RID]))
        ->assertSessionHas('success');

    expect(Requests::withTrashed()->findOrFail($facilityRequest->RID))
        ->Status->toBe('Ended')
        ->deleted_at->not->toBeNull();
});

it('does not let an owner end an approved event before it starts', function () {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_End_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Approved',
        'Purpose' => 'Future event',
    ]);

    $this->actingAs($user)
        ->post(route('waiting.list.end', $facilityRequest))
        ->assertRedirect(route('dashboard', ['request' => $facilityRequest->RID]))
        ->assertSessionHas('warning');

    expect($facilityRequest->fresh()->Status)->toBe('Approved');
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

it('lets the requester securely upload payment proof', function () {
    Storage::fake('local');
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $facilityRequest = Requests::create([
        'User_ID' => $user->id,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Awaiting Payment',
        'Payment_Amount' => 1500,
        'Payment_Deadline' => now()->addDays(2),
        'Purpose' => 'Paid event',
    ]);

    $this->actingAs($user)
        ->post(route('requests.payment-proof.upload', $facilityRequest), [
            'payment_proof' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertRedirect(route('dashboard', ['request' => $facilityRequest->RID]));

    $facilityRequest->refresh();
    expect($facilityRequest->Payment_Proof_Path)->not->toBeNull();
    Storage::disk('local')->assertExists($facilityRequest->Payment_Proof_Path);
});
