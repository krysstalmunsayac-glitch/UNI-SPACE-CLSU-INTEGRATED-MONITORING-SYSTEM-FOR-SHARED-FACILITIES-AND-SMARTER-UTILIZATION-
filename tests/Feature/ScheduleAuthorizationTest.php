<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

function scheduleForFacility(Facilities $facility, string $purpose = 'Test booking'): Schedule
{
    $request = Requests::create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDays(3)->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Approved',
        'Purpose' => $purpose,
    ]);

    return Schedule::create([
        'Request_ID' => $request->RID,
        'Date' => now()->addDays(3)->toDateString(),
        'Start_Time' => '08:00',
        'End_Time' => '09:00',
        'Status' => 'Booked',
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['user_type' => 'admin']);
    $this->assignedFacility = Facilities::create(['Facility_Name' => 'Assigned Hall']);
    $this->otherFacility = Facilities::create(['Facility_Name' => 'Other Hall']);
    $this->admin->facilities()->attach($this->assignedFacility->FID);
    $this->actingAs($this->admin);
});

it('prevents an office admin from updating another facility schedule', function () {
    $assignedSchedule = scheduleForFacility($this->assignedFacility, 'Assigned request');
    $otherSchedule = scheduleForFacility($this->otherFacility, 'Other request');

    expect(fn () => Volt::test('schedule.schedule')
        ->set('editingId', $otherSchedule->SID)
        ->set('Request_ID', $assignedSchedule->Request_ID)
        ->set('Date', now()->addDays(2)->toDateString())
        ->set('Start_Time', '10:00')
        ->set('End_Time', '11:00')
        ->set('Status', 'Booked')
        ->call('save'))
        ->toThrow(ModelNotFoundException::class);

    expect($otherSchedule->fresh()->Request_ID)->toBe($otherSchedule->Request_ID);
});

it('keeps a schedule locked to its original request', function () {
    $schedule = scheduleForFacility($this->assignedFacility, 'Original request');
    $otherSchedule = scheduleForFacility($this->assignedFacility, 'Different request');

    Volt::test('schedule.schedule')
        ->call('edit', $schedule->SID)
        ->set('Request_ID', $otherSchedule->Request_ID)
        ->call('save')
        ->assertHasErrors(['Request_ID']);

    expect($schedule->fresh()->Request_ID)->toBe($schedule->Request_ID);
});

it('rejects an administrative schedule edit shorter than one hour', function () {
    $schedule = scheduleForFacility($this->assignedFacility);

    Volt::test('schedule.schedule')
        ->call('edit', $schedule->SID)
        ->set('Start_Time', '10:00')
        ->set('End_Time', '10:59')
        ->call('save')
        ->assertHasErrors(['End_Time']);

    expect($schedule->fresh()->Start_Time->format('H:i'))->toBe('08:00')
        ->and($schedule->fresh()->End_Time->format('H:i'))->toBe('09:00');
});

it('automatically sets a one hour end time when the schedule start changes', function () {
    $schedule = scheduleForFacility($this->assignedFacility);

    Volt::test('schedule.schedule')
        ->call('edit', $schedule->SID)
        ->set('Start_Time', '14:30')
        ->assertSet('End_Time', '15:30');
});

it('rejects administrative schedule times outside operating hours', function () {
    $schedule = scheduleForFacility($this->assignedFacility);

    Volt::test('schedule.schedule')
        ->call('edit', $schedule->SID)
        ->set('Start_Time', '06:30')
        ->set('End_Time', '08:00')
        ->call('save')
        ->assertHasErrors(['Start_Time']);
});

it('requires administrative schedule changes to remain three days in advance', function () {
    $schedule = scheduleForFacility($this->assignedFacility);

    Volt::test('schedule.schedule')
        ->call('edit', $schedule->SID)
        ->set('Date', now()->addDays(2)->toDateString())
        ->call('save')
        ->assertHasErrors(['Date']);

    expect($schedule->fresh()->Date->toDateString())->toBe(now()->addDays(3)->toDateString());
});

it('prevents an office admin from restoring another facility schedule', function () {
    $schedule = scheduleForFacility($this->otherFacility);
    $schedule->delete();

    expect(fn () => Volt::test('schedule.schedule')->call('restore', $schedule->SID))
        ->toThrow(ModelNotFoundException::class);

    expect($schedule->fresh()->trashed())->toBeTrue();
});

it('prevents an office admin from permanently deleting another facility schedule', function () {
    $schedule = scheduleForFacility($this->otherFacility);
    $schedule->delete();

    expect(fn () => Volt::test('schedule.schedule')->call('forceDelete', $schedule->SID))
        ->toThrow(ModelNotFoundException::class);

    expect(Schedule::withTrashed()->find($schedule->SID))->not->toBeNull();
});
