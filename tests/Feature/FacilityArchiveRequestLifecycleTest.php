<?php

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Support\Facades\Notification;

it('keeps approved reservations active and rejects unapproved requests when a facility is archived', function () {
    Notification::fake();

    $facility = Facility::query()->create([
        'Facility_Name' => 'Archive Lifecycle Hall',
        'Status' => 'Available',
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $pending = FacilityRequest::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Pending',
        'Purpose' => 'Pending archive test',
    ]);
    $awaitingPayment = FacilityRequest::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '13:00',
        'Proposed_End_Time' => '15:00',
        'Status' => 'Awaiting Payment',
        'Purpose' => 'Payment archive test',
    ]);
    $approved = FacilityRequest::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '16:00',
        'Proposed_End_Time' => '18:00',
        'Status' => 'Approved',
        'Purpose' => 'Approved archive test',
    ]);

    $facility->delete();

    expect($facility->trashed())->toBeTrue()
        ->and($pending->fresh()->Status)->toBe('Rejected')
        ->and($pending->fresh()->trashed())->toBeFalse()
        ->and($awaitingPayment->fresh()->Status)->toBe('Rejected')
        ->and($awaitingPayment->fresh()->trashed())->toBeFalse()
        ->and($approved->fresh()->Status)->toBe('Approved')
        ->and($approved->fresh()->trashed())->toBeFalse();

    $this->actingAs($requester)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Archive Lifecycle Hall');

    Notification::assertSentToTimes($requester, RequestStatusUpdated::class, 2);
});
