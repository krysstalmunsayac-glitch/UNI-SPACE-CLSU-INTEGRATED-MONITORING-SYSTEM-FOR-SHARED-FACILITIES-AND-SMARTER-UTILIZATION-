<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Support\Facades\Notification;

it('sends an email and database notification when a request status changes', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'request-owner@example.com',
    ]);
    $facility = Facilities::create([
        'Facility_Name' => 'Conference Hall',
        'Price' => 100,
        'Office' => 'Main Office',
        'Description' => 'Great space',
        'Location' => 'Building A',
        'Capacity' => 50,
        'Status' => 'Available',
    ]);
    $request = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00:00',
        'Proposed_End_Time' => '10:00:00',
        'Status' => 'Pending',
        'Purpose' => 'Team meeting',
    ]);

    Notification::send($user, new RequestStatusUpdated($request, 'Pending'));

    Notification::assertSentTo($user, RequestStatusUpdated::class, function (RequestStatusUpdated $notification, array $channels) use ($user) {
        expect($channels)->toContain('mail')
            ->and($channels)->toContain('database');

        $mailMessage = $notification->toMail($user);

        expect($mailMessage->subject)->toBe('Your facility request status has been updated')
            ->and($mailMessage->markdown)->toBe('emails.request-status-updated')
            ->and($mailMessage->viewData['message'])->toBe('Your request status has been updated.')
            ->and($mailMessage->viewData['facilityName'])->toBe('Conference Hall');

        return true;
    });
});

it('includes the rejection reason in email and database notifications', function () {
    Notification::fake();

    $user = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Rejected Request Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $request = Requests::create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00:00',
        'Proposed_End_Time' => '10:00:00',
        'Status' => 'Rejected',
        'Rejection_Reason' => 'Facility unavailable; Other: Closed for repairs.',
        'Purpose' => 'Team meeting',
    ]);

    Notification::send($user, new RequestStatusUpdated($request, 'Pending'));

    Notification::assertSentTo($user, RequestStatusUpdated::class, function (RequestStatusUpdated $notification) use ($user) {
        $mailMessage = $notification->toMail($user);
        $databaseData = $notification->toArray($user);

        expect($mailMessage->viewData['rejectionReason'])
            ->toBe('Facility unavailable; Other: Closed for repairs.')
            ->and($databaseData['rejection_reason'])
            ->toBe('Facility unavailable; Other: Closed for repairs.');

        return true;
    });
});
