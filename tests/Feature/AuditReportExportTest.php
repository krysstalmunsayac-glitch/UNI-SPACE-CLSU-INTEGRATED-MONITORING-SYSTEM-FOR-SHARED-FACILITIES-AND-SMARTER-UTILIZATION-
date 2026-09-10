<?php

use App\Models\AuditLog;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

it('exports audit history with actor and before-and-after values in every format', function () {
    $admin = User::factory()->create([
        'name' => 'Audit Export Administrator',
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $facility = Facilities::create(['Facility_Name' => 'Audit Report Hall', 'Status' => 'Available']);
    $request = Requests::withoutEvents(fn () => Requests::create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => '2026-10-20',
        'Proposed_End_Date' => '2026-10-20',
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Audit export verification',
    ]));

    $this->actingAs($admin);
    AuditLog::recordRequest(
        $request,
        'request_updated',
        'Changed the reservation purpose.',
        ['Purpose' => 'Original audit value'],
        ['Purpose' => 'Updated audit value'],
    );

    $csv = $this->get(route('exports.audits.csv'))->assertOk()->streamedContent();
    expect($csv)->toContain('Original Values')
        ->toContain('Updated Values')
        ->toContain('Audit Export Administrator')
        ->toContain('Original audit value')
        ->toContain('Updated audit value');

    $pdf = $this->get(route('exports.audits.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->getContent();
    expect($pdf)->toStartWith('%PDF-')->toContain('AUDIT HISTORY REPORT');

    $xlsx = $this->get(route('exports.audits.xlsx'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->getContent();
    $temporaryPath = tempnam(sys_get_temp_dir(), 'audit-history-report-');
    file_put_contents($temporaryPath, $xlsx);
    $archive = new ZipArchive;

    try {
        expect($archive->open($temporaryPath))->toBeTrue();
        $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
        expect($worksheet)->toContain('Original Values')
            ->toContain('Updated Values')
            ->toContain('Original audit value')
            ->toContain('Updated audit value');
    } finally {
        $archive->close();
        @unlink($temporaryPath);
    }
});
