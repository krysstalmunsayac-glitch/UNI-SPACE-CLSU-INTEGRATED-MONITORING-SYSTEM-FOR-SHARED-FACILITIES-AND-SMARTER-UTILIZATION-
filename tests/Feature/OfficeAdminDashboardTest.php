<?php

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('renders office admin pages for an archived assigned facility with an approved request', function () {
    Notification::fake();

    $officeAdmin = User::factory()->create([
        'user_type' => 'admin',
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Office Admin Archive Hall',
        'Status' => 'Available',
    ]);
    $facility->assignedAdmins()->attach($officeAdmin->id);

    FacilityRequest::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Approved',
        'Purpose' => 'Office admin archive request',
    ]);

    $facility->delete();

    $this->actingAs($officeAdmin)
        ->get(route('dashboard.office-admin'))
        ->assertOk();

    $this->get(route('facilities.office-admin.index'))
        ->assertOk();

    $this->get(route('requests.index'))
        ->assertOk()
        ->assertSee('Office Admin Archive Hall');

    $this->get(route('schedules.index'))
        ->assertOk();

    $this->get(route('feedback.index'))
        ->assertOk();
});
