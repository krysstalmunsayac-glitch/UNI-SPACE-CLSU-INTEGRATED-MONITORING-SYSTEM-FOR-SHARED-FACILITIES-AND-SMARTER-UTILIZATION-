<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;

it('renders operational analytics for a super administrator', function () {
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $facility = Facilities::query()->create([
        'Facility_Name' => 'Analytics Hall',
        'Capacity' => 100,
        'Status' => 'Available',
    ]);
    $request = Requests::withoutEvents(fn () => Requests::query()->create([
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->toDateString(),
        'Proposed_End_Date' => today()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Approved',
        'Purpose' => 'Dashboard analytics',
        'Capacity' => 75,
    ]));
    Schedule::query()->create([
        'Request_ID' => $request->RID,
        'Date' => today()->toDateString(),
        'Start_Time' => '09:00',
        'End_Time' => '11:00',
        'Status' => 'Booked',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.superadmin'))
        ->assertOk()
        ->assertSee('Facility Utilization Rate')
        ->assertSee('Booking Demand Heatmap')
        ->assertSee('Analytics Hall');

    $pdf = $this->get(route('dashboard.analytics.pdf'));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($pdf->getContent())->toStartWith('%PDF-');
});

it('limits office administrator analytics to assigned facilities', function () {
    $admin = User::factory()->create(['user_type' => 'admin', 'is_active' => true]);
    $assigned = Facilities::query()->create(['Facility_Name' => 'Assigned Analytics Hall', 'Capacity' => 80]);
    Facilities::query()->create(['Facility_Name' => 'Hidden Analytics Hall', 'Capacity' => 80]);
    $admin->facilities()->attach($assigned->FID);

    $this->actingAs($admin)
        ->get(route('dashboard.officeadmin'))
        ->assertOk()
        ->assertSee('Assigned Analytics Hall')
        ->assertDontSee('Hidden Analytics Hall')
        ->assertDontSee('Download PDF');

    $this->get(route('dashboard.analytics.pdf'))->assertForbidden();
});
