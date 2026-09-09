<?php

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;

it('exports complete request user and amenity reports in every format', function () {
    $admin = User::factory()->create([
        'name' => 'Report Administrator',
        'user_type' => 'super_admin',
        'is_active' => true,
        'clsu_id' => '10-0001',
        'contact_number' => '09123456789',
        'office' => 'Administration Office',
        'address' => 'CLSU Main Campus',
    ]);
    $requester = User::factory()->create([
        'name' => 'Complete Report User',
        'user_type' => 'user',
        'is_active' => true,
        'clsu_id' => '10-0002',
        'contact_number' => '09987654321',
        'office' => 'Student Organization',
        'address' => 'Science City of Munoz',
    ]);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Administrative Report Hall',
        'facility_type' => 'conference',
        'Office' => 'Administration Office',
        'Description' => 'Report test facility.',
        'Location' => 'CLSU Main Campus',
        'Capacity' => 150,
        'Status' => 'Available',
    ]);
    $admin->facilities()->attach($facility->FID);

    $event = Events::query()->create([
        'Event_Title' => 'Complete Report Event',
        'Description' => 'Event used to verify report exports.',
        'Type_Event' => 'Academic',
    ]);
    $amenity = Amenities::query()->create([
        'created_by' => $admin->id,
        'name' => 'Complete Report Projector',
        'Description' => 'Projector used for complete report testing.',
        'Status' => 'Available',
        'reservation_limit' => 2,
    ]);
    $facility->amenities()->attach($amenity->AID);

    $request = Requests::query()->create([
        'Event_ID' => $event->EID,
        'User_ID' => $requester->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => '2026-10-10',
        'Proposed_End_Date' => '2026-10-11',
        'Proposed_Start_Time' => '09:00:00',
        'Proposed_End_Time' => '11:00:00',
        'Daily_Schedules' => [
            ['date' => '2026-10-10', 'start' => '09:00', 'end' => '11:00'],
            ['date' => '2026-10-11', 'start' => '13:00', 'end' => '15:00'],
        ],
        'Status' => 'Pending',
        'Review_Notes' => 'Please provide the final attendee list.',
        'Review_Requested_At' => now(),
        'Purpose' => 'Complete administrative report verification.',
        'Purpose_Categories' => ['Academic activity', 'Training'],
        'Other_Purpose' => 'Reporting exercise',
        'Reservation_Frequency' => 'First time',
        'Facility_Importance' => 'Important',
        'Requirements_Fit' => 'Meets requirements',
        'Reserve_Again_Intent' => 'Yes',
        'Capacity' => 75,
        'attachment_path' => 'request-attachments/report.pdf',
    ]);
    $request->amenities()->attach($amenity->AID);

    $this->actingAs($admin);

    $csvExpectations = [
        'exports.requests.csv' => ['Request Type', 'Daily Schedule', 'Purpose Categories', 'Complete Report Event', 'Complete Report Projector'],
        'exports.users.csv' => ['CLSU ID', 'Assigned Facilities', 'Registration Status', 'Complete Report User', 'Administrative Report Hall'],
        'exports.amenities.csv' => ['Facility Count', 'Request Usage', 'Complete Report Projector', 'Report Administrator'],
    ];

    foreach ($csvExpectations as $route => $expectedValues) {
        $response = $this->get(route($route));
        $response->assertOk();
        $content = $response->streamedContent();

        foreach ($expectedValues as $expectedValue) {
            expect($content)->toContain($expectedValue);
        }
    }

    $pdfExpectations = [
        'exports.requests.pdf' => ['FACILITY REQUEST REPORT', 'Complete Report Event', 'Daily Schedule', 'Complete Report Projector'],
        'exports.users.pdf' => ['USER ACCOUNT REPORT', 'Complete Report User', 'Assigned Facilities', 'Administrative Report Hall'],
        'exports.amenities.pdf' => ['AMENITY REPORT', 'Complete Report Projector', 'Request Usage', 'Report Administrator'],
    ];

    foreach ($pdfExpectations as $route => $expectedValues) {
        $response = $this->get(route($route));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        expect($response->getContent())->toStartWith('%PDF-');

        foreach ($expectedValues as $expectedValue) {
            expect($response->getContent())->toContain($expectedValue);
        }
    }

    $xlsxExpectations = [
        'exports.requests.xlsx' => ['Request Type', 'Daily Schedule', 'Complete Report Event', 'Complete Report Projector'],
        'exports.users.xlsx' => ['CLSU ID', 'Registration Status', 'Complete Report User', 'Administrative Report Hall'],
        'exports.amenities.xlsx' => ['Facility Count', 'Request Usage', 'Complete Report Projector', 'Report Administrator'],
    ];

    foreach ($xlsxExpectations as $route => $expectedValues) {
        $response = $this->get(route($route));
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'administrative-report-test-');
        file_put_contents($temporaryPath, $response->getContent());
        $archive = new ZipArchive;

        try {
            expect($archive->open($temporaryPath))->toBeTrue();
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');

            foreach ($expectedValues as $expectedValue) {
                expect($worksheet)->toContain($expectedValue);
            }
        } finally {
            $archive->close();
            @unlink($temporaryPath);
        }
    }
});
