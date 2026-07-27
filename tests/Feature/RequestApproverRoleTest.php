<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use Livewire\Livewire;

it('allows a request approver to approve a request and cancel its scheduled booking', function () {
    $approver = User::factory()->create([
        'user_type' => 'super_admin',
    ]);

    $requestOwner = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Test Hall',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);

    $request = Requests::create([
        'User_ID' => $requestOwner->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Test purpose',
    ]);

    Livewire::actingAs($approver)
        ->test('request.request')
        ->set('editingId', $request->RID)
        ->set('Event_ID', $request->Event_ID)
        ->set('User_ID', $request->User_ID)
        ->set('Proposed_Date', $request->Proposed_Date->toDateString())
        ->set('Proposed_Start_Time', $request->Proposed_Start_Time->format('H:i'))
        ->set('Proposed_End_Time', $request->Proposed_End_Time->format('H:i'))
        ->set('Status', 'Approved')
        ->set('Purpose', $request->Purpose)
        ->call('save');

    $request->refresh();

    expect($request->Status)->toBe('Approved');
    expect(Schedule::where('Request_ID', $request->RID)->exists())->toBeTrue();

    Livewire::actingAs($approver)
        ->test('request.request')
        ->set('editingId', $request->RID)
        ->set('Event_ID', $request->Event_ID)
        ->set('User_ID', $request->User_ID)
        ->set('Proposed_Date', $request->Proposed_Date->toDateString())
        ->set('Proposed_Start_Time', $request->Proposed_Start_Time->format('H:i'))
        ->set('Proposed_End_Time', $request->Proposed_End_Time->format('H:i'))
        ->set('Status', 'Cancelled')
        ->set('Purpose', $request->Purpose)
        ->call('save');

    $request->refresh();

    expect($request->Status)->toBe('Cancelled');
    expect($request->trashed())->toBeTrue();
    expect(Schedule::where('Request_ID', $request->RID)->exists())->toBeFalse();
});

it('requires a reason before rejecting a pending request', function () {
    $approver = User::factory()->create(['user_type' => 'super_admin']);
    $requestOwner = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Rejection Test Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $request = Requests::create([
        'User_ID' => $requestOwner->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Test rejection',
    ]);

    $component = Livewire::actingAs($approver)
        ->test('request.request')
        ->call('openRejectModal', $request->RID)
        ->call('reject')
        ->assertHasErrors(['rejectionReasons']);

    expect($request->fresh()->Status)->toBe('Pending');

    $component
        ->set('rejectionReasons', ['Facility unavailable', 'Other'])
        ->set('otherRejectionReason', 'The building is closed for repairs.')
        ->call('reject')
        ->assertHasNoErrors();

    $request->refresh();

    expect($request->Status)->toBe('Rejected')
        ->and($request->Rejection_Reason)->toContain('Facility unavailable')
        ->and($request->Rejection_Reason)->toContain('The building is closed for repairs.');
});

it('allows an office admin to approve and reject requests for an assigned facility', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);
    $requestOwner = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Assigned Admin Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $facility->assignedAdmins()->attach($admin->id);

    $approvalRequest = Requests::create([
        'User_ID' => $requestOwner->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Office admin approval',
    ]);
    $rejectionRequest = Requests::create([
        'User_ID' => $requestOwner->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addDays(2)->toDateString(),
        'Proposed_Start_Time' => '13:00',
        'Proposed_End_Time' => '14:00',
        'Status' => 'Pending',
        'Purpose' => 'Office admin rejection',
    ]);

    Livewire::actingAs($admin)
        ->test('request.request')
        ->assertSee('Approve')
        ->assertSee('Reject')
        ->call('approve', $approvalRequest->RID)
        ->call('openRejectModal', $rejectionRequest->RID)
        ->set('rejectionReasons', ['Facility unavailable'])
        ->call('reject')
        ->assertHasNoErrors();

    expect($approvalRequest->fresh()->Status)->toBe('Approved')
        ->and($rejectionRequest->fresh()->Status)->toBe('Rejected')
        ->and($rejectionRequest->fresh()->Rejection_Reason)->toBe('Facility unavailable');
});
