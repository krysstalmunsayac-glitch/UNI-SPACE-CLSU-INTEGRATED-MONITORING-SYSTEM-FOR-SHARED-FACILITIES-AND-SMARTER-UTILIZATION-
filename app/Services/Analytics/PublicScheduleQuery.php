<?php

namespace App\Services\Analytics;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PublicScheduleQuery
{
    public function publicScheduleEvents(): array
    {
        if (! Schema::hasTable('schedules')) {
            return [];
        }

        return Schedule::query()
            ->with(['request.facility', 'request.event'])
            ->where('Status', 'Booked')
            ->get()
            ->map(function (Schedule $schedule): array {
                $facilityName = $schedule->request?->facility?->Facility_Name
                    ?? "Request #{$schedule->Request_ID}";
                $eventName = $schedule->request?->event?->Event_Title
                    ?? 'Reserved facility';
                $date = Carbon::parse($schedule->Date)->toDateString();
                $start = Carbon::parse($date.' '.Carbon::parse($schedule->Start_Time)->format('H:i:s'));
                $end = Carbon::parse($date.' '.Carbon::parse($schedule->End_Time)->format('H:i:s'));
                $status = $schedule->request?->Status === 'Ended' || $end->isPast()
                    ? 'Ended'
                    : ($start->isPast() ? 'Ongoing' : 'Approved');
                $colors = match ($status) {
                    'Ongoing' => ['background' => '#007a2f', 'border' => '#006b2b'],
                    'Ended' => ['background' => '#737373', 'border' => '#525252'],
                    default => ['background' => '#007a2f', 'border' => '#006b2b'],
                };

                return [
                    'id' => $schedule->SID,
                    'title' => $eventName,
                    'event' => $eventName,
                    'facility' => $facilityName,
                    'status' => $status,
                    'start' => $start->format('Y-m-d\TH:i:s'),
                    'end' => $end->format('Y-m-d\TH:i:s'),
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                ];
            })
            ->values()
            ->all();
    }
}
