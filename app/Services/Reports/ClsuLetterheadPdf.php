<?php

namespace App\Services\Reports;

class ClsuLetterheadPdf extends \FPDF
{
    public string $letterheadHeader = '';

    public string $letterheadFooter = '';

    public function Header(): void
    {
        if ($this->letterheadHeader !== '' && is_file($this->letterheadHeader)) {
            $width = min($this->GetPageWidth() > 220 ? 250 : 190, $this->GetPageWidth() - 24);
            $this->Image($this->letterheadHeader, ($this->GetPageWidth() - $width) / 2, 5, $width);
        }

        $this->SetY(52);
    }

    public function Footer(): void
    {
        if ($this->letterheadFooter !== '' && is_file($this->letterheadFooter)) {
            $width = min($this->GetPageWidth() > 220 ? 250 : 190, $this->GetPageWidth() - 24);
            $this->Image($this->letterheadFooter, ($this->GetPageWidth() - $width) / 2, $this->GetPageHeight() - 39, $width);
        }

        $this->SetY(-43);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 4, 'SIEL SPACE  |  Official Administrative Report  |  Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
}
