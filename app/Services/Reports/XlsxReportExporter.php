<?php

namespace App\Services\Reports;

use App\Services\AdminReportExporter;
use Illuminate\Support\Collection;

class XlsxReportExporter
{
    public function __construct(private readonly AdminReportExporter $reports) {}

    public function facilities(Collection $records): string { return $this->reports->facilitiesXlsx($records); }
    public function requests(Collection $records): string { return $this->reports->requestsXlsx($records); }
    public function users(Collection $records): string { return $this->reports->usersXlsx($records); }
    public function amenities(Collection $records): string { return $this->reports->amenitiesXlsx($records); }
    public function audits(Collection $records): string { return $this->reports->auditsXlsx($records); }
}
