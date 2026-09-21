<?php

namespace App\Services\Reports;

use App\Services\AdminReportExporter;

/**
 * CSV-oriented tabular projections. The legacy exporter remains the single
 * mapping source while callers migrate to format-specific dependencies.
 */
class CsvReportExporter
{
    public function __construct(private readonly AdminReportExporter $reports) {}

    public function facilityHeaders(): array { return $this->reports->facilityHeaders(); }
    public function facilityRow($facility): array { return $this->reports->facilityRow($facility); }
    public function requestHeaders(): array { return $this->reports->requestHeaders(); }
    public function requestRow($request): array { return $this->reports->requestRow($request); }
    public function userHeaders(): array { return $this->reports->userHeaders(); }
    public function userRow($user): array { return $this->reports->userRow($user); }
    public function amenityHeaders(): array { return $this->reports->amenityHeaders(); }
    public function amenityRow($amenity): array { return $this->reports->amenityRow($amenity); }
    public function auditHeaders(): array { return $this->reports->auditHeaders(); }
    public function auditRow($log): array { return $this->reports->auditRow($log); }
}
