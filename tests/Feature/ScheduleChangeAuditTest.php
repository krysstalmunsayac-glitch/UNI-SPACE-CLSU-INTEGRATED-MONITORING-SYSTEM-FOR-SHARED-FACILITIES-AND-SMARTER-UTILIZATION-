<?php

use App\Models\AuditLog;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\ScheduleUpdated;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

it('records administrator schedule changes and notifies the end user', function (string $role) {
    Notification::fake();

    $administrator = User::factory()->create([
        'user_type' => $role,
        'is_active' => true,
    ]);
    $endUser = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'student',
        'is_active' => true,
    ]);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Schedule Change Hall',
        'Status' => 'Available',
    ]);

    if ($role === 'admin') {
        $administrator->assignedFacilities()->attach($facility->FID);
    }

    $oldDate = today()->addDays(7)->toDateString();
    $newDate = today()->addDays(8)->toDateString();
    $facilityRequest = Requests::query()->create([
        'User_ID' => $endUser->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $oldDate,
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Approved',
        'Purpose' => 'Schedule change audit test',
    ]);
    $schedule = Schedule::query()->create([
        'Request_ID' => $facilityRequest->RID,
        'Date' => $oldDate,
        'Start_Time' => '09:00',
        'End_Time' => '10:00',
        'Status' => 'Booked',
    ]);

    $this->actingAs($administrator);

    Volt::test('schedule.schedule')
        ->call('edit', $schedule->SID)
        ->set('Date', $newDate)
        ->set('Start_Time', '10:00')
        ->set('End_Time', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::query()->where('action', 'schedule_updated')->latest()->firstOrFail();

    expect($schedule->fresh()->Date->toDateString())->toBe($newDate)
        ->and($log->auditable_id)->toBe($facilityRequest->RID)
        ->and($log->actor_id)->toBe($administrator->id)
        ->and($log->old_values['Date'])->toBe($oldDate)
        ->and($log->new_values['Date'])->toBe($newDate);

    Notification::assertSentTo($endUser, ScheduleUpdated::class);
})->with(['super_admin', 'admin']);

