<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Livewire\Volt\Volt;

function administrativeRequest(string $status = 'Pending'): array
{
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facilities::query()->create(['Facility_Name' => 'Transition Hall']);
    $booking = Requests::withoutEvents(fn () => Requests::query()->create([
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => now()->addWeek()->toDateString(),
        'Proposed_End_Date' => now()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '08:00',
        'Proposed_End_Time' => '10:00',
        'Status' => $status,
        'Purpose' => 'Status transition test',
    ]));

    return [$administrator, $booking];
}

it('allows pending requests to be approved', function () {
    [$administrator, $booking] = administrativeRequest();

    $this->actingAs($administrator);
    Volt::test('request.request')->call('approve', $booking->RID);

    expect($booking->fresh()->Status)->toBe('Approved');
});

it('does not approve or reject an already rejected request', function () {
    [$administrator, $booking] = administrativeRequest('Rejected');
    $this->actingAs($administrator);

    Volt::test('request.request')->call('approve', $booking->RID);
    expect($booking->fresh()->Status)->toBe('Rejected');

    Volt::test('request.request')->call('openRejectModal', $booking->RID)
        ->assertSet('showRejectModal', false);

    expect($booking->fresh()->Status)->toBe('Rejected');
});

it('does not permanently delete an active request', function () {
    [$administrator, $booking] = administrativeRequest();
    $this->actingAs($administrator);

    expect(fn () => Volt::test('request.request')->call('forceDelete', $booking->RID))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Requests::query()->whereKey($booking->RID)->exists())->toBeTrue();
});
