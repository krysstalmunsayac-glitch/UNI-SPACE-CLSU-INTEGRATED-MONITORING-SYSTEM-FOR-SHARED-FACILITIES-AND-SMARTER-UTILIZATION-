<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\NewRequestSubmitted;
use Illuminate\Support\Facades\Notification;

it('sends email and database notifications to office admins and super admins for new requests', function () {
    Notification::fake();

    $requester = User::factory()->create([
        'name' => 'Request Owner',
        'email' => 'request-owner@example.com',
    ]);
    $admin = User::factory()->create(['user_type' => 'admin']);
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $facility = Facilities::create([
        'Facility_Name' => 'Conference Hall',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);
    $request = Requests::create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00:00',
        'Proposed_End_Time' => '10:00:00',
        'Status' => 'Pending',
        'Purpose' => 'Team meeting',
        'Capacity' => 75,
    ]);

    Notification::send(collect([$admin, $superAdmin]), new NewRequestSubmitted($request));

    foreach ([$admin, $superAdmin] as $recipient) {
        Notification::assertSentTo($recipient, NewRequestSubmitted::class, function (NewRequestSubmitted $notification, array $channels) use ($recipient) {
            expect($channels)->toContain('mail')
                ->and($channels)->toContain('database');

            $mailMessage = $notification->toMail($recipient);

            expect($mailMessage->subject)->toBe('New facility request submitted')
                ->and($mailMessage->markdown)->toBe('emails.new-request-submitted')
                ->and($mailMessage->viewData['facilityName'])->toBe('Conference Hall')
                ->and($mailMessage->viewData['expectedCapacity'])->toBe(75);

            return true;
        });
    }
});
