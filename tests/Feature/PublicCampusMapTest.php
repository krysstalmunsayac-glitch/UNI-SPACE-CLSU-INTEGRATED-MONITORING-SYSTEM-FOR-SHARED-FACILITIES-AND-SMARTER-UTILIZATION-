<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\User;

it('lets guests use the campus map controls on the homepage', function () {
    $facility = Facilities::create([
        'Facility_Name' => 'Public Map Hall',
        'facility_type' => 'conference',
        'Location' => 'CLSU Main Campus',
        'Capacity' => 120,
        'rates' => '₱2,500 for four hours',
        'Status' => 'Available',
        'Latitude' => 15.7354,
        'Longitude' => 120.9335,
    ]);
    $amenity = Amenities::create([
        'name' => 'Sound System',
        'Status' => 'Available',
    ]);
    $facility->amenities()->attach($amenity->AID);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Find. Schedule. Reserve.')
        ->assertSee('Browse Facilities')
        ->assertSee('Request a Facility')
        ->assertSee('How SIEL Space works')
        ->assertSee('Available facilities')
        ->assertSee('Requests this month')
        ->assertSee('images/siel-space-slide-01.jpg', false)
        ->assertSee('images/siel-space-slide-06.jpg', false)
        ->assertSee('10000', false)
        ->assertSee('Public Map Hall')
        ->assertSee('120 people')
        ->assertSee('CLSU Main Campus')
        ->assertSee('Sound System')
        ->assertSee('₱2,500 for four hours')
        ->assertSee('No image available')
        ->assertDontSee('images/CLSU_logo.png', false)
        ->assertSee('Book')
        ->assertSee('id="map-facility-type"', false)
        ->assertSee('id="map-facility-filter"', false)
        ->assertSee('Directions from CLSU Main Gate')
        ->assertSee('Directions from My Location')
        ->assertSee('Use my location');

    $this->assertGuest();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('lets users browse multiple facility photos from the card', function () {
    $facility = Facilities::create([
        'Facility_Name' => 'Carousel Hall',
        'facility_type' => 'auditorium',
        'Location' => 'CLSU Main Campus',
        'Capacity' => 200,
        'Status' => 'Available',
    ]);

    $facility->images()->createMany([
        ['image_path' => 'facilities/carousel-one.jpg'],
        ['image_path' => 'facilities/carousel-two.jpg'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Show previous photo of Carousel Hall')
        ->assertSee('Show next photo of Carousel Hall')
        ->assertSee('Show photo 1 of 2')
        ->assertSee('Show photo 2 of 2');
});

it('uses the immersive photo hero for an external user dashboard', function () {
    $user = User::factory()->create([
        'name' => 'External Visitor',
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-transparent="true"', false)
        ->assertSee('Welcome back, External Visitor')
        ->assertSee('Your reservation hub')
        ->assertSee('images/siel-space-slide-06.jpg', false)
        ->assertSee('10000', false);
});
