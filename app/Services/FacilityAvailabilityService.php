<?php

namespace App\Services;

use App\Models\Facilities;
use App\Models\FacilityBlackout;
use App\Models\Requests;
use App\Notifications\FacilityUnavailable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class FacilityAvailabilityService
{
    public const OPENS_AT = '05:00';

    public const CLOSES_AT = '24:00';

    public const SLOT_MINUTES = 30;

    public const MINIMUM_MINUTES = 60;

    public const BUFFER_MINUTES = 30;

    public const MAX_DAYS = 31;

    /**
     * Toggle a facility's availability and cancel any requests that can no
     * longer be fulfilled when it is deactivated.
     *
     * @return int Number of active requests cancelled
     */
    public function toggle(Facilities $facility): int
    {
        if ($facility->Status === 'Unavailable') {
            $facility->update(['Status' => 'Available']);

            return 0;
        }

        $cancelledRequests = DB::transaction(function () use ($facility) {
            $facility->update(['Status' => 'Unavailable']);

            $requests = Requests::query()
                ->with([
                    'user:id,name,email',
                    'facility:FID,Facility_Name',
                ])
                ->where('Facility_ID', $facility->FID)
                ->whereIn('Status', ['Pending', 'Approved'])
                ->lockForUpdate()
                ->get();

            foreach ($requests as $request) {
                $request->update([
                    'Status' => 'Cancelled',
                    'Cancellation_Reason' => 'The facility has been marked unavailable by the facility administrator.',
                ]);

                $request->schedules()->delete();
            }

            return $requests;
        });

        foreach ($cancelledRequests as $request) {
            if ($request->user) {
                Notification::send($request->user, new FacilityUnavailable($request));
            }
        }

        return $cancelledRequests->count();
    }

    public function slots(): array
    {
        $slots = [];
        $lastStart = $this->timeToMinutes(self::CLOSES_AT) - self::MINIMUM_MINUTES;

        for ($minute = $this->timeToMinutes(self::OPENS_AT); $minute <= $lastStart; $minute += self::SLOT_MINUTES) {
            $slots[] = $this->formatMinutes($minute);
        }

        return $slots;
    }

    public function endSlots(): array
    {
        return collect($this->slots())
            ->map(fn (string $time): string => $this->formatMinutes($this->timeToMinutes($time) + self::MINIMUM_MINUTES))
            ->values()
            ->all();
    }

    public function validateSchedules(int $facilityId, string $firstDate, string $lastDate, array $submitted, ?int $ignoreRequestId = null, bool $lock = false): array
    {
        $expected = collect(CarbonPeriod::create($firstDate, $lastDate))->map->format('Y-m-d')->values();
        if ($expected->count() > self::MAX_DAYS) {
            throw ValidationException::withMessages(['Proposed_End_Date' => 'A reservation may cover no more than 31 consecutive days.']);
        }

        $items = collect($submitted);
        if ($items->pluck('date')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['Daily_Schedules' => 'Each booking day must appear exactly once.']);
        }
        $byDate = $items->keyBy('date');
        if ($items->count() !== $expected->count() || $expected->contains(fn ($date) => ! $byDate->has($date))) {
            throw ValidationException::withMessages(['Daily_Schedules' => 'Provide one consecutive schedule for every selected booking day.']);
        }

        $schedules = $expected->map(fn ($date) => ['date' => $date, 'start' => $byDate[$date]['start'], 'end' => $byDate[$date]['end']])->all();
        foreach ($schedules as $index => $schedule) {
            $start = $this->timeToMinutes($schedule['start']);
            $end = $this->timeToMinutes($schedule['end']);
            if ($start % self::SLOT_MINUTES !== 0 || $end % self::SLOT_MINUTES !== 0) {
                throw ValidationException::withMessages(["Daily_Schedules.{$index}.start" => 'Choose a time in 30-minute intervals.']);
            }
            if ($start < $this->timeToMinutes(self::OPENS_AT) || $end > $this->timeToMinutes(self::CLOSES_AT)) {
                throw ValidationException::withMessages(["Daily_Schedules.{$index}.start" => 'Bookings must be between 5:00 AM and 12:00 AM.']);
            }
            if ($end <= $start || ($end - $start) < self::MINIMUM_MINUTES) {
                throw ValidationException::withMessages(["Daily_Schedules.{$index}.end" => 'A booking must be at least 1 hour.']);
            }
            if ($this->blackout($facilityId, $schedule['date'])) {
                throw ValidationException::withMessages(["Daily_Schedules.{$index}.date" => "The facility is closed or under maintenance on {$schedule['date']}."]);
            }
        }

        $conflicts = $this->conflicts($facilityId, $schedules, $ignoreRequestId, $lock, ['Approved']);
        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages(['Daily_Schedules' => 'The selected time on '.$conflicts->first()['date'].' is already booked, including its 30-minute preparation and cleanup buffer.']);
        }

        return $schedules;
    }

    public function conflicts(int $facilityId, array $schedules, ?int $ignoreRequestId = null, bool $lock = false, array $statuses = ['Pending', 'Approved']): Collection
    {
        if ($schedules === []) {
            return collect();
        }
        $dates = array_column($schedules, 'date');
        $requests = Requests::query()->where('Facility_ID', $facilityId)->whereIn('Status', $statuses)
            ->whereDate('Proposed_Date', '<=', max($dates))->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '>=', min($dates))
            ->when($ignoreRequestId, fn ($q) => $q->where('RID', '!=', $ignoreRequestId))
            ->when($lock, fn ($q) => $q->lockForUpdate())->get();

        return collect($schedules)->flatMap(function ($candidate) use ($requests) {
            return $requests->map(function (Requests $request) use ($candidate) {
                $existing = $request->scheduleForDate($candidate['date']);
                if (! $existing) {
                    return null;
                }
                $blockedStartMinutes = max(0, $this->timeToMinutes($existing['start']) - self::BUFFER_MINUTES);
                $blockedEndMinutes = min(24 * 60, $this->timeToMinutes($existing['end']) + self::BUFFER_MINUTES);
                $candidateStart = $this->timeToMinutes($candidate['start']);
                $candidateEnd = $this->timeToMinutes($candidate['end']);
                if ($candidateStart >= $blockedEndMinutes || $candidateEnd <= $blockedStartMinutes) {
                    return null;
                }

                $blockedStart = $this->formatMinutes($blockedStartMinutes);
                $blockedEnd = $this->formatMinutes($blockedEndMinutes);

                return ['request_id' => $request->RID, 'date' => $candidate['date'], 'status' => strtolower($request->Status), 'start' => $existing['start'], 'end' => $existing['end'], 'blocked_start' => $blockedStart, 'blocked_end' => $blockedEnd];
            })->filter();
        })->values();
    }

    public function availability(int $facilityId, string $from, string $to): array
    {
        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = $day->format('Y-m-d');
            $blackout = $this->blackout($facilityId, $date);
            $ranges = $this->conflicts($facilityId, [['date' => $date, 'start' => self::OPENS_AT, 'end' => self::CLOSES_AT]])
                ->map(fn (array $range) => collect($range)->except('request_id')->all())->all();
            $days[$date] = ['closed' => (bool) $blackout, 'reason' => $blackout?->reason, 'ranges' => $ranges];
        }

        return ['config' => ['opens_at' => self::OPENS_AT, 'closes_at' => self::CLOSES_AT, 'slot_minutes' => self::SLOT_MINUTES, 'minimum_minutes' => self::MINIMUM_MINUTES, 'buffer_minutes' => self::BUFFER_MINUTES], 'days' => $days];
    }

    private function blackout(int $facilityId, string $date): ?FacilityBlackout
    {
        return FacilityBlackout::query()->where('facility_id', $facilityId)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->first();
    }

    private function timeToMinutes(string $time): int
    {
        if (! preg_match('/^(?<hour>[01]\d|2[0-3]):(?<minute>[0-5]\d)$|^24:00$/', $time, $matches)) {
            throw ValidationException::withMessages(['Daily_Schedules' => 'Choose a valid booking time.']);
        }

        if ($time === '24:00') {
            return 24 * 60;
        }

        return ((int) $matches['hour'] * 60) + (int) $matches['minute'];
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes >= 24 * 60) {
            return '24:00';
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
