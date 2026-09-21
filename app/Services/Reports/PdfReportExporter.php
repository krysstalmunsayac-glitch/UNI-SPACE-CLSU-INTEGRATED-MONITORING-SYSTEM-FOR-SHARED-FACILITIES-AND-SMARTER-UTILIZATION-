<?php

namespace App\Services\Reports;

use App\Services\AdminReportExporter;
use Illuminate\Support\Collection;

class PdfReportExporter
{
    public function __construct(private readonly AdminReportExporter $reports) {}

    public function analytics(array $data, string $scopeLabel, string $dateLabel): string { return $this->reports->analyticsPdf($data, $scopeLabel, $dateLabel); }
    public function facilities(Collection $records, string $scopeLabel): string { return $this->reports->facilitiesPdf($records, $scopeLabel); }
    public function requests(Collection $records, string $scopeLabel): string { return $this->reports->requestsPdf($records, $scopeLabel); }
    public function users(Collection $records): string { return $this->reports->usersPdf($records); }
    public function amenities(Collection $records): string { return $this->reports->amenitiesPdf($records); }
    public function audits(Collection $records): string { return $this->reports->auditsPdf($records); }
}
