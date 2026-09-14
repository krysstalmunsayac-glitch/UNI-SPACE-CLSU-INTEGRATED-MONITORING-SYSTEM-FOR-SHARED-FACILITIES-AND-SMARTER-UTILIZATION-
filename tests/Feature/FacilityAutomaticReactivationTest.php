<?php

use App\Models\Facilities;
use App\Services\FacilityAvailabilityService;

it('automatically reactivates a facility after its deactivation period ends', function () {
    $expiredFacility = Facilities::query()->create([
        'Facility_Name' => 'Temporary Closure Hall',
        'Status' => 'Unavailable',
        'Available_At' => now()->subMinute(),
        'Deactivated_At' => now()->subDay(),
    ]);

    $futureFacility = Facilities::query()->create([
        'Facility_Name' => 'Maintenance Hall',
        'Status' => 'Unavailable',
        'Available_At' => now()->addHour(),
        'Deactivated_At' => now(),
    ]);

    $reactivatedCount = app(FacilityAvailabilityService::class)->reactivateExpired();

    expect($reactivatedCount)->toBe(1)
        ->and($expiredFacility->fresh()->Status)->toBe('Available')
        ->and($expiredFacility->fresh()->Available_At)->toBeNull()
        ->and($expiredFacility->fresh()->Deactivated_At)->toBeNull()
        ->and($futureFacility->fresh()->Status)->toBe('Unavailable')
        ->and($futureFacility->fresh()->Available_At)->not->toBeNull();
});

it('shows the automatic reactivation time on the public facility page', function () {
    $reactivationTime = now()->addDay()->startOfMinute();
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Scheduled Maintenance Hall',
        'Status' => 'Unavailable',
        'Available_At' => $reactivationTime,
        'Deactivated_At' => now(),
    ]);

    $this->get(route('facilities.show', $facility))
        ->assertOk()
        ->assertSee('Automatic reactivation')
        ->assertSee($reactivationTime->format('M j, Y \a\t g:i A'));
});

it('shows when automatic reactivation is not scheduled', function () {
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Indefinitely Closed Hall',
        'Status' => 'Unavailable',
        'Deactivated_At' => now(),
    ]);

    $this->get(route('facilities.show', $facility))
        ->assertOk()
        ->assertSee('Automatic reactivation')
        ->assertSee('Not scheduled. This facility must be reactivated manually.');
});
