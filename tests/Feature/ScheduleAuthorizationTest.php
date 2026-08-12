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
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '09:00',
        'Status' => 'Approved',
        'Purpose' => $purpose,
    ]);

    return Schedule::create([
        'Request_ID' => $request->RID,
        'Date' => now()->addDay()->toDateString(),
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
