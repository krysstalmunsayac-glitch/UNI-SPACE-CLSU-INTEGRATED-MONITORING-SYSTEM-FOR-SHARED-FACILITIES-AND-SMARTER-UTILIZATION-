<?php

namespace App\Actions\Schedules;

use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Services\ScheduleManagementService;

class UpdateSchedule
{
    public function __construct(private readonly ScheduleManagementService $schedules) {}

    public function handle(Schedule $schedule, FacilityRequest $request, array $data): bool
    {
        return $this->schedules->update($schedule, $request, $data);
    }
}
