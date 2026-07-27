<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AdminReportExporter
{
    public function facilitiesPdf(Collection $facilities, string $scopeLabel): string
    {
        $headers = ['ID', 'Facility', 'Type', 'Price (PHP)', 'Capacity', 'Location', 'Office', 'Status'];
        $widths = [12, 43, 27, 25, 20, 42, 58, 25];
        $rows = $facilities->map(fn ($facility) => [
            $facility->FID,
            $facility->Facility_Name,
            $facility->facility_type ? ucfirst($facility->facility_type) : 'N/A',
            number_format((float) $facility->Price, 2),
            $facility->Capacity ?? 'N/A',
            $facility->Location ?? 'N/A',
            $facility->Office ?? 'N/A',
            $facility->Status,
        ])->all();

        return $this->tablePdf('Facility List', $scopeLabel, $headers, $widths, $rows);
    }

    public function requestsPdf(Collection $requests, string $scopeLabel): string
    {
        $headers = ['ID', 'Requester', 'Facility', 'Date', 'Time', 'Attendees', 'Status', 'Purpose'];
        $widths = [12, 38, 45, 25, 29, 21, 27, 80];
        $rows = $requests->map(fn ($request) => [
            $request->RID,
            $request->user?->name ?? 'N/A',
            $request->facility?->Facility_Name ?? 'N/A',
            $request->Proposed_Date?->format('Y-m-d') ?? 'N/A',
            ($request->Proposed_Start_Time?->format('H:i') ?? '--:--').'-'.($request->Proposed_End_Time?->format('H:i') ?? '--:--'),
            $request->Capacity ?? 'N/A',
            $request->Review_Requested_At && $request->Status === 'Pending' ? 'Needs Revision' : $request->Status,
            $request->Purpose,
        ])->all();

        return $this->tablePdf('Facility Request List', $scopeLabel, $headers, $widths, $rows);
    }

    private function tablePdf(string $title, string $scopeLabel, array $headers, array $widths, array $rows): string
    {
        $pdf = new class('L', 'mm', 'A4') extends \FPDF {
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
                $this->Cell(0, 5, 'CLSU Uni Space | Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
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
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
    }

    private function truncate(string $value, int $width): string
    {
        $limit = max(4, (int) floor($width / 1.8));

        return strlen($value) > $limit
            ? substr($value, 0, $limit - 3).'...'
            : $value;
    }
}
