<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Feedbacks;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AdminReportExporter;
use App\Support\CalendarColor;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function analyticsPdf(HttpRequest $httpRequest, AdminReportExporter $exporter)
    {
        [$dateFrom, $dateTo] = $this->analyticsDateRange($httpRequest);
        $user = $httpRequest->user();
        $requestScope = Requests::withTrashed()
            ->when($user->isAdmin(), fn (Builder $query) => $query
                ->whereHas('facility.assignedAdmins', fn (Builder $adminQuery) => $adminQuery
                    ->where('users.id', $user->id)));
        $facilities = Facilities::query()
            ->when($user->isAdmin(), fn (Builder $query) => $query->assignedToAdmin($user))
            ->orderBy('Facility_Name')
            ->get();
        $rangeQuery = (clone $requestScope)->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $requestMetrics = $this->requestDashboardMetrics($rangeQuery, $dateFrom, $dateTo);
        $analytics = $this->operationalAnalytics($requestScope, $facilities, $dateFrom, $dateTo);
        $amenityDemand = collect($requestMetrics['amenityUsage'] ?? [])->map(fn ($count, $name) => [
            'amenity' => $name,
            'count' => (int) $count,
        ])->values()->all();

        $content = $exporter->analyticsPdf([
            ...$analytics,
            'amenityDemand' => $amenityDemand,
            'kpis' => [
                'Facilities' => $facilities->count(),
                'Pending Requests' => $requestMetrics['dashboardStatusCounts']['Pending'] ?? 0,
                'Time Utilization' => ($analytics['overallFacilityUtilizationRate'] ?? 0).'%',
                'Approval Rate' => isset($requestMetrics['approvalRate']) ? $requestMetrics['approvalRate'].'%' : 'N/A',
                'Avg Review Time' => isset($requestMetrics['averageReviewHours']) ? $requestMetrics['averageReviewHours'].' hours' : 'N/A',
            ],
        ], $user->isAdmin() ? 'Assigned facilities only' : 'All facilities', $dateFrom->format('M d, Y').' - '.$dateTo->format('M d, Y'));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facility-analytics-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function index(HttpRequest $httpRequest): View
    {
        Requests::markPastRequestsAsEnded();

        $focusedFacilityId = $httpRequest->integer('map_facility');
        if ($focusedFacilityId && ! Requests::withTrashed()
            ->where('User_ID', Auth::id())
            ->where('Facility_ID', $focusedFacilityId)
            ->exists()) {
            $focusedFacilityId = null;
        }

        $requestMetrics = $this->requestDashboardMetrics(
            Requests::withTrashed()->where('User_ID', Auth::id())
        );

        return view('dashboards.user', [
            'facilities' => Facilities::query()
                ->with('images')
                ->where('Status', 'Available')
                ->orderBy('Facility_Name')
                ->get(),
            'mapFacilities' => Facilities::query()
                ->orderBy('Facility_Name')
                ->get([
                    'FID', 'Facility_Name', 'Location', 'Status', 'facility_type',
                    'Capacity', 'Latitude', 'Longitude',
                ]),
            'focusedFacilityId' => $focusedFacilityId,
            'events' => Events::query()->orderBy('Event_Title')->get(),
            'schedules' => $this->publicScheduleEvents(),
            ...$requestMetrics,
        ]);
    }

    public function superAdmin(HttpRequest $httpRequest): View
    {
        [$dateFrom, $dateTo] = $this->analyticsDateRange($httpRequest);
        $analyticsScope = Requests::withTrashed();
        $analyticsQuery = (clone $analyticsScope)
            ->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $monthlyLabels = [];
        $monthlyRequestTotals = [];
        $monthlyUserTotals = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyLabels[] = $month->format('M Y');
            $monthlyRequestTotals[] = Requests::query()->whereYear('Created_at', $month->year)->whereMonth('Created_at', $month->month)->count();
            $monthlyUserTotals[] = User::query()->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count();
        }

        $requestMetrics = $this->requestDashboardMetrics($analyticsQuery, $dateFrom, $dateTo);
        $facilities = Facilities::query()->orderBy('Facility_Name')->get();
        $operationalAnalytics = $this->operationalAnalytics($analyticsScope, $facilities, $dateFrom, $dateTo);

        return view('dashboards.super-admin', [
            'totalUsers' => User::query()->count(),
            'facilityCount' => $facilities->count(),
            'totalRequests' => (clone $analyticsQuery)->count(),
            'pendingRequests' => (clone $analyticsQuery)->where('Status', 'Pending')->count(),
            'approvedRequests' => (clone $analyticsQuery)->where('Status', 'Approved')->count(),
            'analyticsDateFrom' => $dateFrom->toDateString(),
            'analyticsDateTo' => $dateTo->toDateString(),
            'analyticsDateLabel' => $dateFrom->format('M d, Y').' – '.$dateTo->format('M d, Y'),
            'monthlyLabels' => $monthlyLabels,
            'monthlyRequestTotals' => $monthlyRequestTotals,
            'monthlyUserTotals' => $monthlyUserTotals,
            'requestStatusCounts' => $requestMetrics['dashboardStatusCounts'],
            'recentRequests' => (clone $analyticsQuery)->with(['user', 'facility'])->latest('Created_at')->take(5)->get(),
            ...$requestMetrics,
            ...$operationalAnalytics,
        ]);
    }

    public function officeAdmin(HttpRequest $httpRequest): View
    {
        $user = Auth::user();
        [$dateFrom, $dateTo] = $this->analyticsDateRange($httpRequest);
        $requestScope = Requests::withTrashed()
            ->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', $user?->id));
        $requestMetricsQuery = (clone $requestScope)
            ->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $facilityQuery = Facilities::query()->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user?->id));
        $facilities = (clone $facilityQuery)->orderBy('Facility_Name')->get();

        $requestMetrics = $this->requestDashboardMetrics($requestMetricsQuery, $dateFrom, $dateTo);
        $operationalAnalytics = $this->operationalAnalytics($requestScope, $facilities, $dateFrom, $dateTo);

        return view('dashboards.office-admin', [
            'facilityCount' => $facilityQuery->count(),
            'rangeRequests' => (clone $requestMetricsQuery)->count(),
            'analyticsDateFrom' => $dateFrom->toDateString(),
            'analyticsDateTo' => $dateTo->toDateString(),
            'analyticsDateLabel' => $dateFrom->format('M d, Y').' – '.$dateTo->format('M d, Y'),
            ...$requestMetrics,
            ...$operationalAnalytics,
        ]);
    }

    private function requestDashboardMetrics(Builder $baseQuery, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
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
            ->groupBy(fn (Requests $request) => $request->facility?->Facility_Name ?? 'Unknown facility')
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
            ->groupBy(fn (Requests $request) => $request->facility?->Facility_Name ?? 'Unknown facility')
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
            $facility = Facilities::withTrashed()->find($mostUsedFacilityRecord->Facility_ID);

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

        $facilityTypeUsage = Facilities::withTrashed()
            ->whereIn('FID', $facilityUsageCounts->keys())
            ->get(['FID', 'facility_type'])
            ->groupBy(fn (Facilities $facility) => $facility->facility_type ?: 'Other')
            ->map(fn ($facilities) => $facilities->sum(
                fn (Facilities $facility) => (int) ($facilityUsageCounts[$facility->FID] ?? 0)
            ))
            ->sortDesc()
            ->all();

        $eventTypeUsage = (clone $baseQuery)
            ->with('event')
            ->whereNotNull('Event_ID')
            ->get()
            ->filter(fn (Requests $request) => filled($request->event?->Type_Event))
            ->groupBy(fn (Requests $request) => trim($request->event->Type_Event))
            ->map->count()
            ->sortDesc()
            ->all();

        $mostUsedEventType = collect($eventTypeUsage)
            ->map(fn (int $count, string $type) => ['type' => $type, 'count' => $count])
            ->first();

        $amenityUsage = (clone $baseQuery)
            ->with('amenities')
            ->get()
            ->flatMap(fn (Requests $request) => $request->amenities)
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
            ->where('auditable_type', Requests::class)
            ->whereIn('auditable_id', $scopedRequests->pluck('RID'))
            ->whereIn('action', ['request_approved', 'request_rejected'])
            ->oldest('created_at')
            ->get(['auditable_id', 'created_at'])
            ->groupBy('auditable_id')
            ->map->first();
        $reviewDurations = $scopedRequests
            ->filter(fn (Requests $request) => $decisionLogs->has($request->RID) && $request->Created_at)
            ->map(fn (Requests $request) => Carbon::parse($request->Created_at)
                ->diffInMinutes(Carbon::parse($decisionLogs->get($request->RID)->created_at)) / 60);
        $averageReviewHours = $reviewDurations->isNotEmpty() ? round($reviewDurations->average(), 1) : null;

        $dayOrder = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        $peakBookingDays = $dayOrder->map(fn (string $day) => [
            'day' => $day,
            'count' => $scopedRequests->filter(
                fn (Requests $request) => $request->Proposed_Date?->format('l') === $day
            )->count(),
        ])->values()->all();
        $peakBookingHours = $scopedRequests
            ->filter(fn (Requests $request) => $request->Proposed_Start_Time)
            ->groupBy(fn (Requests $request) => $request->Proposed_Start_Time->format('g:00 A'))
            ->map->count()
            ->sortDesc();
        $peakBookingHour = $peakBookingHours->isNotEmpty()
            ? ['hour' => $peakBookingHours->keys()->first(), 'count' => $peakBookingHours->first()]
            : null;

        $facilityBookingHours = $scopedRequests
            ->filter(fn (Requests $request) => in_array($request->Status, ['Approved', 'Ended'], true)
                && $request->facility
                && $request->Proposed_Start_Time
                && $request->Proposed_End_Time)
            ->groupBy(fn (Requests $request) => $request->facility->Facility_Name)
            ->map(function ($requests): float {
                return round($requests->sum(function (Requests $request): float {
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
        $statusFacilities = Facilities::withTrashed()
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

    /**
     * Build operational analytics from booked schedules and scoped requests.
     * Facilities currently have no operating-hours fields, so availability is
     * measured against the dashboard's documented 8 AM–6 PM window.
     */
    private function operationalAnalytics(
        Builder $requestScope,
        Collection $facilities,
        Carbon $dateFrom,
        Carbon $dateTo,
    ): array {
        $requestIds = (clone $requestScope)->pluck('RID');
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
        $scheduleRequests = Requests::withTrashed()
            ->whereIn('RID', $bookedSchedules->pluck('Request_ID')->unique())
            ->get(['RID', 'Facility_ID'])
            ->keyBy('RID');

        $bookedHoursByFacility = $bookedSchedules
            ->groupBy(fn (Schedule $schedule) => $scheduleRequests->get($schedule->Request_ID)?->Facility_ID)
            ->map(fn ($schedules) => round($schedules->sum(
                fn (Schedule $schedule) => max(0, $schedule->Start_Time->diffInMinutes($schedule->End_Time) / 60)
            ), 1));

        $facilityUtilizationRates = $facilities->map(function (Facilities $facility) use ($bookedHoursByFacility, $availableHoursPerFacility): array {
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
                    ->filter(fn (Requests $request) => $request->Status === $status
                        && $request->Created_at?->isSameMonth($month))
                    ->count())->all()]
            )->all(),
        ];

        $decisionLogs = AuditLog::query()
            ->where('auditable_type', Requests::class)
            ->whereIn('auditable_id', $requestIds)
            ->whereIn('action', ['request_approved', 'request_rejected'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->oldest('created_at')
            ->get(['auditable_id', 'created_at'])
            ->groupBy('auditable_id')
            ->map->first();
        $createdAtByRequest = Requests::withTrashed()
            ->whereIn('RID', $decisionLogs->keys())
            ->pluck('Created_at', 'RID');
        $reviewTimeTrend = $months->map(function (Carbon $month) use ($decisionLogs, $createdAtByRequest): array {
            $hours = $decisionLogs
                ->filter(fn ($log) => Carbon::parse($log->created_at)->isSameMonth($month))
                ->map(function ($log) use ($createdAtByRequest): ?float {
                    $submittedAt = $createdAtByRequest[$log->auditable_id] ?? null;

                    return $submittedAt ? Carbon::parse($submittedAt)->diffInMinutes(Carbon::parse($log->created_at)) / 60 : null;
                })->filter();

            return ['label' => $month->format('M Y'), 'hours' => $hours->isNotEmpty() ? round($hours->average(), 1) : null];
        })->values()->all();

        $rangeRequests = (clone $requestScope)
            ->with('facility:FID,Facility_Name,Capacity')
            ->whereBetween('Created_at', [$dateFrom, $dateTo])
            ->get(['RID', 'Facility_ID', 'Status', 'Capacity']);
        $capacityUtilization = $rangeRequests
            ->filter(fn (Requests $request) => $request->facility?->Capacity > 0 && $request->Capacity !== null)
            ->groupBy('Facility_ID')
            ->map(function ($requests, $facilityId) use ($facilityLookup): array {
                $facility = $facilityLookup->get($facilityId);
                $rate = $requests->average(fn (Requests $request) => min(100, $request->Capacity / $request->facility->Capacity * 100));

                return ['facility' => $facility?->Facility_Name ?? 'Unknown facility', 'rate' => round($rate, 1)];
            })->sortByDesc('rate')->values()->all();
        $facilityRequestGroups = $rangeRequests
            ->whereNotNull('Facility_ID')
            ->filter(fn (Requests $request) => $facilityLookup->has($request->Facility_ID))
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
        $facilityRatings = Feedbacks::query()
            ->whereIn('Facility_ID', $facilities->pluck('FID'))
            ->whereNotNull('Rating')
            ->whereBetween('Created_at', [$dateFrom, $dateTo])
            ->selectRaw('Facility_ID, AVG(Rating) as average_rating, COUNT(*) as rating_count')
            ->groupBy('Facility_ID')
            ->get()
            ->map(fn (Feedbacks $feedback): array => [
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
            'reviewTimeTrend' => $reviewTimeTrend,
            'capacityUtilization' => $capacityUtilization,
            'cancellationRates' => $cancellationRates,
            'facilityDecisionRates' => $facilityDecisionRates,
            'facilityRatings' => $facilityRatings,
            'availabilityBaseline' => '8:00 AM–6:00 PM daily',
        ];
    }

    private function analyticsDateRange(HttpRequest $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = isset($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : today()->subMonths(5)->startOfMonth();
        $dateTo = isset($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : today()->endOfDay();

        if ($dateFrom->diffInDays($dateTo) > 365) {
            $dateFrom = $dateTo->copy()->subDays(365)->startOfDay();
        }

        return [$dateFrom, $dateTo];
    }

    private function publicScheduleEvents(): array
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
                $colors = CalendarColor::forValue($facilityName);
                $isEnded = $schedule->request?->Status === 'Ended';

                return [
                    'id' => $schedule->SID,
                    'title' => $eventName,
                    'event' => $eventName,
                    'facility' => $facilityName,
                    'start' => Carbon::parse($schedule->Date)->toDateString().'T'.Carbon::parse($schedule->Start_Time)->format('H:i:s'),
                    'end' => Carbon::parse($schedule->Date)->toDateString().'T'.Carbon::parse($schedule->End_Time)->format('H:i:s'),
                    'backgroundColor' => $isEnded ? '#dc2626' : $colors['backgroundColor'],
                    'borderColor' => $isEnded ? '#991b1b' : $colors['borderColor'],
                ];
            })
            ->values()
            ->all();
    }

    public function facilityRedirect(): RedirectResponse
    {
        $user = Auth::user();

        return match (true) {
            $user?->isSuperAdmin() => redirect()->route('Facility.SuperAdmin'),
            $user?->isAdmin() => redirect()->route('Facility.OfficeAdmin'),
            default => abort(403),
        };
    }
}
