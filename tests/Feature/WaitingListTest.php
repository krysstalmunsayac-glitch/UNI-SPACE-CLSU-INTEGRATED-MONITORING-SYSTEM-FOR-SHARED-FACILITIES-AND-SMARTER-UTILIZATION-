<?php

use App\Models\Events;
use App\Models\Feedbacks;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\RequestCancelledByUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('authenticated users can view their waiting list and update request and event details', function () {
    $user = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Waiting List Facility',
        'Price' => 120,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);

    $event = Events::create([
        'User_ID' => $user->id,
        'Event_Title' => 'Original Event',
        'Description' => 'Original description',
        'Type_Event' => 'Seminar',
        'Status' => 'Upcoming',
    ]);

    $request = Requests::create([
        'User_ID' => $user->id,
        'Event_ID' => $event->EID,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Original purpose',
    ]);

    actingAs($user);

    $this->get(route('waiting.list'))
        ->assertRedirect(route('dashboard').'#requests');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Waiting List Facility')
        ->assertSee('Original Event');

    $this->post(route('waiting.list.update', $request), [
        'Event_Title' => 'Updated Event',
        'Description' => 'Updated description',
        'Type_Event' => 'Conference',
        'Proposed_Date' => now()->addDays(2)->toDateString(),
        'Proposed_Start_Time' => '10:00',
        'Proposed_End_Time' => '11:00',
        'Purpose' => 'Updated purpose',
        'Event_Status' => 'Completed',
    ])
        ->assertRedirect(route('dashboard'));

    $request->refresh();
    $event->refresh();

    expect($request->Purpose)->toBe('Updated purpose')
        ->and($request->Proposed_Date->toDateString())->toBe(now()->addDays(2)->toDateString())
        ->and($event->Event_Title)->toBe('Updated Event')
        ->and($event->Status)->toBe('Completed');
});

test('authenticated users must provide a reason when cancelling a pending or approved request', function () {
    Notification::fake();

    $user = User::factory()->create();
    $admin = User::factory()->create(['user_type' => 'admin']);
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $facility = Facilities::create([
        'Facility_Name' => 'Cancellation Facility',
        'Price' => 120,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);
    $facility->assignedAdmins()->attach($admin->id);

    $request = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Approved',
        'Purpose' => 'Original purpose',
    ]);

    actingAs($user);

    $this->from(route('dashboard'))
        ->post(route('waiting.list.cancel', $request), [
            'Cancellation_Reason' => '',
        ])
        ->assertSessionHasErrors('Cancellation_Reason');

    $request->refresh();

    expect($request->Status)->toBe('Approved')
        ->and($request->Cancellation_Reason)->toBeNull();

    $this->post(route('waiting.list.cancel', $request), [
        'Cancellation_Reason' => 'The event was moved to another venue.',
    ])
        ->assertRedirect(route('dashboard'));

    $request->refresh();

    expect($request->Status)->toBe('Cancelled')
        ->and($request->Cancellation_Reason)->toBe('The event was moved to another venue.')
        ->and($request->trashed())->toBeTrue();

    foreach ([$admin, $superAdmin] as $recipient) {
        Notification::assertSentTo($recipient, RequestCancelledByUser::class, function (RequestCancelledByUser $notification, array $channels) use ($recipient) {
            expect($channels)->toContain('mail')
                ->and($channels)->toContain('database');

            $mailMessage = $notification->toMail($recipient);

            expect($mailMessage->subject)->toBe('Facility request cancelled by user')
                ->and($mailMessage->markdown)->toBe('emails.request-cancelled-by-user')
                ->and($mailMessage->viewData['reason'])->toBe('The event was moved to another venue.')
                ->and($mailMessage->viewData['facilityName'])->toBe('Cancellation Facility');

            return true;
        });
    }
});

test('external users can submit feedback only for their own facility request', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Feedback Facility',
        'Price' => 120,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);

    $ownedRequest = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Approved',
        'Purpose' => 'Feedback test',
    ]);

    $otherRequest = Requests::create([
        'User_ID' => $otherUser->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDays(2)->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Approved',
        'Purpose' => 'Other user feedback test',
    ]);

    actingAs($user);

    $this->post(route('facility-feedback.store', $ownedRequest), [
        'Comment' => 'The facility was clean and well prepared.',
    ])->assertRedirect(route('dashboard').'#requests');

    expect(Feedbacks::query()->where('User_ID', $user->id)->value('Comment'))
        ->toBe('The facility was clean and well prepared.');

    $this->post(route('facility-feedback.store', $otherRequest), [
        'Comment' => 'This should not be accepted.',
    ])->assertForbidden();
});
