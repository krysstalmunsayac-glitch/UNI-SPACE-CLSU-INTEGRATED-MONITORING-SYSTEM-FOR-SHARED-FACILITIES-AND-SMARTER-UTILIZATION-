<?php

use App\Services\AdminReportExporter;

it('renders analytics KPI cards without overflowing the report', function () {
    $data = [
        'kpis' => [
            'Facilities' => 33,
            'Pending Requests' => 4,
            'Time Utilization' => '0.2%',
            'Approval Rate' => '80%',
            'Total Requests' => 20,
        ],
        'facilityUtilizationRates' => [
            ['facility' => 'Test Facility', 'rate' => 12.5],
        ],
        'bookingDemandHeatmap' => [
            'Monday' => [8 => 2],
        ],
        'capacityUtilization' => [],
        'cancellationRates' => [],
        'facilityRatings' => [],
        'amenityDemand' => [],
    ];

    $pdf = app(AdminReportExporter::class)->analyticsPdf(
        $data,
        'All facilities',
        'Sep 1, 2026 - Sep 23, 2026',
    );

    expect($pdf)
        ->toStartWith('%PDF-')
        ->toContain('FACILITY ANALYTICS')
        ->toContain('Approval Rate')
        ->toContain('Total Requests');
});
