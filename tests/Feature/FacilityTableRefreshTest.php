<?php

use App\Models\Facilities;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

it('keeps facility pages stable and recovers after the last row is archived', function () {
    $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
    $facilities = collect(range(1, 9))->map(fn ($number) => Facilities::create([
        'Facility_Name' => "Refresh Hall {$number}",
        'created_at' => now()->startOfDay(),
    ]));

    $component = Volt::test('facility.super-admin-facility');
    expect($component->instance()->facilities->pluck('FID')->all())
        ->toBe($facilities->reverse()->take(8)->pluck('FID')->all());

    $component->call('setPage', 2, 'facilitiesPage')
        ->assertSee('Refresh Hall 1')
        ->call('archiveFacility', $facilities->first()->FID)
        ->assertSet('paginators.facilitiesPage', 1)
        ->assertSee('Refresh Hall 9')
        ->assertDontSee('No facilities found.');
});

it('refreshes filtered results and recovers pages after external changes', function (string $role, string $view, string $page) {
    $admin = User::factory()->create(['user_type' => $role]);
    $this->actingAs($admin);
    $facilities = collect(range(1, 9))->map(function ($number) use ($admin) {
        $facility = Facilities::create([
            'Facility_Name' => "Live Hall {$number}",
            'facility_type' => 'conference',
            'Status' => 'Available',
            'created_at' => now()->startOfDay(),
        ]);
        $admin->facilities()->attach($facility->FID);

        return $facility;
    });
    $component = Volt::test($view)
        ->set('searchInput', 'conference')
        ->set('statusFilter', 'Available')
        ->call('setPage', 2, $page);

    $facilities->first()->update(['Status' => 'Unavailable']);
    $component->call('$refresh')
        ->assertSet("paginators.{$page}", 1)
        ->assertSet('search', 'conference')
        ->assertSet('statusFilter', 'Available')
        ->assertSee('Live Hall 9');
    expect($component->instance()->facilities->total())->toBe(8);

    $facilities->last()->update(['Facility_Name' => 'Updated Conference Hall']);
    $component->call('$refresh')->assertSee('Updated Conference Hall');
})->with([
    ['super_admin', 'facility.super-admin-facility', 'facilitiesPage'],
    ['admin', 'facility.office-admin-facility', 'assignedFacilitiesPage'],
]);

it('recovers the archive page when another session restores its final row', function () {
    $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
    $facilities = collect(range(1, 9))->map(function ($number) {
        $facility = Facilities::create(['Facility_Name' => "Archived Hall {$number}"]);
        $facility->delete();

        return $facility;
    });
    $component = Volt::test('facility.super-admin-facility')
        ->call('openArchivedFacilities')
        ->call('setPage', 2, 'archivedFacilitiesPage');
    $facilities->first()->restore();
    $component->call('$refresh')->assertSet('paginators.archivedFacilitiesPage', 1);
    expect($component->instance()->archivedFacilities->count())->toBe(8);
});

it('retains uploaded images on archive and restore and deletes every file on permanent deletion', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
    $facility = Facilities::create(['Facility_Name' => 'Photo Hall']);
    foreach (['facilities/one.jpg', 'facilities/two.jpg'] as $path) {
        Storage::disk('public')->put($path, 'image');
        $facility->images()->create(['image_path' => $path]);
    }
    $component = Volt::test('facility.super-admin-facility')
        ->call('archiveFacility', $facility->FID);
    expect($facility->images()->count())->toBe(2);
    $component->call('restoreFacility', $facility->FID);
    expect($facility->images()->count())->toBe(2);
    $component->call('archiveFacility', $facility->FID)
        ->call('forceDeleteFacility', $facility->FID);
    Storage::disk('public')->assertMissing(['facilities/one.jpg', 'facilities/two.jpg']);
    expect($facility->images()->count())->toBe(0);
});

it('shows stored image URLs and pauses polling while editing', function () {
    $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
    $facility = Facilities::create([
        'Facility_Name' => 'Image Hall',
        'Image_URL' => 'images/about/campus-facility.png',
    ]);
    Volt::test('facility.super-admin-facility')
        ->assertSee('wire:poll.15s', false)
        ->assertSee('wire:key="facility-'.$facility->FID.'"', false)
        ->assertSee(asset('images/about/campus-facility.png'), false)
        ->call('edit', $facility->FID)
        ->assertDontSee('wire:poll.15s', false);
});
