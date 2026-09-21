<?php

namespace App\Services\Analytics;

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\Feedback;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalAnalyticsQuery
{
    public function operationalAnalytics(
        Builder $requestScope,
        Collection $facilities,
        Carbon $dateFrom,
        Carbon $dateTo,
    ): array {
        $approvedRequestIds = (clone $requestScope)
            ->whereIn('Status', ['Approved', 'Ended'])
            ->pluck('RID');
        $facilityLookup = $facilities->keyBy('FID');
        $dayCount = max(1, $dateFrom->copy()->startOfDay()->diffInDays($dateTo->copy()->startOfDay()) + 1);
        $availableHoursPerFacility = $dayCount * 10;

        $bookedSchedules = Schedule::query()
            ->where('Status', 'Booked')
            ->whereIn('Request_ID', $approvedRequestIds)
            ->whereBetween('Date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get(['Request_ID', 'Date', 'Start_Time', 'End_Time']);
        $scheduleRequests = FacilityRequest::withTrashed()
            ->whereIn('RID', $bookedSchedules->pluck('Request_ID')->unique())
            ->get(['RID', 'Facility_ID'])
            ->keyBy('RID');

        $bookedHoursByFacility = $bookedSchedules
            ->groupBy(fn (Schedule $schedule) => $scheduleRequests->get($schedule->Request_ID)?->Facility_ID)
            ->map(fn ($schedules) => round($schedules->sum(
                fn (Schedule $schedule) => max(0, $schedule->Start_Time->diffInMinutes($schedule->End_Time) / 60)
            ), 1));

        $facilityUtilizationRates = $facilities->map(function (Facility $facility) use ($bookedHoursByFacility, $availableHoursPerFacility): array {
            $bookedHours = (float) ($bookedHoursByFacility[$facility->FID] ?? 0);

            return [
                'facility' => $facility->Facility_Name,
                'bookedHours' => $bookedHours,
                'availableHours' => $availableHoursPerFacility,
                'rate' => $availableHoursPerFacility > 0 ? round(min(100, $bookedHours / $availableHoursPerFacility * 100), 1) : 0,
            ];
        })->sortByDesc('rate')->values()->all();

        $totalBookedHours = (float) $bookedHoursByFacility->sum();
        $totalAvailableHours = $availableHoursPerFacility * max(1, $facilities->count());
        $overallFacilityUtilizationRate = $totalAvailableHours > 0
            ? round(min(100, $totalBookedHours / $totalAvailableHours * 100), 1)
            : 0;

        $heatmap = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
            ->mapWithKeys(fn (string $day) => [$day => array_fill(8, 10, 0)])
            ->all();
        foreach ($bookedSchedules as $schedule) {
            $day = $schedule->Date->format('l');
            $startHour = max(8, (int) $schedule->Start_Time->format('G'));
            $endHour = min(18, (int) ceil((float) $schedule->End_Time->format('G') + ((int) $schedule->End_Time->format('i') / 60)));
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $heatmap[$day][$hour]++;
            }
        }

        $months = collect();
        for ($month = $dateFrom->copy()->startOfMonth(); $month->lte($dateTo); $month->addMonth()) {
            $months->push($month->copy());
        }
        $months = $months->take(-12)->values();
        $outcomeRecords = (clone $requestScope)
            ->whereBetween('Created_at', [$months->first()?->copy()->startOfMonth() ?? $dateFrom, $dateTo])
            ->get(['Status', 'Created_at']);
        $requestOutcomesTrend = [
            'labels' => $months->map->format('M Y')->all(),
            'series' => collect(['Pending', 'Approved', 'Rejected', 'Cancelled'])->mapWithKeys(
                fn (string $status) => [$status => $months->map(fn (Carbon $month) => $outcomeRecords
                    ->filter(fn (FacilityRequest $request) => $request->Status === $status
                        && $request->Created_at?->isSameMonth($month))
                    ->count())->all()]
            )->all(),
        ];
        $typeTrendUsesDailyBuckets = $dateFrom->copy()->startOfDay()->diffInDays($dateTo->copy()->startOfDay()) <= 31;
        $typeTrendBuckets = collect();
        if ($typeTrendUsesDailyBuckets) {
            for ($day = $dateFrom->copy()->startOfDay(); $day->lte($dateTo); $day->addDay()) {
                $typeTrendBuckets->push($day->copy());
            }
        } else {
            $typeTrendBuckets = $months;
        }
        $typeTrendStart = $typeTrendUsesDailyBuckets
            ? $dateFrom->copy()->startOfDay()
            : ($months->first()?->copy()->startOfMonth() ?? $dateFrom);
        $facilityTypeRecords = (clone $requestScope)
            ->with('facility:FID,facility_type')
            ->whereBetween('Created_at', [$typeTrendStart, $dateTo])
            ->get(['RID', 'Facility_ID', 'Created_at']);
        $facilityTypes = $facilityTypeRecords
            ->map(fn (FacilityRequest $request) => filled($request->facility?->facility_type)
                ? ucfirst($request->facility->facility_type)
                : 'Other')
            ->unique()
            ->sort()
            ->values();
        $facilityTypeUsageTrend = [
            'labels' => $typeTrendBuckets
                ->map(fn (Carbon $bucket) => $bucket->format($typeTrendUsesDailyBuckets ? 'M d' : 'M Y'))
                ->all(),
            'series' => $facilityTypes->mapWithKeys(fn (string $type) => [
                $type => $typeTrendBuckets->map(fn (Carbon $bucket) => $facilityTypeRecords
                    ->filter(fn (FacilityRequest $request) => (
                        filled($request->facility?->facility_type)
                            ? ucfirst($request->facility->facility_type)
                            : 'Other'
                    ) === $type && ($typeTrendUsesDailyBuckets
                        ? $request->Created_at?->isSameDay($bucket)
                        : $request->Created_at?->isSameMonth($bucket)))
                    ->count())->all(),
            ])->all(),
        ];

        $rangeRequests = (clone $requestScope)
            ->with('facility:FID,Facility_Name,Capacity')
            ->whereBetween('Created_at', [$dateFrom, $dateTo])
            ->get(['RID', 'Facility_ID', 'Status', 'Capacity']);
        $capacityUtilization = $rangeRequests
            ->filter(fn (FacilityRequest $request) => $request->facility?->Capacity > 0 && $request->Capacity !== null)
            ->groupBy('Facility_ID')
            ->map(function ($requests, $facilityId) use ($facilityLookup): array {
                $facility = $facilityLookup->get($facilityId);
                $rate = $requests->average(fn (FacilityRequest $request) => min(100, $request->Capacity / $request->facility->Capacity * 100));

                return ['facility' => $facility?->Facility_Name ?? 'Unknown facility', 'rate' => round($rate, 1)];
            })->sortByDesc('rate')->values()->all();
        $facilityRequestGroups = $rangeRequests
            ->whereNotNull('Facility_ID')
            ->filter(fn (FacilityRequest $request) => $facilityLookup->has($request->Facility_ID))
            ->groupBy('Facility_ID');
        $cancellationRates = $facilityRequestGroups->map(function ($requests, $facilityId) use ($facilityLookup): array {
            $total = $requests->count();
            $cancelled = $requests->where('Status', 'Cancelled')->count();

            return [
                'facility' => $facilityLookup->get($facilityId)->Facility_Name,
                'rate' => $total ? round($cancelled / $total * 100, 1) : 0,
                'cancelled' => $cancelled,
                'total' => $total,
            ];
        })->filter(fn (array $row) => $row['cancelled'] > 0)->sortByDesc('rate')->values()->all();
        $facilityDecisionRates = $facilityRequestGroups->map(function ($requests, $facilityId) use ($facilityLookup): array {
            $approved = $requests->whereIn('Status', ['Approved', 'Ended'])->count();
            $rejected = $requests->where('Status', 'Rejected')->count();
            $decided = $approved + $rejected;

            return [
                'facility' => $facilityLookup->get($facilityId)->Facility_Name,
                'approved' => $decided ? round($approved / $decided * 100, 1) : 0,
                'rejected' => $decided ? round($rejected / $decided * 100, 1) : 0,
                'decided' => $decided,
            ];
        })->filter(fn (array $row) => $row['decided'] > 0)->sortByDesc('approved')->values()->all();
        $facilityRatings = Feedback::query()
            ->whereIn('Facility_ID', $facilities->pluck('FID'))
            ->whereNotNull('Rating')
            ->whereBetween('Created_at', [$dateFrom, $dateTo])
            ->selectRaw('Facility_ID, AVG(Rating) as average_rating, COUNT(*) as rating_count')
            ->groupBy('Facility_ID')
            ->get()
            ->map(fn (Feedback $feedback): array => [
                'facility' => $facilityLookup->get($feedback->Facility_ID)?->Facility_Name ?? 'Unknown facility',
                'rating' => round((float) $feedback->average_rating, 1),
                'count' => (int) $feedback->rating_count,
            ])
            ->sortByDesc('rating')
            ->values()
            ->all();

        return [
            'facilityUtilizationRates' => $facilityUtilizationRates,
            'overallFacilityUtilizationRate' => $overallFacilityUtilizationRate,
            'bookingDemandHeatmap' => $heatmap,
            'requestOutcomesTrend' => $requestOutcomesTrend,
            'facilityTypeUsageTrend' => $facilityTypeUsageTrend,
            'capacityUtilization' => $capacityUtilization,
            'cancellationRates' => $cancellationRates,
            'facilityDecisionRates' => $facilityDecisionRates,
            'facilityRatings' => $facilityRatings,
            'availabilityBaseline' => '8:00 AM–6:00 PM daily',
        ];
    }

}
