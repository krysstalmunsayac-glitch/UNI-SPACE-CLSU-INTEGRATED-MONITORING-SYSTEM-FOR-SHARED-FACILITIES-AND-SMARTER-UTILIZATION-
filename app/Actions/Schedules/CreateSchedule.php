<?php

namespace App\Actions\Schedules;

use App\Models\Schedule;

class CreateSchedule
{
    public function handle(array $data): Schedule
    {
        return Schedule::query()->create($data);
    }
}
