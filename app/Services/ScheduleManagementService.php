<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Notifications\ScheduleUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ScheduleManagementService
{
    public function update(Schedule $schedule, FacilityRequest $request, array $data): bool
    {
        $this->validateAssignment($schedule, $request, $data);
        $this->validateDuration($data['Start_Time'], $data['End_Time']);
        $this->validateAvailability($schedule, $request, $data);

        $oldSchedule = [
            'Date' => Carbon::parse($schedule->Date)->toDateString(),
            'Start_Time' => substr((string) $schedule->getRawOriginal('Start_Time'), 0, 5),
            'End_Time' => substr((string) $schedule->getRawOriginal('End_Time'), 0, 5),
        ];
        $newSchedule = collect($data)->only(['Date', 'Start_Time', 'End_Time'])->all();
        $changed = $oldSchedule !== $newSchedule;
        $schedule->update($data);

        if ($changed) {
            $request->loadMissing(['user', 'facility']);
            AuditLog::recordRequest(
                $request,
                'schedule_updated',
                "Changed the schedule for request #{$request->RID}.",
                $oldSchedule,
                $newSchedule,
            );
            $notification = new ScheduleUpdated($request, $oldSchedule, $newSchedule);
            if ($request->user) {
                Notification::send($request->user, $notification);
            } elseif ($request->Is_Guest_Booking && filled($request->Guest_Email)) {
                Notification::route('mail', $request->Guest_Email)->notify($notification);
            }
        }

        return $changed;
    }

    private function validateAssignment(Schedule $schedule, FacilityRequest $request, array $data): void
    {
        if ((int) $data['Request_ID'] !== (int) $schedule->Request_ID || (int) $request->RID !== (int) $schedule->Request_ID) {
            throw ValidationException::withMessages([
                'Request_ID' => 'The request assigned to a schedule cannot be changed.',
            ]);
        }

        if (Schedule::where('Request_ID', $data['Request_ID'])->where('SID', '!=', $schedule->SID)->exists()) {
            throw ValidationException::withMessages(['Request_ID' => 'This request already has a schedule.']);
        }
    }

    private function validateDuration(string $start, string $end): void
    {
        $startMinutes = $this->minutes($start);
        $endMinutes = $this->minutes($end);
        if (($endMinutes - $startMinutes) < FacilityAvailabilityService::MINIMUM_MINUTES) {
            throw ValidationException::withMessages(['End_Time' => 'A booking must be at least 1 hour.']);
        }
        if ($startMinutes < $this->minutes(FacilityAvailabilityService::OPENS_AT)
            || $endMinutes > $this->minutes(FacilityAvailabilityService::CLOSES_AT)
            || $startMinutes % FacilityAvailabilityService::SLOT_MINUTES !== 0
            || $endMinutes % FacilityAvailabilityService::SLOT_MINUTES !== 0) {
            throw ValidationException::withMessages([
                'Start_Time' => 'Choose a 30-minute time slot between 5:00 AM and 12:00 AM.',
            ]);
        }
    }

    private function validateAvailability(Schedule $schedule, FacilityRequest $request, array $data): void
    {
        $facilityId = $request->facility?->FID;
        if ($facilityId && Schedule::where('Date', $data['Date'])
            ->whereHas('request.facility', fn ($query) => $query->where('FID', $facilityId))
            ->where(fn ($query) => $query->where('Start_Time', '<', $data['End_Time'])->where('End_Time', '>', $data['Start_Time']))
            ->where('SID', '!=', $schedule->SID)->exists()) {
            throw ValidationException::withMessages(['Date' => 'This facility is already booked during that time.']);
        }
    }

    private function minutes(string $time): int
    {
        if ($time === '24:00') {
            return 1440;
        }
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }
}
