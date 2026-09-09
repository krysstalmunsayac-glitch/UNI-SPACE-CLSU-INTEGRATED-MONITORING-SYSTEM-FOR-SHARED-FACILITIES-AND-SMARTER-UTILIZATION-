<?php

use App\Models\Facilities;
use Database\Seeders\ClsuFacilitySeeder;

it('seeds every documented facility without changing existing facility types', function () {
    $existing = Facilities::query()->create([
        'Facility_Name' => 'University Auditorium',
        'facility_type' => 'other',
        'Status' => 'Available',
    ]);

    $this->seed(ClsuFacilitySeeder::class);
    $this->seed(ClsuFacilitySeeder::class);

    expect(Facilities::query()->count())->toBe(33)
        ->and($existing->refresh()->facility_type)->toBe('other')
        ->and($existing->rates)->toContain('₱10,000')
        ->and($existing->protocols_and_guidelines)->toContain('No food or beverages allowed')
        ->and(Facilities::query()->where('Facility_Name', 'Armando N. Espino Jr. Conference Hall (PreDiCt)')->exists())->toBeTrue()
        ->and(Facilities::query()->where('Facility_Name', 'Silid-Likhaan (Dungon Museum)')->exists())->toBeTrue();
});
