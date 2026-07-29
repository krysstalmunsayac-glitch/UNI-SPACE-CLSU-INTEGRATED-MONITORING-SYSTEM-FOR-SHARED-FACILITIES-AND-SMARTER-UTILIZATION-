<?php

use App\Services\AdminReportExporter;
use Illuminate\Support\Collection;

it('creates a valid formatted xlsx package for facility exports', function () {
    $facility = (object) [
        'FID' => 7,
        'Facility_Name' => 'CLSU Auditorium',
        'facility_type' => 'auditorium',
        'Price' => 1500.50,
        'Capacity' => 300,
        'Location' => 'Main Campus',
        'Office' => 'University Services',
        'Status' => 'Available',
    ];

    $content = app(AdminReportExporter::class)->facilitiesXlsx(new Collection([$facility]));
    $temporaryPath = tempnam(sys_get_temp_dir(), 'xlsx-test-');
    file_put_contents($temporaryPath, $content);

    $zip = new ZipArchive();

    expect($zip->open($temporaryPath))->toBeTrue()
        ->and($zip->locateName('[Content_Types].xml'))->not->toBeFalse()
        ->and($zip->locateName('xl/workbook.xml'))->not->toBeFalse()
        ->and($zip->locateName('xl/worksheets/sheet1.xml'))->not->toBeFalse()
        ->and($zip->getFromName('xl/worksheets/sheet1.xml'))
        ->toContain('CLSU Auditorium', 'autoFilter', 'state="frozen"')
        ->and($zip->getFromName('xl/styles.xml'))
        ->toContain('FF009639');

    $zip->close();
    unlink($temporaryPath);
});
