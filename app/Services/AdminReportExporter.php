<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AdminReportExporter
{
    public function facilityHeaders(): array
    {
        return [
            'Facility ID', 'Facility Name', 'Type', 'Access Type', 'Price (PHP)',
            'Rates', 'Capacity', 'Location', 'Latitude', 'Longitude', 'Office',
            'Description', 'Amenities', 'Protocols and Guidelines', 'Contact Details',
            'Reference URL', 'Data Notes', 'Status',
        ];
    }

    public function facilityRow($facility): array
    {
        return [
            $facility->FID,
            $facility->Facility_Name,
            $facility->facility_type ? ucfirst($facility->facility_type) : '',
            $facility->Access_Type ?? '',
            $facility->Price === null ? '' : (float) $facility->Price,
            $facility->rates ?: ($facility->Rate_Details ?? ''),
            $facility->Capacity ?? '',
            $facility->Location ?? '',
            $facility->Latitude ?? '',
            $facility->Longitude ?? '',
            $facility->Office ?? '',
            $facility->Description ?? '',
            $facility->amenities->pluck('name')->join(', '),
            $facility->protocols_and_guidelines ?: ($facility->Protocols ?? ''),
            $facility->Contact_Details ?? '',
            $facility->Reference_URL ?? '',
            $facility->Data_Notes ?? '',
            $facility->Status ?? '',
        ];
    }

    public function requestHeaders(): array
    {
        return [
            'Request ID', 'Request Type', 'Requester', 'CLSU ID', 'Email', 'Contact Number',
            'Organization or Office', 'Created By', 'Facility', 'Event', 'Event Type',
            'First Day', 'Last Day', 'Start Time', 'End Time', 'Daily Schedule',
            'Attendees', 'Amenities', 'Status', 'Purpose', 'Purpose Categories',
            'Other Purpose', 'Reservation Frequency', 'Facility Importance',
            'Requirements Fit', 'Reserve Again Intent', 'Review Requested At',
            'Review Notes', 'Rejection Reason', 'Cancellation Reason', 'Attachment',
            'Submitted At', 'Updated At',
        ];
    }

    public function requestRow($request): array
    {
        return [
            $request->RID,
            $request->Is_Guest_Booking ? 'Guest booking' : 'Registered user',
            $request->requesterName(),
            $request->Is_Guest_Booking ? '' : ($request->user?->clsu_id ?? ''),
            $request->requesterEmail() ?? '',
            $request->Is_Guest_Booking ? ($request->Guest_Contact ?? '') : ($request->user?->contact_number ?? ''),
            $request->Is_Guest_Booking ? ($request->Guest_Organization ?? '') : ($request->user?->office ?? ''),
            $request->creator?->name ?? ($request->Is_Guest_Booking ? '' : 'Self-service'),
            $request->facility?->Facility_Name ?? '',
            $request->event?->Event_Title ?? '',
            $request->event?->Type_Event ?? '',
            $request->Proposed_Date?->format('Y-m-d') ?? '',
            ($request->Proposed_End_Date ?? $request->Proposed_Date)?->format('Y-m-d') ?? '',
            $request->Proposed_Start_Time?->format('H:i') ?? '',
            $request->Proposed_End_Time?->format('H:i') ?? '',
            $this->dailyScheduleText($request->Daily_Schedules),
            $request->Capacity ?? '',
            $request->amenities->pluck('name')->join(', '),
            $request->Review_Requested_At && $request->Status === 'Pending' ? 'Needs Revision' : ($request->Status ?? ''),
            $request->Purpose ?? '',
            collect($request->Purpose_Categories ?? [])->join(', '),
            $request->Other_Purpose ?? '',
            $request->Reservation_Frequency ?? '',
            $request->Facility_Importance ?? '',
            $request->Requirements_Fit ?? '',
            $request->Reserve_Again_Intent ?? '',
            $this->dateTimeText($request->Review_Requested_At),
            $request->Review_Notes ?? '',
            $request->Rejection_Reason ?? '',
            $request->Cancellation_Reason ?? '',
            $request->attachment_path ? 'Attached' : 'None',
            $this->dateTimeText($request->Created_at),
            $this->dateTimeText($request->Updated_at),
        ];
    }

    public function userHeaders(): array
    {
        return [
            'User ID', 'CLSU ID', 'Name', 'Email', 'Role', 'Contact Number', 'Office',
            'Address', 'Assigned Facilities', 'Account Status', 'Registration Status',
            'Email Verified At', 'Invitation Sent At', 'Invitation Expires At',
            'Invitation Revoked At', 'Joined At', 'Updated At',
        ];
    }

    public function userRow($user): array
    {
        return [
            $user->id,
            $user->clsu_id ?? '',
            $user->name,
            $user->email,
            $user->roleLabel(),
            $user->contact_number ?? '',
            $user->office ?? '',
            $user->address ?? '',
            $user->facilities->pluck('Facility_Name')->join(', '),
            $user->is_active ? 'Active' : 'Inactive',
            $user->invitationStatus(),
            $this->dateTimeText($user->email_verified_at),
            $this->dateTimeText($user->invitation_sent_at),
            $this->dateTimeText($user->invitation_expires_at),
            $this->dateTimeText($user->invitation_revoked_at),
            $this->dateTimeText($user->created_at),
            $this->dateTimeText($user->updated_at),
        ];
    }

    public function amenityHeaders(): array
    {
        return [
            'Amenity ID', 'Name', 'Description', 'Status', 'Reservation Limit',
            'Facilities', 'Facility Count', 'Request Usage', 'Created By', 'Created At', 'Updated At',
        ];
    }

    public function amenityRow($amenity): array
    {
        return [
            $amenity->AID,
            $amenity->name,
            $amenity->Description ?? '',
            $amenity->Status ?? '',
            $amenity->reservation_limit ?? 'Unlimited',
            $amenity->facilities->pluck('Facility_Name')->join(', '),
            $amenity->facilities->count(),
            $amenity->requests_count ?? $amenity->requests()->count(),
            $amenity->creator?->name ?? '',
            $this->dateTimeText($amenity->Created_at),
            $this->dateTimeText($amenity->Updated_at),
        ];
    }

    public function analyticsPdf(array $data, string $scopeLabel, string $dateLabel): string
    {
        $pdf = new class('L', 'mm', 'A4') extends \FPDF
        {
            public string $scope = '';

            public string $period = '';

            public function Header(): void
            {
                $this->SetFont('Arial', 'B', 17);
                $this->SetTextColor(5, 105, 75);
                $this->Cell(0, 8, 'SIEL SPACE - FACILITY ANALYTICS REPORT', 0, 1);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(80, 90, 100);
                $this->Cell(0, 5, 'Period: '.$this->period.' | Scope: '.$this->scope.' | Generated: '.now()->format('Y-m-d H:i'), 0, 1);
                $this->Ln(3);
            }

            public function Footer(): void
            {
                $this->SetY(-10);
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'SIEL SPACE | Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
            }

            public function section(string $title, string $description): void
            {
                if ($this->GetY() > 175) {
                    $this->AddPage();
                }
                $this->SetFont('Arial', 'B', 12);
                $this->SetTextColor(20, 30, 35);
                $this->Cell(0, 7, $title, 0, 1);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(90, 100, 110);
                $this->MultiCell(0, 4, $description);
                $this->Ln(2);
            }

            public function bars(array $rows, string $valueKey, float $maximum, string $suffix = ''): void
            {
                $maximum = max(1, $maximum);
                foreach ($rows as $row) {
                    if ($this->GetY() > 190) {
                        $this->AddPage();
                    }
                    $label = (string) ($row['facility'] ?? $row['amenity'] ?? 'Unknown');
                    $value = (float) ($row[$valueKey] ?? 0);
                    $this->SetFont('Arial', '', 8);
                    $this->SetTextColor(45, 55, 65);
                    $this->Cell(62, 6, substr($label, 0, 34), 0, 0);
                    $x = $this->GetX();
                    $y = $this->GetY() + 1;
                    $this->SetFillColor(232, 240, 237);
                    $this->Rect($x, $y, 160, 4, 'F');
                    $this->SetFillColor(16, 185, 129);
                    $this->Rect($x, $y, 160 * min(1, $value / $maximum), 4, 'F');
                    $this->SetX($x + 163);
                    $this->Cell(28, 6, number_format($value, 1).$suffix, 0, 1, 'R');
                }
                $this->Ln(2);
            }
        };

        $pdf->scope = $this->pdfText($scopeLabel);
        $pdf->period = $this->pdfText($dateLabel);
        $pdf->AliasNbPages();
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetCompression(false);
        $pdf->AddPage();

        $pdf->section('Executive Summary', 'Key indicators for facility demand, utilization, request decisions, and administrative performance.');
        $kpis = $data['kpis'];
        foreach ($kpis as $label => $value) {
            $pdf->SetFillColor(242, 247, 245);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(5, 105, 75);
            $pdf->Cell(54, 8, $this->pdfText($label), 1, 0, 'L', true);
            $pdf->SetTextColor(25, 30, 35);
            $pdf->Cell(38, 8, $this->pdfText((string) $value), 1, 0, 'R');
        }
        $pdf->Ln(12);

        $utilization = $data['facilityUtilizationRates'] ?? [];
        $pdf->section('Facility Time Utilization', 'Booked schedule hours divided by available hours using the 8:00 AM-6:00 PM daily baseline. Higher percentages indicate more intensive use of the available schedule.');
        $pdf->bars($utilization, 'rate', 100, '%');

        $pdf->section('Booking Demand Heatmap', 'Approved bookings by weekday and hourly slot. Larger values identify the periods with the greatest demand.');
        $hours = range(8, 17);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(28, 6, 'Day', 1);
        foreach ($hours as $hour) {
            $pdf->Cell(22, 6, date('g A', mktime($hour)), 1, 0, 'C');
        }
        $pdf->Ln();
        foreach (($data['bookingDemandHeatmap'] ?? []) as $day => $counts) {
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(28, 6, $day, 1);
            foreach ($hours as $hour) {
                $count = (int) ($counts[$hour] ?? 0);
                $pdf->SetFillColor($count ? 167 : 245, $count ? 243 : 248, $count ? 208 : 247);
                $pdf->Cell(22, 6, (string) $count, 1, 0, 'C', true);
            }
            $pdf->Ln();
        }

        $pdf->AddPage();
        $pdf->section('Capacity Utilization', 'Expected attendees divided by maximum facility capacity. This measures how well the physical size of each facility matches booking demand.');
        $pdf->bars($data['capacityUtilization'] ?? [], 'rate', 100, '%');
        $pdf->section('Cancellation Rate', 'Cancelled requests divided by all requests for each facility in the selected period.');
        $pdf->bars($data['cancellationRates'] ?? [], 'rate', 100, '%');
        $pdf->section('Facility Ratings', 'Average facility rating submitted through completed-request feedback, on a five-star scale.');
        $pdf->bars($data['facilityRatings'] ?? [], 'rating', 5, ' / 5');
        $pdf->section('Amenity Demand', 'Number of reservation requests that included each amenity. This supports purchasing and maintenance decisions.');
        $amenities = $data['amenityDemand'] ?? [];
        $pdf->bars($amenities, 'count', max(1, (float) collect($amenities)->max('count')), ' requests');

        return $pdf->Output('S');
    }

    public function facilitiesXlsx(Collection $facilities): string
    {
        return $this->xlsx(
            'Facilities',
            $this->facilityHeaders(),
            $facilities->map(fn ($facility) => $this->facilityRow($facility))->all(),
            [12, 34, 18, 16, 16, 55, 12, 35, 16, 16, 30, 55, 45, 60, 40, 50, 50, 16],
        );
    }

    public function requestsXlsx(Collection $requests): string
    {
        return $this->xlsx(
            'Facility Requests',
            $this->requestHeaders(),
            $requests->map(fn ($request) => $this->requestRow($request))->all(),
            [12, 18, 28, 16, 32, 20, 28, 24, 30, 30, 18, 14, 14, 13, 13, 48, 12, 42, 18, 50, 35, 25, 22, 20, 20, 22, 22, 45, 45, 45, 14, 22, 22],
        );
    }

    public function usersXlsx(Collection $users): string
    {
        return $this->xlsx(
            'Users',
            $this->userHeaders(),
            $users->map(fn ($user) => $this->userRow($user))->all(),
            [12, 16, 28, 34, 20, 20, 28, 42, 50, 18, 22, 22, 22, 22, 22, 22, 22],
        );
    }

    public function amenitiesXlsx(Collection $amenities): string
    {
        return $this->xlsx(
            'Amenities',
            $this->amenityHeaders(),
            $amenities->map(fn ($amenity) => $this->amenityRow($amenity))->all(),
            [14, 28, 55, 16, 20, 55, 16, 16, 24, 22, 22],
        );
    }

    public function facilitiesPdf(Collection $facilities, string $scopeLabel): string
    {
        $pdf = new class('P', 'mm', 'A4') extends \FPDF
        {
            public string $scopeLabel = '';

            public function Header(): void
            {
                $this->SetFont('Arial', 'B', 16);
                $this->SetTextColor(0, 107, 43);
                $this->Cell(0, 8, 'SIEL SPACE - FACILITY REPORT', 0, 1);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(90, 100, 105);
                $this->Cell(0, 5, 'Scope: '.$this->scopeLabel.' | Generated: '.now()->format('Y-m-d H:i'), 0, 1);
                $this->Ln(4);
            }

            public function Footer(): void
            {
                $this->SetY(-10);
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'SIEL SPACE | Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
            }

            public function facilityTitle(string $title): void
            {
                if ($this->GetY() > 245) {
                    $this->AddPage();
                }

                $this->SetFillColor(0, 107, 43);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('Arial', 'B', 10);
                $this->MultiCell(0, 7, $title, 0, 'L', true);
                $this->Ln(1);
            }

            public function detailRow(string $label, string $value): void
            {
                if ($this->GetY() > 270) {
                    $this->AddPage();
                }

                $startY = $this->GetY();
                $this->SetFont('Arial', 'B', 8);
                $this->SetTextColor(45, 55, 65);
                $this->Cell(38, 5, $label, 0, 0);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(30, 35, 38);
                $this->MultiCell(0, 5, $value !== '' ? $value : 'N/A');

                if ($this->GetY() === $startY) {
                    $this->Ln(5);
                }
            }
        };

        $pdf->scopeLabel = $this->pdfText($scopeLabel).' | Records: '.$facilities->count();
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetCompression(false);
        $pdf->AddPage();

        if ($facilities->isEmpty()) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 12, 'No facilities found.', 1, 1, 'C');
        }

        foreach ($facilities as $facility) {
            $pdf->facilityTitle($this->pdfText(sprintf('FAC-%05d - %s', $facility->FID, $facility->Facility_Name)));

            $details = [
                'Facility type' => $facility->facility_type ? ucfirst($facility->facility_type) : 'N/A',
                'Access type' => $facility->Access_Type ?? 'N/A',
                'Status' => $facility->Status ?? 'N/A',
                'Office' => $facility->Office ?? 'N/A',
                'Capacity' => $facility->Capacity ?? 'N/A',
                'Base price (PHP)' => $facility->Price === null ? 'N/A' : number_format((float) $facility->Price, 2),
                'Rates' => $facility->rates ?: ($facility->Rate_Details ?? 'N/A'),
                'Location' => $facility->Location ?? 'N/A',
                'Coordinates' => $facility->Latitude !== null && $facility->Longitude !== null
                    ? $facility->Latitude.', '.$facility->Longitude
                    : 'N/A',
                'Description' => $facility->Description ?? 'N/A',
                'Amenities' => $facility->amenities->pluck('name')->join(', ') ?: 'None listed',
                'Protocols and guidelines' => $facility->protocols_and_guidelines ?: ($facility->Protocols ?? 'N/A'),
                'Contact details' => $facility->Contact_Details ?? 'N/A',
                'Reference URL' => $facility->Reference_URL ?? 'N/A',
                'Data notes' => $facility->Data_Notes ?? 'N/A',
            ];

            foreach ($details as $label => $value) {
                $pdf->detailRow($this->pdfText($label), $this->pdfText((string) $value));
            }

            $pdf->Ln(4);
        }

        return $pdf->Output('S');
    }

    public function requestsPdf(Collection $requests, string $scopeLabel): string
    {
        $headers = $this->requestHeaders();
        $records = $requests->map(function ($request) use ($headers): array {
            $row = $this->requestRow($request);

            return [
                'title' => sprintf('REQ-%05d - %s', $request->RID, $request->requesterName()),
                'details' => array_combine(array_slice($headers, 1), array_slice($row, 1)),
            ];
        })->all();

        return $this->detailPdf('FACILITY REQUEST REPORT', $scopeLabel, $records);
    }

    public function usersPdf(Collection $users): string
    {
        $headers = $this->userHeaders();
        $records = $users->map(function ($user) use ($headers): array {
            $row = $this->userRow($user);

            return [
                'title' => sprintf('USR-%05d - %s', $user->id, $user->name),
                'details' => array_combine(array_slice($headers, 1), array_slice($row, 1)),
            ];
        })->all();

        return $this->detailPdf('USER ACCOUNT REPORT', 'All active and inactive accounts', $records);
    }

    public function amenitiesPdf(Collection $amenities): string
    {
        $headers = $this->amenityHeaders();
        $records = $amenities->map(function ($amenity) use ($headers): array {
            $row = $this->amenityRow($amenity);

            return [
                'title' => sprintf('AMN-%05d - %s', $amenity->AID, $amenity->name),
                'details' => array_combine(array_slice($headers, 1), array_slice($row, 1)),
            ];
        })->all();

        return $this->detailPdf('AMENITY REPORT', 'All active amenities', $records);
    }

    private function detailPdf(string $title, string $scopeLabel, array $records): string
    {
        $pdf = new class('P', 'mm', 'A4') extends \FPDF
        {
            public string $reportTitle = '';

            public string $scopeLabel = '';

            public function Header(): void
            {
                $this->SetFont('Arial', 'B', 16);
                $this->SetTextColor(0, 107, 43);
                $this->Cell(0, 8, $this->reportTitle, 0, 1);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(90, 100, 105);
                $this->Cell(0, 5, 'Scope: '.$this->scopeLabel.' | Generated: '.now()->format('Y-m-d H:i'), 0, 1);
                $this->Ln(4);
            }

            public function Footer(): void
            {
                $this->SetY(-10);
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'SIEL SPACE | Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
            }

            public function recordTitle(string $title): void
            {
                if ($this->GetY() > 245) {
                    $this->AddPage();
                }

                $this->SetFillColor(0, 107, 43);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('Arial', 'B', 10);
                $this->MultiCell(0, 7, $title, 0, 'L', true);
                $this->Ln(1);
            }

            public function detailRow(string $label, string $value): void
            {
                if ($this->GetY() > 270) {
                    $this->AddPage();
                }

                $this->SetFont('Arial', 'B', 8);
                $this->SetTextColor(45, 55, 65);
                $this->Cell(42, 5, $label, 0, 0);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(30, 35, 38);
                $this->MultiCell(0, 5, $value !== '' ? $value : 'N/A');
            }
        };

        $pdf->reportTitle = $this->pdfText('SIEL SPACE - '.$title);
        $pdf->scopeLabel = $this->pdfText($scopeLabel).' | Records: '.count($records);
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetCompression(false);
        $pdf->AddPage();

        if ($records === []) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 12, 'No records found.', 1, 1, 'C');
        }

        foreach ($records as $record) {
            $pdf->recordTitle($this->pdfText($record['title']));

            foreach ($record['details'] as $label => $value) {
                $pdf->detailRow($this->pdfText((string) $label), $this->pdfText((string) ($value === '' ? 'N/A' : $value)));
            }

            $pdf->Ln(4);
        }

        return $pdf->Output('S');
    }

    private function tablePdf(string $title, string $scopeLabel, array $headers, array $widths, array $rows): string
    {
        $pdf = new class('L', 'mm', 'A4') extends \FPDF
        {
            public string $reportTitle = '';

            public string $scopeLabel = '';

            public array $headers = [];

            public array $widths = [];

            public function Header(): void
            {
                $this->SetFont('Arial', 'B', 16);
                $this->SetTextColor(5, 75, 55);
                $this->Cell(0, 8, $this->reportTitle, 0, 1);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(90, 100, 105);
                $this->Cell(0, 5, 'Scope: '.$this->scopeLabel.' | Generated: '.now()->format('Y-m-d H:i'), 0, 1);
                $this->Ln(3);
                $this->SetFillColor(5, 105, 75);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('Arial', 'B', 7);

                foreach ($this->headers as $index => $header) {
                    $this->Cell($this->widths[$index], 7, $header, 1, 0, 'L', true);
                }

                $this->Ln();
            }

            public function Footer(): void
            {
                $this->SetY(-10);
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'SIEL SPACE | Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
            }
        };

        $pdf->reportTitle = $title;
        $pdf->scopeLabel = $this->pdfText($scopeLabel).' | Records: '.count($rows);
        $pdf->headers = $headers;
        $pdf->widths = $widths;
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(30, 35, 38);

        foreach ($rows as $rowIndex => $row) {
            $fill = $rowIndex % 2 === 1;
            $pdf->SetFillColor(242, 247, 245);

            foreach ($row as $index => $value) {
                $pdf->Cell(
                    $widths[$index],
                    7,
                    $this->truncate($this->pdfText((string) $value), $widths[$index]),
                    1,
                    0,
                    'L',
                    $fill
                );
            }

            $pdf->Ln();
        }

        if ($rows === []) {
            $pdf->Cell(array_sum($widths), 12, 'No records found.', 1, 1, 'C');
        }

        return $pdf->Output('S');
    }

    private function pdfText(string $value): string
    {
        $value = str_replace('₱', 'PHP ', $value);

        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
    }

    private function dailyScheduleText(?array $schedules): string
    {
        return collect($schedules ?? [])->map(function ($schedule): string {
            $date = $schedule['date'] ?? 'Unknown date';
            $start = $schedule['start'] ?? '--:--';
            $end = $schedule['end'] ?? '--:--';

            return "{$date} {$start}-{$end}";
        })->join('; ');
    }

    private function dateTimeText($value): string
    {
        if (! $value) {
            return '';
        }

        return method_exists($value, 'format')
            ? $value->format('Y-m-d H:i')
            : (string) $value;
    }

    private function truncate(string $value, int $width): string
    {
        $limit = max(4, (int) floor($width / 1.8));

        return strlen($value) > $limit
            ? substr($value, 0, $limit - 3).'...'
            : $value;
    }

    private function xlsx(string $sheetName, array $headers, array $rows, array $widths): string
    {
        $columnName = function (int $index): string {
            $name = '';

            while ($index >= 0) {
                $name = chr(($index % 26) + 65).$name;
                $index = intdiv($index, 26) - 1;
            }

            return $name;
        };

        $xmlValue = fn ($value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $sheetRows = [];
        $allRows = array_merge([$headers], $rows);

        foreach ($allRows as $rowIndex => $row) {
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $reference = $columnName($columnIndex).($rowIndex + 1);
                $style = $rowIndex === 0 ? 1 : ($columnIndex === 3 && $sheetName === 'Facilities' ? 2 : 0);

                if ($rowIndex > 0 && (is_int($value) || is_float($value))) {
                    $cells[] = '<c r="'.$reference.'" s="'.$style.'" t="n"><v>'.$value.'</v></c>';
                } else {
                    $cells[] = '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$xmlValue($value ?? '').'</t></is></c>';
                }
            }

            $sheetRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        $columns = collect($widths)
            ->map(fn ($width, $index) => '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>')
            ->implode('');
        $lastCell = $columnName(count($headers) - 1).max(1, count($allRows));
        $safeSheetName = $xmlValue($sheetName);

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.$columns.'</cols><sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'<autoFilter ref="A1:'.$lastCell.'"/></worksheet>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$safeSheetName.'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Aptos"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Aptos"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF009639"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml' => $worksheet,
        ];

        $temporaryPath = tempnam(sys_get_temp_dir(), 'silesyu-space-xlsx-');
        $zip = new \ZipArchive;
        $zip->open($temporaryPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }

        $zip->close();
        $content = file_get_contents($temporaryPath);
        unlink($temporaryPath);

        return $content;
    }
}
