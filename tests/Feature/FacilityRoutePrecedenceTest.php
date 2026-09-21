<?php

use App\Livewire\Facilities\OfficeAdminFacility;
use App\Livewire\Facilities\SuperAdminFacility;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Support\Facades\Route;

it('keeps facility management URLs separate from the public facility route', function () {
    expect(Route::getRoutes()->match(
        request()->create('/facilities/super-admin', 'GET')
    )->getActionName())->toBe(SuperAdminFacility::class);

    expect(Route::getRoutes()->match(
        request()->create('/facilities/office-admin', 'GET')
    )->getActionName())->toBe(OfficeAdminFacility::class);
});

it('only accepts numeric identifiers on the public facility route', function () {
    expect(Route::getRoutes()->match(
        request()->create('/facilities/123', 'GET')
    )->getName())->toBe('facilities.show');
});

it('loads Leaflet assets on facility management routes', function () {
    $request = request()->create('/facilities/super-admin', 'GET');
    $request->setRouteResolver(fn () => Route::getRoutes()->match($request));

    app()->instance('request', $request);

    expect(view('partials.head')->render())
        ->toContain('leaflet@1.9.4/dist/leaflet.css')
        ->toContain('leaflet@1.9.4/dist/leaflet.js');
});

it('shows only the assigned office admin email on a facility page', function () {
    $facility = Facility::query()->create([
        'Facility_Name' => 'Assigned Facility',
        'facility_type' => 'conference',
        'Office' => 'Assigned Office',
        'Description' => 'A facility used to verify assigned office contact details.',
        'Capacity' => 50,
        'Status' => 'Available',
    ]);
    $officeAdmin = User::factory()->create([
        'email' => 'assigned-office@clsu.edu.ph',
        'user_type' => 'admin',
    ]);
    $unrelatedAdmin = User::factory()->create([
        'email' => 'unrelated-office@clsu.edu.ph',
        'user_type' => 'admin',
    ]);
    $facility->assignedAdmins()->attach($officeAdmin->id);

    $this->get(route('facilities.show', $facility))
        ->assertOk()
        ->assertSee('assigned-office@clsu.edu.ph')
        ->assertDontSee('unrelated-office@clsu.edu.ph');
});
