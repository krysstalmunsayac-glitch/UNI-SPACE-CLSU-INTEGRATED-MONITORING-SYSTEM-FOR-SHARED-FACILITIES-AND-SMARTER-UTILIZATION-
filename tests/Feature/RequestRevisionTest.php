<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\RequestNeedsRevision;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('lets an approver request revisions and the user resubmit the same request', function () {
    Notification::fake();

    $approver = User::factory()->create(['user_type' => 'super_admin']);
    $requester = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::create([
        'Facility_Name' => 'Revision Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $facilityRequest = Requests::create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Seminar',
    ]);

    Livewire::actingAs($approver)
        ->test('request.request')
        ->call('openReviewModal', $facilityRequest->RID)
        ->assertSet('showReviewModal', true)
        ->set('reviewNotes', 'Please provide the expected attendee count and a clearer event description.')
        ->call('requestRevision')
        ->assertHasNoErrors();

    $facilityRequest->refresh();

    expect($facilityRequest->RID)->toBe($facilityRequest->getKey())
        ->and($facilityRequest->Status)->toBe('Pending')
        ->and($facilityRequest->Review_Notes)->toContain('expected attendee count')
        ->and($facilityRequest->Review_Requested_At)->not->toBeNull();

    Notification::assertSentTo($requester, RequestNeedsRevision::class);

    $this->actingAs($requester)
        ->post(route('waiting.list.update', $facilityRequest), [
            'Proposed_Date' => now()->addWeek()->toDateString(),
            'Proposed_Start_Time' => '09:00',
            'Proposed_End_Time' => '10:00',
            'Purpose' => 'Seminar with complete details',
            'Capacity' => 75,
        ])
        ->assertRedirect(route('dashboard'));

    $facilityRequest->refresh();

    expect($facilityRequest->RID)->toBe($facilityRequest->getKey())
        ->and($facilityRequest->Status)->toBe('Pending')
        ->and($facilityRequest->Capacity)->toBe(75)
        ->and($facilityRequest->Review_Notes)->toBeNull()
        ->and($facilityRequest->Review_Requested_At)->toBeNull();
});
