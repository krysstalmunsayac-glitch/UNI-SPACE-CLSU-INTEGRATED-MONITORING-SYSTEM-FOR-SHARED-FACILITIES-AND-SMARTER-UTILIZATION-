<?php

namespace App\Services;

use App\Models\FacilityBlackout;
use App\Models\Requests;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacilityAvailabilityService
{
    public const OPENS_AT = '07:00';
    public const CLOSES_AT = '20:00';
    public const SLOT_MINUTES = 30;
    public const MINIMUM_MINUTES = 60;
    public const BUFFER_MINUTES = 30;
    public const MAX_DAYS = 31;

    public function slots(): array
    {
        $slots = [];
        $cursor = Carbon::createFromFormat('H:i', self::OPENS_AT);
        $last = Carbon::createFromFormat('H:i', self::CLOSES_AT)->subMinutes(self::MINIMUM_MINUTES);
        while ($cursor->lessThanOrEqualTo($last)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes(self::SLOT_MINUTES);
        }
        return $slots;
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
            $start = Carbon::createFromFormat('H:i', $schedule['start']);
            $end = Carbon::createFromFormat('H:i', $schedule['end']);
            if ((int) $start->format('i') % self::SLOT_MINUTES !== 0 || (int) $end->format('i') % self::SLOT_MINUTES !== 0) {
                throw ValidationException::withMessages(["Daily_Schedules.{$index}.start" => 'Choose a time in 30-minute intervals.']);
            }
            if ($schedule['start'] < self::OPENS_AT || $schedule['end'] > self::CLOSES_AT) {
                throw ValidationException::withMessages(["Daily_Schedules.{$index}.start" => 'Bookings must be between 7:00 AM and 8:00 PM.']);
            }
            if ($end->lessThanOrEqualTo($start) || $start->diffInMinutes($end) < self::MINIMUM_MINUTES) {
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
        if ($schedules === []) return collect();
        $dates = array_column($schedules, 'date');
        $requests = Requests::query()->where('Facility_ID', $facilityId)->whereIn('Status', $statuses)
            ->whereDate('Proposed_Date', '<=', max($dates))->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '>=', min($dates))
            ->when($ignoreRequestId, fn ($q) => $q->where('RID', '!=', $ignoreRequestId))
            ->when($lock, fn ($q) => $q->lockForUpdate())->get();

        return collect($schedules)->flatMap(function ($candidate) use ($requests) {
            return $requests->map(function (Requests $request) use ($candidate) {
                $existing = $request->scheduleForDate($candidate['date']);
                if (! $existing) return null;
                $blockedStart = Carbon::createFromFormat('H:i', $existing['start'])->subMinutes(self::BUFFER_MINUTES)->format('H:i');
                $blockedEnd = Carbon::createFromFormat('H:i', $existing['end'])->addMinutes(self::BUFFER_MINUTES)->format('H:i');
                if ($candidate['start'] >= $blockedEnd || $candidate['end'] <= $blockedStart) return null;
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
}
