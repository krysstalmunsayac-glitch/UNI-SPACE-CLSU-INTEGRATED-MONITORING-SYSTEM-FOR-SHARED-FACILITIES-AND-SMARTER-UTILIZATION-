<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Support\CalendarColor;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(HttpRequest $httpRequest): View
    {
        Requests::markPastRequestsAsEnded();

        $requestSort = in_array($httpRequest->query('request_sort'), ['latest', 'oldest'], true)
            ? $httpRequest->query('request_sort')
            : 'latest';
        $requestStatus = in_array($httpRequest->query('request_status'), ['Pending', 'Approved', 'Ended'], true)
            ? $httpRequest->query('request_status')
            : '';

        $requestMetrics = $this->requestDashboardMetrics(
            Requests::withTrashed()->where('User_ID', Auth::id())
        );

        $userRequests = Requests::withTrashed()
            ->where('User_ID', Auth::id())
            ->where(function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->orWhere('Status', 'Ended');
            })
            ->with(['facility', 'event', 'feedback'])
            ->when($requestStatus, fn (Builder $query) => $query->where('Status', $requestStatus))
            ->orderBy('Created_at', $requestSort === 'oldest' ? 'asc' : 'desc')
            ->orderBy('RID', $requestSort === 'oldest' ? 'asc' : 'desc')
            ->paginate(5, ['*'], 'requests_page')
            ->withQueryString()
            ->fragment('requests');

        return view('dashboards.user', [
            'facilities' => Facilities::query()
                ->with('images')
                ->where('Status', 'Available')
                ->orderBy('Facility_Name')
                ->get(),
            'events' => Events::query()->orderBy('Event_Title')->get(),
            'requests' => $userRequests,
            'requestSort' => $requestSort,
            'requestStatus' => $requestStatus,
            'totalUserRequests' => Requests::withTrashed()
                ->where('User_ID', Auth::id())
                ->where(function (Builder $query) {
                    $query->whereNull('deleted_at')
                        ->orWhere('Status', 'Ended');
                })
                ->count(),
            'schedules' => $this->publicScheduleEvents(),
            ...$requestMetrics,
        ]);
    }

    public function superAdmin(HttpRequest $httpRequest): View
    {
        [$dateFrom, $dateTo] = $this->analyticsDateRange($httpRequest);
        $analyticsQuery = Requests::withTrashed()
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

        return view('dashboards.super-admin', [
            'totalUsers' => User::query()->count(),
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
        ]);
    }

    public function officeAdmin(HttpRequest $httpRequest): View
    {
        $user = Auth::user();
        [$dateFrom, $dateTo] = $this->analyticsDateRange($httpRequest);
        $requestMetricsQuery = Requests::withTrashed()
            ->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', $user?->id))
            ->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $facilityQuery = Facilities::query()->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user?->id));

        $requestMetrics = $this->requestDashboardMetrics($requestMetricsQuery, $dateFrom, $dateTo);

        return view('dashboards.office-admin', [
            'facilityCount' => $facilityQuery->count(),
            'rangeRequests' => (clone $requestMetricsQuery)->count(),
            'analyticsDateFrom' => $dateFrom->toDateString(),
            'analyticsDateTo' => $dateTo->toDateString(),
            'analyticsDateLabel' => $dateFrom->format('M d, Y').' – '.$dateTo->format('M d, Y'),
            ...$requestMetrics,
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

    private function analyticsDateRange(HttpRequest $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = isset($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : today()->subDays(29)->startOfDay();
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
