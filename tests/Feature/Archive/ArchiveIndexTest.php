<?php

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

it('shows only archived requests and schedules for the office admin\'s assigned facility', function () {
    $admin = User::factory()->create([
        'user_type' => 'admin',
    ]);

    $assignedFacility = Facilities::create([
        'Facility_Name' => 'Assigned Facility',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);
    $assignedFacility->assignedAdmins()->attach($admin->id);

    $otherFacility = Facilities::create([
        'Facility_Name' => 'Other Facility',
        'Price' => 120,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);

    $assignedAmenity = Amenities::create([
        'name' => 'Assigned Amenity',
        'Description' => 'Assigned amenity',
        'Status' => 'Available',
    ]);

    $otherAmenity = Amenities::create([
        'name' => 'Other Amenity',
        'Description' => 'Other amenity',
        'Status' => 'Available',
    ]);

    $assignedFacility->amenities()->sync([$assignedAmenity->AID]);

    $otherFacility->amenities()->sync([$otherAmenity->AID]);

    $assignedRequest = Requests::create([
        'User_ID' => $admin->id,
        'Facility_ID' => $assignedFacility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00:00',
        'Proposed_End_Time' => '10:00:00',
        'Status' => 'Pending',
        'Purpose' => 'Assigned request',
    ]);
    $assignedRequest->delete();

    $assignedSchedule = Schedule::create([
        'Request_ID' => $assignedRequest->RID,
        'Date' => now()->addDay()->toDateString(),
        'Start_Time' => '09:00:00',
        'End_Time' => '10:00:00',
        'Status' => 'Booked',
    ]);
    $assignedSchedule->delete();

    $otherRequest = Requests::create([
        'User_ID' => $admin->id,
        'Facility_ID' => $otherFacility->FID,
        'Proposed_Date' => now()->addDays(2)->toDateString(),
        'Proposed_Start_Time' => '10:00:00',
        'Proposed_End_Time' => '11:00:00',
        'Status' => 'Pending',
        'Purpose' => 'Other request',
    ]);
    $otherRequest->delete();

    $otherSchedule = Schedule::create([
        'Request_ID' => $otherRequest->RID,
        'Date' => now()->addDays(2)->toDateString(),
        'Start_Time' => '10:00:00',
        'End_Time' => '11:00:00',
        'Status' => 'Booked',
    ]);
    $otherSchedule->delete();

    $archivedEvent = Events::create([
        'Event_Title' => 'Archived Event',
        'Description' => 'Archived event description',
        'Type_Event' => 'Conference',
        'Status' => 'Upcoming',
    ]);
    $archivedEvent->delete();

    $archivedUser = User::factory()->create([
        'name' => 'Archived User',
        'email' => 'archived.user@example.com',
    ]);
    $archivedUser->delete();

    actingAs($admin);

    Volt::test('archive.archive-index')
        ->assertSee('Request #'.$assignedRequest->RID)
        ->assertDontSee('Request #'.$otherRequest->RID)
        ->assertSee('Schedule #'.$assignedSchedule->SID)
        ->assertDontSee('Schedule #'.$otherSchedule->SID)
        ->assertSee('Event #'.$archivedEvent->EID)
        ->assertSee('User #'.$archivedUser->id);
});
