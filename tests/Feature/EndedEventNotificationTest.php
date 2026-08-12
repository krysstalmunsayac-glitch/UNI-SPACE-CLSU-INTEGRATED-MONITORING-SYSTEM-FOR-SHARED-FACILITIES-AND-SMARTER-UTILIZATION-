<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\RequestFeedbackRequested;
use Illuminate\Support\Facades\Notification;

it('sends bell and email notifications when an event automatically ends', function () {
    Notification::fake();

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
        'Status' => 'Approved',
        'Purpose' => 'Completed event',
    ]);

    expect(Requests::markPastRequestsAsEnded())->toBe(1);

    Notification::assertSentTo(
        $user,
        RequestFeedbackRequested::class,
        fn (RequestFeedbackRequested $notification, array $channels) => $channels === ['mail', 'database'],
    );

    expect(Requests::withTrashed()->findOrFail($facilityRequest->RID))
        ->Status->toBe('Ended')
        ->trashed()->toBeTrue();

    $this->actingAs($user)
        ->get(route('dashboard', ['request_status' => 'Ended']))
        ->assertOk()
        ->assertSee('Give optional feedback');
});
