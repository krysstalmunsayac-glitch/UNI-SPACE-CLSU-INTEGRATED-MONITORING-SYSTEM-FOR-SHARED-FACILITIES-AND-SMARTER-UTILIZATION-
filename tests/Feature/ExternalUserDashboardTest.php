<?php

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('renders an external user dashboard with active and archived-facility requests', function () {
    Notification::fake();

    $user = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'external',
        'is_active' => true,
    ]);
    $activeFacility = Facility::query()->create([
        'Facility_Name' => 'Active External Hall',
        'Status' => 'Available',
    ]);
    $archivedFacility = Facility::query()->create([
        'Facility_Name' => 'Archived External Hall',
        'Status' => 'Available',
    ]);

    FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $activeFacility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Pending',
        'Purpose' => 'Active dashboard request',
    ]);
    FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $archivedFacility->FID,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '13:00',
        'Proposed_End_Time' => '15:00',
        'Status' => 'Approved',
        'Purpose' => 'Archived facility approved request',
    ]);

    $archivedFacility->delete();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Active External Hall')
        ->assertSee('Archived External Hall')
        ->assertSee('Approved');
});
