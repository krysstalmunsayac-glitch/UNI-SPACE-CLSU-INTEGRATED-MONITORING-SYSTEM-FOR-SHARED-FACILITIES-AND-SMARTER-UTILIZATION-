<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\NewRequestSubmitted;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('stores a facility request with a facility reference', function () {
    $user = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Test Hall',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);

    actingAs($user);

    $response = $this->post(route('requests.store', $facility), [
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Purpose' => 'Test purpose',
        'Capacity' => 75,
    ]);

    $response->assertRedirect();

    assertDatabaseHas('requests', [
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Purpose' => 'Test purpose',
        'Capacity' => 75,
    ]);
});

it('notifies only the assigned office admin and all super admins about a facility request', function () {
    Notification::fake();

    $requester = User::factory()->create(['user_type' => 'user']);
    $assignedAdmin = User::factory()->create(['user_type' => 'admin']);
    $unassignedAdmin = User::factory()->create(['user_type' => 'admin']);
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $facility = Facilities::create([
        'Facility_Name' => 'Scoped Notification Hall',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);
    $facility->assignedAdmins()->attach($assignedAdmin->id);

    actingAs($requester);

    $this->post(route('requests.store', $facility), [
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Purpose' => 'Scoped notification test',
    ])->assertRedirect();

    Notification::assertSentTo($assignedAdmin, NewRequestSubmitted::class);
    Notification::assertSentTo($superAdmin, NewRequestSubmitted::class);
    Notification::assertNotSentTo($unassignedAdmin, NewRequestSubmitted::class);
});

it('rejects later overlapping requests for the same facility date and time', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Conflict Hall',
        'Price' => 100,
        'Office' => 'Main Office',
        'Status' => 'Available',
    ]);

    actingAs($firstUser);

    $firstResponse = $this->post(route('requests.store', $facility), [
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Purpose' => 'First request',
    ]);

    $firstResponse->assertRedirect();

    actingAs($secondUser);

    $secondResponse = $this->post(route('requests.store', $facility), [
        'Proposed_Date' => now()->addDay()->toDateString(),
        'Proposed_Start_Time' => '09:30',
        'Proposed_End_Time' => '10:30',
        'Purpose' => 'Second request',
    ]);

    $secondResponse->assertRedirect(route('waiting.list'));

    $firstRequest = Requests::where('User_ID', $firstUser->id)->first();
    $secondRequest = Requests::where('User_ID', $secondUser->id)->first();

    expect($firstRequest->Status)->toBe('Pending')
        ->and($secondRequest->Status)->toBe('Rejected');
});
