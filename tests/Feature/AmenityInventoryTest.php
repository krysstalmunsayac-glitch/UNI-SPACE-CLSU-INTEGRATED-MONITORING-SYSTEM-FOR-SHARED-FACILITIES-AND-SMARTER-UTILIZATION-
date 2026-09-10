<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function amenityInventoryPayload(string $date, int $amenityId, int $quantity): array
{
    return [
        'Guest_Name' => 'Inventory Test Guest',
        'Event_Title' => 'Inventory Test Event',
        'Description' => 'Testing exact amenity inventory allocation.',
        'Type_Event' => 'Meeting',
        'Event_Scope' => 'External',
        'Amenity_ID' => [$amenityId],
        'Amenity_Quantity' => [$amenityId => $quantity],
        'Proposed_Date' => $date,
        'Proposed_End_Date' => $date,
        'Daily_Schedules' => [['date' => $date, 'start' => '09:00', 'end' => '10:00']],
        'Purpose_Categories' => ['Meeting or Conference'],
        'Reservation_Frequency' => 'First time',
        'Facility_Importance' => 'Important',
        'Requirements_Fit' => 'Yes, completely',
        'Reserve_Again_Intent' => 'Definitely Yes',
        'Capacity' => 10,
    ];
}

it('stores requested amenity units and rejects quantities exceeding overlapping inventory', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $firstFacility = Facilities::create(['Facility_Name' => 'Inventory Hall A', 'Status' => 'Available', 'Capacity' => 50]);
    $secondFacility = Facilities::create(['Facility_Name' => 'Inventory Hall B', 'Status' => 'Available', 'Capacity' => 50]);
    $amenity = Amenities::create(['name' => 'Wireless Microphone', 'Status' => 'Available', 'inventory_quantity' => 5]);
    $firstFacility->amenities()->attach($amenity->AID);
    $secondFacility->amenities()->attach($amenity->AID);
    $date = today()->addDays(5)->toDateString();

    $this->actingAs($admin)
        ->post(route('admin.requests.store', $firstFacility), amenityInventoryPayload($date, $amenity->AID, 3))
        ->assertSessionHasNoErrors();

    $firstRequest = Requests::firstOrFail();
    expect((int) $firstRequest->amenities()->firstOrFail()->pivot->quantity)->toBe(3);

    $this->post(route('admin.requests.store', $secondFacility), amenityInventoryPayload($date, $amenity->AID, 3))
        ->assertSessionHasErrors("Amenity_Quantity.{$amenity->AID}");

    expect(Requests::count())->toBe(1);

    $this->post(route('admin.requests.store', $secondFacility), amenityInventoryPayload($date, $amenity->AID, 2))
        ->assertSessionHasNoErrors();

    expect(Requests::count())->toBe(2);
});

it('shows exact inventory and reserved units on the amenity management page', function () {
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    Amenities::create(['name' => 'Projector', 'Status' => 'Available', 'inventory_quantity' => 2]);

    $this->actingAs($admin)
        ->get(route('Amenities'))
        ->assertOk()
        ->assertSee('Available quantity')
        ->assertSee('2 units');
});
