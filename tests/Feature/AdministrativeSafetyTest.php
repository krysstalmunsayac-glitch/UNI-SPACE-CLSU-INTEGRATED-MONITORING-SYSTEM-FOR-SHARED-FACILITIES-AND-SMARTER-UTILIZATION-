<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Livewire\Volt\Volt;

it('prevents a super admin from deactivating itself through the edit form', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);

    $this->actingAs($superAdmin);

    Volt::test('user.user-management')
        ->call('edit', $superAdmin->id)
        ->set('is_active', false)
        ->call('save', true, true)
        ->assertHasErrors(['is_active']);

    expect($superAdmin->fresh()->is_active)->toBeTrue();
});

it('prevents administrators from approving an outdated booking request', function (string $role) {
    $this->travelTo(now()->startOfDay()->setTime(12, 0));

    $administrator = User::factory()->create([
        'user_type' => $role,
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facilities::create(['Facility_Name' => 'Test Hall']);

    if ($role === 'admin') {
        $administrator->facilities()->attach($facility->FID);
    }

    $booking = Requests::create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->toDateString(),
        'Proposed_End_Date' => today()->toDateString(),
        'Proposed_Start_Time' => '20:00',
        'Proposed_End_Time' => '21:00',
        'Status' => 'Pending',
        'Purpose' => 'Outdated booking test',
    ]);

    $this->actingAs($administrator);

    Volt::test('request.request')->call('approve', $booking->RID);

    expect($booking->fresh()->Status)->toBe('Pending')
        ->and($booking->schedules()->count())->toBe(0);
})->with(['super_admin', 'admin']);
