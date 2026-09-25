<?php

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('renders super admin pages after a facility with an approved request is archived', function () {
    Notification::fake();

    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Super Admin Archive Hall',
        'Status' => 'Available',
    ]);
    FacilityRequest::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Approved',
        'Purpose' => 'Super admin archive request',
    ]);

    $facility->delete();

    $this->actingAs($superAdmin)
        ->get(route('dashboard.super-admin'))
        ->assertOk();

    $this->get(route('facilities.super-admin.index'))
        ->assertOk();

    $this->get(route('requests.index'))
        ->assertOk()
        ->assertSee('Super Admin Archive Hall');

    $this->get(route('schedules.index'))
        ->assertOk();

    $this->get(route('feedback.index'))
        ->assertOk();
});
