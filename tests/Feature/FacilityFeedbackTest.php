<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

function endedFacilityRequestFor(User $user): Requests
{
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Test Hall',
        'Location' => 'Test Campus',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);

    return Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->subDay()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Ended',
        'Purpose' => 'Feedback regression test',
    ]));
}

test('an end user can open feedback for their ended facility request', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $facilityRequest = endedFacilityRequestFor($user);

    $this->actingAs($user)
        ->get(route('facility-feedback.create', $facilityRequest))
        ->assertOk()
        ->assertSee('Rate your facility')
        ->assertSee('Test Hall');
});

test('an end user can submit feedback once', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $facilityRequest = endedFacilityRequestFor($user);

    $this->actingAs($user)
        ->post(route('facility-feedback.store', $facilityRequest), [
            'Rating' => 5,
            'Comment' => 'Excellent facility.',
        ])
        ->assertRedirect(route('dashboard').'#requests');

    $this->assertDatabaseHas('feedbacks', [
        'User_ID' => $user->id,
        'Request_ID' => $facilityRequest->RID,
        'Facility_ID' => $facilityRequest->Facility_ID,
        'Rating' => 5,
        'Comment' => 'Excellent facility.',
    ]);
});

test('archived feedback is still treated as already submitted', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $facilityRequest = endedFacilityRequestFor($user);
    $feedback = $facilityRequest->feedback()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facilityRequest->Facility_ID,
        'Rating' => 4,
        'Comment' => 'Original feedback.',
    ]);
    $feedback->delete();

    $this->actingAs($user)
        ->get(route('facility-feedback.create', $facilityRequest))
        ->assertRedirect(route('dashboard').'#requests')
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('facility-feedback.store', $facilityRequest), [
            'Rating' => 5,
            'Comment' => 'Duplicate feedback.',
        ])
        ->assertConflict();

    expect($facilityRequest->feedback()->withTrashed()->count())->toBe(1);
});
