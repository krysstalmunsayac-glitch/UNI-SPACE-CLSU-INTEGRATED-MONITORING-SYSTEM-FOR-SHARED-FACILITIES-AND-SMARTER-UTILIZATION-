<?php

use App\Models\Amenity;
use App\Models\Facility;
use App\Models\User;

it('shows unavailable facilities to external users without a booking action', function () {
    $externalUser = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'external',
        'is_active' => true,
    ]);
    Facility::query()->create([
        'Facility_Name' => 'Temporarily Closed Hall',
        'Status' => 'Unavailable',
    ]);

    $this->actingAs($externalUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Temporarily Closed Hall')
        ->assertSee('Unavailable')
        ->assertSee('images/siel-space-slide-01.jpg', false)
        ->assertDontSee('images/siel-space-slide-03.jpg', false)
        ->assertDontSee('href="'.route('requests.create', ['facilitySlug' => Facility::query()->first()->slug]).'"', false);
});

it('shows only amenity names on the public facility page', function () {
    $facility = Facility::query()->create([
        'Facility_Name' => 'Public Amenity Display Hall',
        'Status' => 'Available',
    ]);
    $amenity = Amenity::query()->create([
        'name' => 'Fixed Sound System',
        'Status' => 'Available',
        'inventory_type' => 'countable',
        'inventory_quantity' => 1,
    ]);
    $facility->amenities()->attach($amenity->AID);

    $this->get(route('facilities.show', $facility))
        ->assertOk()
        ->assertSee('Fixed Sound System')
        ->assertDontSee('1 unit');
});

it('shows the responsible office instead of the location on the request page', function () {
    $externalUser = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'external',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Alumni Social Hall',
        'Office' => 'CLSU Alumni Association Inc.',
        'Location' => 'Central Luzon State University, Science City of Muñoz, Nueva Ecija',
        'Status' => 'Available',
    ]);
    $officeAdmin = User::factory()->create([
        'name' => 'Assigned Alumni Admin',
        'email' => 'alumni-admin@clsu.edu.ph',
        'user_type' => 'admin',
    ]);
    $facility->assignedAdmins()->attach($officeAdmin->id);

    $this->actingAs($externalUser)
        ->get(route('requests.create', ['facilitySlug' => $facility->slug]))
        ->assertOk()
        ->assertSee('Office')
        ->assertSee('CLSU Alumni Association Inc.')
        ->assertSee('Assigned Alumni Admin')
        ->assertSee('alumni-admin@clsu.edu.ph')
        ->assertDontSee('Central Luzon State University, Science City of Muñoz, Nueva Ecija');
});
