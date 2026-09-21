<?php

namespace App\Services\Analytics;

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class RequestAnalyticsQuery
{
    public function responseRateMetrics(Builder $baseQuery): array
    {
        $counts = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw(
                "SUM(CASE WHEN Status IN ('Approved', 'Rejected', 'Cancelled', 'Ended') THEN 1 ELSE 0 END) as responded_requests"
            )
            ->first();

        $total = (int) ($counts?->total_requests ?? 0);
        $responded = (int) ($counts?->responded_requests ?? 0);

        return [
            'responseRate' => $total > 0 ? round(($responded / $total) * 100, 1) : 0,
            'respondedRequestCount' => $responded,
            'responseRateTotalCount' => $total,
        ];
    }

    public function requestDashboardMetrics(Builder $baseQuery, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
        $requestStatusCounts = collect($statuses)
            ->mapWithKeys(fn (string $status) => [
                $status => (clone $baseQuery)->where('Status', $status)->count(),
            ])
            ->all();

        $dailyLabels = [];
        $dailyCapacityTotals = [];
        $dailyRequestTotals = [];

        $trendStart = $dateFrom?->copy()->startOfDay() ?? today()->subDays(6);
        $trendEnd = $dateTo?->copy()->startOfDay() ?? today();
        $trendDateColumn = $dateFrom ? 'Created_at' : 'Proposed_Date';

        for ($date = $trendStart->copy(); $date->lte($trendEnd); $date->addDay()) {
            $dailyLabels[] = $date->format('M d');

            $dailyCapacityTotals[] = (int) (clone $baseQuery)
                ->whereDate($trendDateColumn, $date)
                ->whereNotIn('Status', ['Rejected', 'Cancelled'])
                ->sum('Capacity');

            $dailyRequestTotals[] = (clone $baseQuery)
                ->whereDate($trendDateColumn, $date)
                ->count();
        }

        $capacityDates = collect($dailyLabels)->keys()->mapWithKeys(function (int $index) use ($trendStart): array {
            return [$trendStart->copy()->addDays($index)->toDateString() => $index];
        });
        $facilityCapacitySeries = (clone $baseQuery)
            ->with('facility')
            ->whereNotIn('Status', ['Rejected', 'Cancelled'])
            ->whereBetween($trendDateColumn, [$trendStart->copy()->startOfDay(), $trendEnd->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (FacilityRequest $request) => $request->facility?->Facility_Name ?? 'Unknown facility')
            ->map(function ($requests, string $facilityName) use ($capacityDates, $trendDateColumn, $dailyLabels): array {
                $totals = array_fill(0, count($dailyLabels), 0);

                foreach ($requests as $request) {
                    $dateValue = $request->{$trendDateColumn};
                    $dateKey = Carbon::parse($dateValue)->toDateString();
                    $index = $capacityDates->get($dateKey);

                    if ($index !== null) {
                        $totals[$index] += (int) ($request->Capacity ?? 0);
                    }
                }

                return ['facility' => $facilityName, 'totals' => $totals];
            })
            ->sortByDesc(fn (array $series) => array_sum($series['totals']))
            ->values()
            ->all();

        $facilityUsageSeries = (clone $baseQuery)
            ->with('facility')
            ->whereBetween($trendDateColumn, [$trendStart->copy()->startOfDay(), $trendEnd->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (FacilityRequest $request) => $request->facility?->Facility_Name ?? 'Unknown facility')
            ->map(function ($requests, string $facilityName) use ($capacityDates, $trendDateColumn, $dailyLabels): array {
                $totals = array_fill(0, count($dailyLabels), 0);

                foreach ($requests as $request) {
                    $dateKey = Carbon::parse($request->{$trendDateColumn})->toDateString();
                    $index = $capacityDates->get($dateKey);

                    if ($index !== null) {
                        $totals[$index]++;
                    }
                }

                return ['facility' => $facilityName, 'totals' => $totals];
            })
            ->sortByDesc(fn (array $series) => array_sum($series['totals']))
            ->values()
            ->all();

        $mostUsedFacilityRecord = (clone $baseQuery)
            ->whereNotNull('Facility_ID')
            ->selectRaw('Facility_ID, COUNT(*) as total')
            ->groupBy('Facility_ID')
            ->orderByDesc('total')
            ->first();

        $mostUsedFacility = null;

        if ($mostUsedFacilityRecord) {
            $facility = Facility::withTrashed()->find($mostUsedFacilityRecord->Facility_ID);

            $mostUsedFacility = [
                'name' => $facility?->Facility_Name ?? 'Unknown facility',
                'count' => (int) $mostUsedFacilityRecord->total,
                'capacity' => $facility?->Capacity,
                'status' => $facility?->Status,
            ];
        }

        $facilityUsageCounts = (clone $baseQuery)
            ->whereNotNull('Facility_ID')
            ->selectRaw('Facility_ID, COUNT(*) as total')
            ->groupBy('Facility_ID')
            ->pluck('total', 'Facility_ID');

        $facilityTypeUsage = Facility::withTrashed()
            ->whereIn('FID', $facilityUsageCounts->keys())
            ->get(['FID', 'facility_type'])
            ->groupBy(fn (Facility $facility) => $facility->facility_type ?: 'Other')
            ->map(fn ($facilities) => $facilities->sum(
                fn (Facility $facility) => (int) ($facilityUsageCounts[$facility->FID] ?? 0)
            ))
            ->sortDesc()
            ->all();

        $eventTypeUsage = (clone $baseQuery)
            ->with('event')
            ->whereNotNull('Event_ID')
            ->get()
            ->filter(fn (FacilityRequest $request) => filled($request->event?->Type_Event))
            ->groupBy(fn (FacilityRequest $request) => trim($request->event->Type_Event))
            ->map->count()
            ->sortDesc()
            ->all();

        $mostUsedEventType = collect($eventTypeUsage)
            ->map(fn (int $count, string $type) => ['type' => $type, 'count' => $count])
            ->first();

        $amenityUsage = (clone $baseQuery)
            ->with('amenities')
            ->get()
            ->flatMap(fn (FacilityRequest $request) => $request->amenities)
            ->groupBy(fn ($amenity) => $amenity->name)
            ->map->count()
            ->sortDesc()
            ->all();

        $mostUsedAmenity = collect($amenityUsage)
            ->map(fn (int $count, string $name) => ['name' => $name, 'count' => $count])
            ->first();

        $approvedOutcomeCount = (int) (($requestStatusCounts['Approved'] ?? 0)
            + (clone $baseQuery)->where('Status', 'Ended')->count());
        $rejectedOutcomeCount = (int) ($requestStatusCounts['Rejected'] ?? 0);
        $decidedRequestCount = $approvedOutcomeCount + $rejectedOutcomeCount;
        $approvalRate = $decidedRequestCount > 0
            ? round(($approvedOutcomeCount / $decidedRequestCount) * 100, 1)
            : null;

        $scopedRequests = (clone $baseQuery)
            ->with('facility')
            ->get([
                'RID', 'Facility_ID', 'Proposed_Date', 'Proposed_End_Date',
                'Proposed_Start_Time', 'Proposed_End_Time', 'Status', 'Created_at',
            ]);

        $decisionLogs = AuditLog::query()
            ->where('auditable_type', FacilityRequest::class)
            ->whereIn('auditable_id', $scopedRequests->pluck('RID'))
            ->whereIn('action', ['request_approved', 'request_rejected'])
            ->oldest('created_at')
            ->get(['auditable_id', 'created_at'])
            ->groupBy('auditable_id')
            ->map->first();
        $reviewDurations = $scopedRequests
            ->filter(fn (FacilityRequest $request) => $decisionLogs->has($request->RID) && $request->Created_at)
            ->map(fn (FacilityRequest $request) => Carbon::parse($request->Created_at)
                ->diffInMinutes(Carbon::parse($decisionLogs->get($request->RID)->created_at)) / 60);
        $averageReviewHours = $reviewDurations->isNotEmpty() ? round($reviewDurations->average(), 1) : null;

        $dayOrder = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        $peakBookingDays = $dayOrder->map(fn (string $day) => [
            'day' => $day,
            'count' => $scopedRequests->filter(
                fn (FacilityRequest $request) => $request->Proposed_Date?->format('l') === $day
            )->count(),
        ])->values()->all();
        $peakBookingHours = $scopedRequests
            ->filter(fn (FacilityRequest $request) => $request->Proposed_Start_Time)
            ->groupBy(fn (FacilityRequest $request) => $request->Proposed_Start_Time->format('g:00 A'))
            ->map->count()
            ->sortDesc();
        $peakBookingHour = $peakBookingHours->isNotEmpty()
            ? ['hour' => $peakBookingHours->keys()->first(), 'count' => $peakBookingHours->first()]
            : null;

        $facilityBookingHours = $scopedRequests
            ->filter(fn (FacilityRequest $request) => in_array($request->Status, ['Approved', 'Ended'], true)
                && $request->facility
                && $request->Proposed_Start_Time
                && $request->Proposed_End_Time)
            ->groupBy(fn (FacilityRequest $request) => $request->facility->Facility_Name)
            ->map(function ($requests): float {
                return round($requests->sum(function (FacilityRequest $request): float {
                    $dailyHours = max(0, $request->Proposed_Start_Time->diffInMinutes($request->Proposed_End_Time) / 60);
                    $days = $request->Proposed_Date->diffInDays($request->Proposed_End_Date ?? $request->Proposed_Date) + 1;

                    return $dailyHours * $days;
                }), 1);
            })
            ->sortDesc();
        $totalFacilityBookingHours = (float) $facilityBookingHours->sum();
        $facilityUtilization = $facilityBookingHours->map(fn (float $hours, string $facility) => [
            'facility' => $facility,
            'hours' => $hours,
            'share' => $totalFacilityBookingHours > 0 ? round(($hours / $totalFacilityBookingHours) * 100, 1) : 0,
        ])->values()->all();

        $facilityStatusRecords = (clone $baseQuery)
            ->whereNotNull('Facility_ID')
            ->selectRaw('Facility_ID, Status, COUNT(*) as total')
            ->groupBy('Facility_ID', 'Status')
            ->get();
        $statusFacilities = Facility::withTrashed()
            ->whereIn('FID', $facilityStatusRecords->pluck('Facility_ID')->unique())
            ->get(['FID', 'Facility_Name'])
            ->keyBy('FID');
        $facilityStatusBreakdown = $facilityStatusRecords
            ->groupBy('Facility_ID')
            ->map(function ($records, $facilityId) use ($statusFacilities, $statuses): array {
                $counts = collect($statuses)->mapWithKeys(fn (string $status): array => [
                    $status => (int) ($records->firstWhere('Status', $status)?->total ?? 0),
                ])->all();

                return [
                    'facility' => $statusFacilities->get($facilityId)?->Facility_Name ?? 'Unknown facility',
                    'statuses' => $counts,
                    'total' => array_sum($counts),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'expectedCapacityToday' => (int) (clone $baseQuery)
                ->when(! $dateFrom, fn (Builder $query) => $query->whereDate('Proposed_Date', today()))
                ->whereNotIn('Status', ['Rejected', 'Cancelled'])
                ->sum('Capacity'),
            'capacityMetricLabel' => $dateFrom ? 'Expected in Range' : 'Expected Today',
            'capacityMetricNote' => $dateFrom
                ? 'Expected attendees from requests submitted in the selected range.'
                : 'Active expected attendees for today.',
            'rejectedRequests' => $requestStatusCounts['Rejected'] ?? 0,
            'cancelledRequests' => $requestStatusCounts['Cancelled'] ?? 0,
            'mostUsedFacility' => $mostUsedFacility,
            'facilityTypeUsage' => $facilityTypeUsage,
            'eventTypeUsage' => $eventTypeUsage,
            'mostUsedEventType' => $mostUsedEventType,
            'amenityUsage' => $amenityUsage,
            'mostUsedAmenity' => $mostUsedAmenity,
            'approvalRate' => $approvalRate,
            'approvalOutcomeCounts' => [
                'Approved' => $approvedOutcomeCount,
                'Rejected' => $rejectedOutcomeCount,
            ],
            'averageReviewHours' => $averageReviewHours,
            'reviewedRequestCount' => $reviewDurations->count(),
            'peakBookingDays' => $peakBookingDays,
            'peakBookingHour' => $peakBookingHour,
            'facilityUtilization' => $facilityUtilization,
            'facilityStatusBreakdown' => $facilityStatusBreakdown,
            'rejectedRequestRecords' => (clone $baseQuery)
                ->with(['user', 'facility'])
                ->where('Status', 'Rejected')
                ->latest('Created_at')
                ->take(5)
                ->get(),
            'cancelledRequestRecords' => (clone $baseQuery)
                ->with(['user', 'facility'])
                ->where('Status', 'Cancelled')
                ->latest('Created_at')
                ->take(5)
                ->get(),
            'dashboardStatusCounts' => $requestStatusCounts,
            'dailyCapacityLabels' => $dailyLabels,
            'dailyCapacityTotals' => $dailyCapacityTotals,
            'dailyRequestTotals' => $dailyRequestTotals,
            'facilityCapacitySeries' => $facilityCapacitySeries,
            'facilityUsageSeries' => $facilityUsageSeries,
        ];
    }

}
