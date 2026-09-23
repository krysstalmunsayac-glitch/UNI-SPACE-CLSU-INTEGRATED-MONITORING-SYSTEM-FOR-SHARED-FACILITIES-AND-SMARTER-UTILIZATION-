<?php

namespace App\Services;

use App\Models\Amenity;
use App\Models\FacilityRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class BookingRequestValidator
{
    /** @param array<int, int> $amenityQuantities */
    public function validateAmenityAvailability(
        array $amenityQuantities,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): void {
        $amenities = Amenity::query()
            ->whereIn('AID', array_keys($amenityQuantities))
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->get();

        foreach ($amenities as $amenity) {
            if ($amenity->isPermanent()) {
                continue;
            }

            $requested = $amenityQuantities[(int) $amenity->AID];

            if ($amenity->Status === 'Available' && $requested <= $amenity->inventory_quantity) {
                continue;
            }

            if ($amenity->Status !== 'Available') {
                throw ValidationException::withMessages([
                    "Amenity_ID.{$amenity->AID}" => "{$amenity->name} is currently unavailable.",
                ]);
            }

            throw ValidationException::withMessages([
                "Amenity_Quantity.{$amenity->AID}" => "Only {$amenity->quantityLabel()} may be requested for {$amenity->name}; {$requested} requested.",
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $amenityIds
     * @param  array<int|string, int|string|null>  $submittedQuantities
     * @return array<int, int>
     */
    public function validatedAmenityQuantities(array $amenityIds, array $submittedQuantities): array
    {
        $ids = collect($amenityIds)->map(fn ($id) => (int) $id)->unique()->values();
        $amenities = Amenity::query()->whereIn('AID', $ids)->get()->keyBy('AID');
        $quantities = [];

        foreach ($ids as $id) {
            $amenity = $amenities->get($id);

            if ($amenity?->isPermanent()) {
                $quantities[$id] = 1;

                continue;
            }

            $quantity = $submittedQuantities[$id] ?? null;

            if (! is_numeric($quantity) || (int) $quantity < 1) {
                throw ValidationException::withMessages([
                    "Amenity_Quantity.{$id}" => 'Enter the number of units needed for each selected amenity.',
                ]);
            }

            $quantities[$id] = (int) $quantity;
        }

        return $quantities;
    }

    /** @return array<int, array{date:string,start:string,end:string}> */
    public function validatedDailySchedules(array $validated): array
    {
        $expectedDates = collect(CarbonPeriod::create($validated['Proposed_Date'], $validated['Proposed_End_Date']))
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values();

        if ($expectedDates->count() > 31) {
            throw ValidationException::withMessages([
                'Proposed_End_Date' => 'A single request may cover no more than 31 consecutive days.',
            ]);
        }

        $submitted = collect($validated['Daily_Schedules'])
            ->map(fn (array $schedule) => [
                'date' => $schedule['date'],
                'start' => $schedule['start'],
                'end' => $schedule['end'],
            ])
            ->keyBy('date');

        if ($submitted->count() !== $expectedDates->count() || $expectedDates->contains(fn ($date) => ! $submitted->has($date))) {
            throw ValidationException::withMessages([
                'Daily_Schedules' => 'Please provide one time schedule for every selected booking day.',
            ]);
        }

        $schedules = $expectedDates->map(fn (string $date) => $submitted->get($date))->all();

        foreach ($schedules as $index => $schedule) {
            if ($schedule['end'] <= $schedule['start']) {
                throw ValidationException::withMessages([
                    "Daily_Schedules.{$index}.end" => 'The end time must be later than the start time.',
                ]);
            }

            $this->validateBookingDuration($schedule['start'], $schedule['end'], "Daily_Schedules.{$index}.end");
        }

        return $schedules;
    }

    public function validateBookingDuration(string $startTime, string $endTime, string $errorKey = 'Proposed_End_Time'): void
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        if ($end->greaterThan($start) && $start->diffInMinutes($end) >= 60) {
            return;
        }

        throw ValidationException::withMessages([$errorKey => 'A booking must be at least 1 hour.']);
    }

    public function validateRequestDateRange(string $startDate, string $endDate): void
    {
        if (Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) < FacilityAvailabilityService::MAX_DAYS) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_End_Date' => 'A reservation may cover no more than 31 consecutive days.',
        ]);
    }

    public function validateDailyRequestLimit(
        int $userId,
        string $startDate,
        string $endDate,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): void {
        if (! FacilityRequest::userReachedRequestLimitOnDate($userId, $startDate, $endDate, $ignoreRequestId, $lockForUpdate)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Date' => 'You may submit up to '.FacilityRequest::MAX_REQUESTS_PER_EVENT_DATE.' active reservation requests per event date. Cancel an existing request or choose another date.',
        ]);
    }

    public function validateFacilityAvailability(
        int $facilityId,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): void {
        if (! FacilityRequest::hasActiveFacilityConflict($facilityId, $startDate, $endDate, $startTime, $endTime, $ignoreRequestId, $lockForUpdate)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Start_Time' => 'This facility already has a request during the selected time. Please choose another time.',
        ]);
    }
}
