<?php

use App\Models\AuditLog;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records the authenticated administrator who approves a request', function () {
    $admin = User::factory()->create(['user_type' => 'super_admin']);
    $endUser = User::factory()->create(['user_type' => 'user']);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Audit Hall',
        'Price' => 0,
        'Status' => 'Available',
    ]);
    $requestRecord = Requests::query()->create([
        'User_ID' => $endUser->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Pending',
        'Purpose' => 'Audit approval behavior',
    ]);

    $this->actingAs($admin);
    $requestRecord->update(['Status' => 'Approved']);

    $log = AuditLog::query()->where('action', 'request_approved')->first();

    expect($log)
        ->not->toBeNull()
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->auditable_id)->toBe($requestRecord->RID)
        ->and($log->old_values['Status'])->toBe('Pending')
        ->and($log->new_values['Status'])->toBe('Approved');
});

it('records automatic event ending as a system action', function () {
    $user = User::factory()->create(['user_type' => 'user']);
    $requestRecord = Requests::query()->create([
        'User_ID' => $user->id,
        'Proposed_Date' => now()->subDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Approved',
        'Purpose' => 'Audit automatic ending',
    ]);

    Requests::markPastRequestsAsEnded();

    $log = AuditLog::query()
        ->where('action', 'event_ended')
        ->where('auditable_id', $requestRecord->RID)
        ->first();

    expect($log)
        ->not->toBeNull()
        ->and($log->actor_id)->toBeNull()
        ->and($log->new_values['Status'])->toBe('Ended');
});
