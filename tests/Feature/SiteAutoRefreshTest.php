<?php

use App\Models\Amenities;
use App\Models\User;
use App\Support\SiteVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('changes the site version when an administrator updates content', function () {
    $admin = User::factory()->create(['user_type' => 'super_admin']);
    $amenity = Amenities::query()->create([
        'name' => 'Projector',
        'Status' => 'Available',
    ]);
    $originalVersion = SiteVersion::current();

    $this->actingAs($admin);
    $amenity->update(['Status' => 'Unavailable']);

    expect(SiteVersion::current())->not->toBe($originalVersion);

    $this->getJson(route('site.version'))
        ->assertOk()
        ->assertJson(['version' => SiteVersion::current()])
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
});
