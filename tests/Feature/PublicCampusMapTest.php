<?php

use App\Models\Facilities;

it('lets guests use the campus map controls on the homepage', function () {
    Facilities::create([
        'Facility_Name' => 'Public Map Hall',
        'Status' => 'Available',
        'Latitude' => 15.7354,
        'Longitude' => 120.9335,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Public Map Hall')
        ->assertSee('id="map-facility-type"', false)
        ->assertSee('id="map-facility-filter"', false)
        ->assertSee('Directions from CLSU Main Gate')
        ->assertSee('Directions from My Location')
        ->assertSee('Use my location');

    $this->assertGuest();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
