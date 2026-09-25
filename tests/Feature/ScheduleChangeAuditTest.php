<?php

use App\Livewire\Requests\RequestManagement;
use App\Livewire\Schedules\ScheduleManagement;
use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\ScheduleUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

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
    $facility = Facility::query()->create([
        'Facility_Name' => 'Schedule Change Hall',
        'Status' => 'Available',
    ]);

    if ($role === 'admin') {
        $administrator->assignedFacilities()->attach($facility->FID);
    }

    $oldDate = today()->addDays(7)->toDateString();
    $newDate = today()->addDays(8)->toDateString();
    $facilityRequest = FacilityRequest::query()->create([
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

    Livewire::test(ScheduleManagement::class)
        ->call('edit', $schedule->SID)
        ->assertSee($endUser->name)
        ->assertDontSee('Delete')
        ->set('form.Date', $newDate)
        ->set('form.Start_Time', '10:00')
        ->set('form.End_Time', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::query()->where('action', 'schedule_updated')->latest()->firstOrFail();
    $updatedRequest = $facilityRequest->fresh();

    expect($schedule->fresh()->Date->toDateString())->toBe($newDate)
        ->and($updatedRequest->Proposed_Date->toDateString())->toBe($newDate)
        ->and($updatedRequest->Proposed_End_Date->toDateString())->toBe($newDate)
        ->and($updatedRequest->Proposed_Start_Time->format('H:i'))->toBe('10:00')
        ->and($updatedRequest->Proposed_End_Time->format('H:i'))->toBe('11:00')
        ->and($updatedRequest->Daily_Schedules)->toBe([
            ['date' => $newDate, 'start' => '10:00', 'end' => '11:00'],
        ])
        ->and($log->auditable_id)->toBe($facilityRequest->RID)
        ->and($log->actor_id)->toBe($administrator->id)
        ->and($log->old_values['Date'])->toBe($oldDate)
        ->and($log->new_values['Date'])->toBe($newDate);

    Livewire::test(RequestManagement::class)
        ->call('showRequest', $facilityRequest->RID)
        ->assertSet('Schedule_Changes.0.old.date', Carbon::parse($oldDate)->format('M d, Y'))
        ->assertSet('Schedule_Changes.0.old.start', '9:00 AM')
        ->assertSet('Schedule_Changes.0.new.date', Carbon::parse($newDate)->format('M d, Y'))
        ->assertSet('Schedule_Changes.0.new.start', '10:00 AM')
        ->assertSee('Schedule change history')
        ->assertSee('Previous schedule')
        ->assertSee('Updated schedule')
        ->assertSee($administrator->name);

    Notification::assertSentTo($endUser, ScheduleUpdated::class);
})->with(['super_admin', 'admin']);

it('allows one day of a multi-day request schedule to be updated', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $endUser = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Multi-day Schedule Hall',
        'Status' => 'Available',
    ]);
    $firstDate = today()->addDays(7)->toDateString();
    $secondDate = today()->addDays(8)->toDateString();
    $facilityRequest = FacilityRequest::query()->create([
        'User_ID' => $endUser->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $firstDate,
        'Proposed_End_Date' => $secondDate,
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Daily_Schedules' => [
            ['date' => $firstDate, 'start' => '09:00', 'end' => '11:00'],
            ['date' => $secondDate, 'start' => '09:00', 'end' => '11:00'],
        ],
        'Status' => 'Approved',
        'Purpose' => 'Multi-day schedule update test',
    ]);
    $firstSchedule = Schedule::query()->create([
        'Request_ID' => $facilityRequest->RID,
        'Date' => $firstDate,
        'Start_Time' => '09:00',
        'End_Time' => '11:00',
        'Status' => 'Booked',
    ]);
    $secondSchedule = Schedule::query()->create([
        'Request_ID' => $facilityRequest->RID,
        'Date' => $secondDate,
        'Start_Time' => '09:00',
        'End_Time' => '11:00',
        'Status' => 'Booked',
    ]);

    $this->actingAs($administrator);

    Livewire::test(ScheduleManagement::class)
        ->call('edit', $firstSchedule->SID)
        ->set('form.Start_Time', '12:00')
        ->set('form.End_Time', '14:00')
        ->call('save')
        ->assertHasNoErrors();

    $updatedRequest = $facilityRequest->fresh();

    expect($firstSchedule->fresh()->Start_Time->format('H:i'))->toBe('12:00')
        ->and($firstSchedule->fresh()->End_Time->format('H:i'))->toBe('14:00')
        ->and($secondSchedule->fresh()->Start_Time->format('H:i'))->toBe('09:00')
        ->and($secondSchedule->fresh()->End_Time->format('H:i'))->toBe('11:00')
        ->and($updatedRequest->Proposed_Date->toDateString())->toBe($firstDate)
        ->and($updatedRequest->Proposed_End_Date->toDateString())->toBe($secondDate)
        ->and($updatedRequest->Proposed_Start_Time->format('H:i'))->toBe('12:00')
        ->and($updatedRequest->Proposed_End_Time->format('H:i'))->toBe('11:00')
        ->and($updatedRequest->Daily_Schedules)->toBe([
            ['date' => $firstDate, 'start' => '12:00', 'end' => '14:00'],
            ['date' => $secondDate, 'start' => '09:00', 'end' => '11:00'],
        ]);
});

it('shows completed schedules with their archived request details in read only mode', function (string $role) {
    $administrator = User::factory()->create([
        'user_type' => $role,
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Completed Schedule Hall',
        'Status' => 'Available',
    ]);

    if ($role === 'admin') {
        $administrator->assignedFacilities()->attach($facility->FID);
    }

    $date = today()->subDay()->toDateString();
    $facilityRequest = FacilityRequest::query()->create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => $date,
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Ended',
        'Purpose' => 'Completed schedule reference test',
    ]);
    $schedule = Schedule::query()->create([
        'Request_ID' => $facilityRequest->RID,
        'Date' => $date,
        'Start_Time' => '09:00',
        'End_Time' => '11:00',
        'Status' => 'Booked',
    ]);
    $facilityRequest->delete();

    $this->actingAs($administrator);

    Livewire::test(ScheduleManagement::class)
        ->call('edit', $schedule->SID)
        ->assertSet('showModal', true)
        ->assertSet('scheduleReadOnly', true)
        ->assertSee('Schedule Details')
        ->assertSee('Completed Schedule Hall')
        ->assertSee('Completed schedule reference test')
        ->set('form.Date', today()->addWeek()->toDateString())
        ->call('save');

    expect($schedule->fresh()->Date->toDateString())->toBe($date);
})->with(['super_admin', 'admin']);

it('refreshes the calendar instead of returning a 404 for a stale schedule event', function () {
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);

    $this->actingAs($administrator);

    Livewire::test(ScheduleManagement::class)
        ->call('edit', 999999)
        ->assertSet('showModal', false)
        ->assertSet('editingId', null)
        ->assertDispatched('calendar-refresh');
});
