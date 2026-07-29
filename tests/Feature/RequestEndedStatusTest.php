<?php

use App\Models\Requests;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('marks pending and approved requests as ended after their proposed end time', function () {
    Carbon::setTestNow('2026-07-28 14:30:00');

    $pastApproved = Requests::query()->create([
        'Proposed_Date' => '2026-07-28',
        'Proposed_Start_Time' => '13:00',
        'Proposed_End_Time' => '14:00',
        'Status' => 'Approved',
        'Purpose' => 'Past approved request',
    ]);
    $schedule = Schedule::query()->create([
        'Request_ID' => $pastApproved->RID,
        'Date' => '2026-07-28',
        'Start_Time' => '13:00',
        'End_Time' => '14:00',
        'Status' => 'Booked',
    ]);

    $pastPending = Requests::query()->create([
        'Proposed_Date' => '2026-07-27',
        'Proposed_Start_Time' => '15:00',
        'Proposed_End_Time' => '16:00',
        'Status' => 'Pending',
        'Purpose' => 'Past pending request',
    ]);

    $futureApproved = Requests::query()->create([
        'Proposed_Date' => '2026-07-28',
        'Proposed_Start_Time' => '14:00',
        'Proposed_End_Time' => '15:00',
        'Status' => 'Approved',
        'Purpose' => 'Future approved request',
    ]);

    $rejected = Requests::query()->create([
        'Proposed_Date' => '2026-07-27',
        'Proposed_Start_Time' => '15:00',
        'Proposed_End_Time' => '16:00',
        'Status' => 'Rejected',
        'Purpose' => 'Rejected request',
    ]);

    expect(Requests::markPastRequestsAsEnded())->toBe(2)
        ->and($pastApproved->fresh()->Status)->toBe('Ended')
        ->and($pastPending->fresh()->Status)->toBe('Ended')
        ->and($schedule->fresh())->not->toBeNull()
        ->and($schedule->fresh()->Status)->toBe('Booked')
        ->and($futureApproved->fresh()->Status)->toBe('Approved')
        ->and($rejected->fresh()->Status)->toBe('Rejected');
});
