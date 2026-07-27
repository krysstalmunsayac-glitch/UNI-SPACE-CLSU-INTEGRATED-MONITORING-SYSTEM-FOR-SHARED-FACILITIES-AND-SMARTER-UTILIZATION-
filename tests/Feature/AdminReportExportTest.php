<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

it('exports facility lists as CSV and PDF for a super admin', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    Facilities::create([
        'Facility_Name' => 'CSV Export Hall',
        'facility_type' => 'conference',
        'Price' => 2500,
        'Capacity' => 120,
        'Location' => 'Main Campus',
        'Office' => 'Administration',
        'Status' => 'Available',
    ]);

    $csv = $this->actingAs($superAdmin)->get(route('exports.facilities.csv'));
    $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv->streamedContent())->toContain('CSV Export Hall');

    $pdf = $this->actingAs($superAdmin)->get(route('exports.facilities.pdf'));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($pdf->getContent())->toStartWith('%PDF-')
        ->and($pdf->getContent())->toContain('CSV Export Hall');
});

it('limits office admin exports to assigned facilities and their requests', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);
    $requester = User::factory()->create(['user_type' => 'user']);
    $proposedDate = now()->addDay();

    $assigned = Facilities::create([
        'Facility_Name' => 'Assigned Export Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $other = Facilities::create([
        'Facility_Name' => 'Hidden Export Hall',
        'Price' => 100,
        'Status' => 'Available',
    ]);
    $assigned->assignedAdmins()->attach($admin->id);

    Requests::create([
        'User_ID' => $requester->id,
        'Facility_ID' => $assigned->FID,
        'Proposed_Date' => $proposedDate->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Visible export request',
    ]);
    Requests::create([
        'User_ID' => $requester->id,
        'Facility_ID' => $other->FID,
        'Proposed_Date' => $proposedDate->toDateString(),
        'Proposed_Start_Time' => '11:00',
        'Proposed_End_Time' => '12:00',
        'Status' => 'Pending',
        'Purpose' => 'Hidden export request',
    ]);

    $facilityCsv = $this->actingAs($admin)->get(route('exports.facilities.csv'));
    $facilityContent = $facilityCsv->streamedContent();
    expect($facilityContent)->toContain('Assigned Export Hall')
        ->not->toContain('Hidden Export Hall');

    $requestCsv = $this->actingAs($admin)->get(route('exports.requests.csv'));
    $requestContent = $requestCsv->streamedContent();
    expect($requestContent)->toContain('Visible export request')
        ->toContain('"=""'.$proposedDate->format('M d, Y').'"""')
        ->not->toContain('Hidden export request');
});
