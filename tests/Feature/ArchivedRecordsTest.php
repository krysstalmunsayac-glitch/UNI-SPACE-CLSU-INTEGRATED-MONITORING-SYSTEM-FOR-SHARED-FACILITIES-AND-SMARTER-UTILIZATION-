<?php

use App\Models\Facilities;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('archived facilities are listed and can be restored', function () {
    $user = User::factory()->create([
        'user_type' => 'super_admin',
    ]);

    $facility = Facilities::create([
        'Facility_Name' => 'Archived Facility',
        'Price' => 100,
        'Status' => 'Available',
        'Office' => 'CEN',
    ]);

    $facility->delete();

    $this->actingAs($user)
        ->get('/archived')
        ->assertOk();

    $this->assertTrue($facility->fresh()->trashed());
});
