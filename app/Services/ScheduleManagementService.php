<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Notifications\ScheduleUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class ScheduleManagementService
{
    public function update(Schedule $schedule, FacilityRequest $request, array $data): bool
    {
        $result = DB::transaction(function () use ($schedule, $request, $data): array {
            $lockedSchedule = Schedule::query()->lockForUpdate()->findOrFail($schedule->SID);
            $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($request->RID);

            $this->validateAssignment($lockedSchedule, $lockedRequest, $data);
            $this->validateDuration($data['Start_Time'], $data['End_Time']);
            $this->validateRequestDate($lockedSchedule, $data['Date']);
            $this->validateAvailability($lockedSchedule, $lockedRequest, $data);

            $oldSchedule = [
                'Date' => Carbon::parse($lockedSchedule->Date)->toDateString(),
                'Start_Time' => substr((string) $lockedSchedule->getRawOriginal('Start_Time'), 0, 5),
                'End_Time' => substr((string) $lockedSchedule->getRawOriginal('End_Time'), 0, 5),
            ];
            $newSchedule = [
                'Date' => Carbon::parse($data['Date'])->toDateString(),
                'Start_Time' => substr((string) $data['Start_Time'], 0, 5),
                'End_Time' => substr((string) $data['End_Time'], 0, 5),
            ];
            $changed = $oldSchedule !== $newSchedule;

            $lockedSchedule->update($data);

            if ($changed) {
                $this->synchronizeRequestSchedule($lockedRequest);

                AuditLog::recordRequest(
                    $lockedRequest,
                    'schedule_updated',
                    "Changed the schedule for request #{$lockedRequest->RID}.",
                    $oldSchedule,
                    $newSchedule,
                );
            }

            return compact('changed', 'oldSchedule', 'newSchedule');
        });

        if (! $result['changed']) {
            return false;
        }

        $request = FacilityRequest::query()
            ->with(['user', 'facility'])
            ->findOrFail($request->RID);
        $notification = new ScheduleUpdated($request, $result['oldSchedule'], $result['newSchedule']);

        try {
            if ($request->user) {
                Notification::send($request->user, $notification);
            } elseif ($request->Is_Guest_Booking && filled($request->Guest_Email)) {
                Notification::route('mail', $request->Guest_Email)->notify($notification);
            }
        } catch (Throwable $exception) {
            Log::warning('Schedule was updated, but its notification could not be delivered.', [
                'request_id' => $request->RID,
                'schedule_id' => $schedule->SID,
                'exception' => $exception,
            ]);
        }

        return true;
    }

    private function validateAssignment(Schedule $schedule, FacilityRequest $request, array $data): void
    {
        if ((int) $data['Request_ID'] !== (int) $schedule->Request_ID || (int) $request->RID !== (int) $schedule->Request_ID) {
            throw ValidationException::withMessages([
                'Request_ID' => 'The request assigned to a schedule cannot be changed.',
            ]);
        }
    }

    private function validateRequestDate(Schedule $schedule, string $date): void
    {
        if (Schedule::query()
            ->where('Request_ID', $schedule->Request_ID)
            ->whereDate('Date', $date)
            ->where('SID', '!=', $schedule->SID)
            ->exists()) {
            throw ValidationException::withMessages([
                'Date' => 'This request already has a schedule on that date.',
            ]);
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

    private function synchronizeRequestSchedule(FacilityRequest $request): void
    {
        $dailySchedules = $request->schedules()
            ->orderBy('Date')
            ->orderBy('Start_Time')
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'date' => Carbon::parse($schedule->Date)->toDateString(),
                'start' => substr((string) $schedule->getRawOriginal('Start_Time'), 0, 5),
                'end' => substr((string) $schedule->getRawOriginal('End_Time'), 0, 5),
            ])
            ->values();

        $firstSchedule = $dailySchedules->first();
        $lastSchedule = $dailySchedules->last();

        if (! $firstSchedule || ! $lastSchedule) {
            return;
        }

        $request->updateQuietly([
            'Proposed_Date' => $firstSchedule['date'],
            'Proposed_End_Date' => $lastSchedule['date'],
            'Proposed_Start_Time' => $firstSchedule['start'],
            'Proposed_End_Time' => $lastSchedule['end'],
            'Daily_Schedules' => $dailySchedules->all(),
        ]);
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
