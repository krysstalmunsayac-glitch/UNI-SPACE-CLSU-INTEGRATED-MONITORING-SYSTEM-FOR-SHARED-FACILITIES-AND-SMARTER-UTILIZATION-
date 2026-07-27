<?php

use App\Models\Facilities;
use App\Models\User;

test('models can be archived and hidden from default queries', function () {
    $user = User::factory()->create();
    $facility = Facilities::create([
        'Facility_Name' => 'Archive Test Facility',
        'Price' => 100,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);

    $user->delete();
    $facility->delete();

    expect($user->trashed())->toBeTrue();
    expect($facility->trashed())->toBeTrue();
    expect(User::count())->toBe(0);
    expect(Facilities::count())->toBe(0);
});
