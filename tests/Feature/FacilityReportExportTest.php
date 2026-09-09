<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\User;

it('includes complete facility details in csv excel and pdf reports', function () {
    $this->actingAs(User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]));

    $facility = Facilities::query()->create([
        'Facility_Name' => 'Complete Export Hall',
        'facility_type' => 'conference',
        'Access_Type' => 'Shared',
        'Price' => 2500,
        'rates' => 'PHP 2,500 for four hours',
        'Rate_Details' => 'PHP 2,500 for four hours',
        'Office' => 'Export Test Office',
        'Description' => 'A complete description for export verification.',
        'Protocols' => 'Keep the room clean after use.',
        'protocols_and_guidelines' => 'Keep the room clean after use.',
        'Contact_Details' => 'reports@example.test',
        'Reference_URL' => 'https://example.test/facility-source',
        'Data_Notes' => 'Verified facility source note.',
        'Location' => 'CLSU Main Campus',
        'Latitude' => 15.7354,
        'Longitude' => 120.9335,
        'Capacity' => 120,
        'Status' => 'Available',
    ]);

    $amenity = Amenities::query()->create([
        'name' => 'Export Projector',
        'Description' => 'Projector used by the export test.',
        'Status' => 'Available',
    ]);
    $facility->amenities()->attach($amenity->AID);

    $csv = $this->get(route('exports.facilities.csv'));
    $csv->assertOk();
    $csvContent = $csv->streamedContent();

    expect($csvContent)
        ->toContain('Access Type')
        ->toContain('Rates')
        ->toContain('Protocols and Guidelines')
        ->toContain('Complete Export Hall')
        ->toContain('Export Projector')
        ->toContain('reports@example.test')
        ->toContain('https://example.test/facility-source');

    $pdf = $this->get(route('exports.facilities.pdf'));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');

    expect($pdf->getContent())
        ->toStartWith('%PDF-')
        ->toContain('Complete Export Hall')
        ->toContain('Access type')
        ->toContain('Base price \\(PHP\\)')
        ->toContain('Rates')
        ->toContain('Export Projector')
        ->toContain('Protocols and guidelines')
        ->toContain('reports@example.test');

    $xlsx = $this->get(route('exports.facilities.xlsx'));
    $xlsx->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $temporaryPath = tempnam(sys_get_temp_dir(), 'facility-report-test-');
    file_put_contents($temporaryPath, $xlsx->getContent());
    $archive = new ZipArchive;

    try {
        expect($archive->open($temporaryPath))->toBeTrue();
        $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');

        expect($worksheet)
            ->toContain('Access Type')
            ->toContain('Complete Export Hall')
            ->toContain('Export Projector')
            ->toContain('Verified facility source note');
    } finally {
        $archive->close();
        @unlink($temporaryPath);
    }
});
