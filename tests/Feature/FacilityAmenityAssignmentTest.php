<?php

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\FacilityUnavailable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('super admin can assign available amenities to a facility', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
    ]);

    $facility = Facilities::create([
        'Facility_Name' => 'Test Hall',
        'facility_type' => 'conference',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);

    $amenity = Amenities::create([
        'name' => 'Projector',
        'Description' => 'HD projector',
        'Status' => 'Available',
    ]);

    Livewire::actingAs($superAdmin)
        ->test('facility.super-admin-facility')
        ->set('editingId', $facility->FID)
        ->set('Facility_Name', $facility->Facility_Name)
        ->set('facility_type', 'conference')
        ->set('Price', $facility->Price)
        ->set('Office', $facility->Office)
        ->set('Description', $facility->Description)
        ->set('Location', $facility->Location)
        ->set('Capacity', $facility->Capacity)
        ->set('Status', $facility->Status)
        ->set('selectedAmenityIds', [$amenity->AID])
        ->call('save');

    $facility->refresh();

    expect($facility->facility_type)->toBe('conference');
    expect($facility->amenities->pluck('AID')->all())->toBe([$amenity->AID]);
});

test('office admin can replace the existing facility image', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'user_type' => 'admin',
    ]);

    $facility = Facilities::create([
        'Facility_Name' => 'Image Replacement Hall',
        'facility_type' => 'auditorium',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);

    $facility->assignedAdmins()->attach($admin->id);
    Storage::disk('public')->put('facilities/old-image.jpg', 'old image');
    $facility->images()->create(['image_path' => 'facilities/old-image.jpg']);

    Livewire::actingAs($admin)
        ->test('facility.office-admin-facility')
        ->call('edit', $facility->FID)
        ->assertSet('facility_type', 'auditorium')
        ->assertSet('existingImages', ['facilities/old-image.jpg'])
        ->set('images', [UploadedFile::fake()->create('replacement.jpg', 20, 'image/jpeg')])
        ->call('save')
        ->assertHasNoErrors();

    $facility->refresh();
    $replacementPath = $facility->images()->sole()->image_path;

    Storage::disk('public')->assertMissing('facilities/old-image.jpg');
    Storage::disk('public')->assertExists($replacementPath);
});

test('super admin can filter facilities by availability', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
    ]);

    Facilities::create([
        'Facility_Name' => 'Available Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);

    Facilities::create([
        'Facility_Name' => 'Unavailable Hall',
        'Price' => 100,
        'Status' => 'Unavailable',
    ]);

    Livewire::actingAs($superAdmin)
        ->test('facility.super-admin-facility')
        ->set('statusFilter', 'Available')
        ->assertSee('Available Hall')
        ->assertDontSee('Unavailable Hall')
        ->set('statusFilter', 'Unavailable')
        ->assertSee('Unavailable Hall')
        ->assertDontSee('Available Hall');
});

test('super admin can toggle a facility availability status', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
    ]);

    $facility = Facilities::create([
        'Facility_Name' => 'Toggle Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);

    Livewire::actingAs($superAdmin)
        ->test('facility.super-admin-facility')
        ->call('toggleStatus', $facility->FID);

    expect($facility->fresh()->Status)->toBe('Unavailable');

    Livewire::actingAs($superAdmin)
        ->test('facility.super-admin-facility')
        ->call('toggleStatus', $facility->FID);

    expect($facility->fresh()->Status)->toBe('Available');
});

test('office admin can toggle an assigned facility availability status', function () {
    $admin = User::factory()->create([
        'user_type' => 'admin',
    ]);

    $facility = Facilities::create([
        'Facility_Name' => 'Assigned Toggle Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);

    $facility->assignedAdmins()->attach($admin->id);

    Livewire::actingAs($admin)
        ->test('facility.office-admin-facility')
        ->call('toggleStatus', $facility->FID);

    expect($facility->fresh()->Status)->toBe('Unavailable');
});

test('making a facility unavailable cancels active bookings and notifies requesters', function () {
    Notification::fake();

    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
    ]);
    $requester = User::factory()->create();

    $facility = Facilities::create([
        'Facility_Name' => 'Closure Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);

    $event = Events::create([
        'Event_Title' => 'Scheduled Seminar',
        'Status' => 'Upcoming',
    ]);

    $request = Requests::create([
        'Event_ID' => $event->EID,
        'Facility_ID' => $facility->FID,
        'User_ID' => $requester->id,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Approved',
        'Purpose' => 'Seminar',
    ]);

    $schedule = Schedule::create([
        'Request_ID' => $request->RID,
        'Date' => now()->addWeek()->toDateString(),
        'Start_Time' => '09:00',
        'End_Time' => '10:00',
        'Status' => 'Booked',
    ]);

    Livewire::actingAs($superAdmin)
        ->test('facility.super-admin-facility')
        ->call('toggleStatus', $facility->FID);

    expect($facility->fresh()->Status)->toBe('Unavailable')
        ->and($request->fresh()->Status)->toBe('Cancelled')
        ->and($request->fresh()->Cancellation_Reason)->toContain('marked unavailable')
        ->and($event->fresh()->Status)->toBe('Cancelled')
        ->and(Schedule::withTrashed()->findOrFail($schedule->SID)->trashed())->toBeTrue();

    Notification::assertSentTo(
        $requester,
        FacilityUnavailable::class,
    );
});
