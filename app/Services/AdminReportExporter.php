<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AdminReportExporter
{
    public function facilitiesXlsx(Collection $facilities): string
    {
        return $this->xlsx(
            'Facilities',
            ['Facility ID', 'Facility Name', 'Type', 'Price (PHP)', 'Capacity', 'Location', 'Office', 'Status'],
            $facilities->map(fn ($facility) => [
                $facility->FID,
                $facility->Facility_Name,
                $facility->facility_type ? ucfirst($facility->facility_type) : '',
                (float) $facility->Price,
                $facility->Capacity,
                $facility->Location ?? '',
                $facility->Office ?? '',
                $facility->Status,
            ])->all(),
            [12, 28, 18, 16, 12, 30, 26, 18],
        );
    }

    public function requestsXlsx(Collection $requests): string
    {
        return $this->xlsx(
            'Facility Requests',
            ['Request ID', 'Requester', 'Email', 'Facility', 'Date', 'Start Time', 'End Time', 'Attendees', 'Status', 'Purpose'],
            $requests->map(fn ($request) => [
                $request->RID,
                $request->user?->name ?? '',
                $request->user?->email ?? '',
                $request->facility?->Facility_Name ?? '',
                $request->Proposed_Date?->format('Y-m-d') ?? '',
                $request->Proposed_Start_Time?->format('H:i') ?? '',
                $request->Proposed_End_Time?->format('H:i') ?? '',
                $request->Capacity,
                $request->Review_Requested_At && $request->Status === 'Pending' ? 'Needs Revision' : $request->Status,
                $request->Purpose,
            ])->all(),
            [12, 24, 30, 28, 14, 13, 13, 12, 18, 42],
        );
    }

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

        $temporaryPath = tempnam(sys_get_temp_dir(), 'unispace-xlsx-');
        $zip = new \ZipArchive();
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
