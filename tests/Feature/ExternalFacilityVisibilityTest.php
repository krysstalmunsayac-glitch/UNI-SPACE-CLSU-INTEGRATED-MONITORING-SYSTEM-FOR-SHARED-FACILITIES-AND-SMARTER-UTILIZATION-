<?php

use App\Models\Facilities;
use App\Models\User;

it('shows unavailable facilities to external users without a booking action', function () {
    $externalUser = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'external',
        'is_active' => true,
    ]);
    Facilities::query()->create([
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
        ->assertDontSee('href="'.route('requests.create', Facilities::query()->first()).'"', false);
});

it('shows the responsible office instead of the location on the request page', function () {
    $externalUser = User::factory()->create([
        'user_type' => 'user',
        'account_type' => 'external',
        'is_active' => true,
    ]);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Alumni Social Hall',
        'Office' => 'CLSU Alumni Association Inc.',
        'Location' => 'Central Luzon State University, Science City of Muñoz, Nueva Ecija',
        'Status' => 'Available',
    ]);

    $this->actingAs($externalUser)
        ->get(route('requests.create', $facility))
        ->assertOk()
        ->assertSee('Office')
        ->assertSee('CLSU Alumni Association Inc.')
        ->assertDontSee('Central Luzon State University, Science City of Muñoz, Nueva Ecija');
});
