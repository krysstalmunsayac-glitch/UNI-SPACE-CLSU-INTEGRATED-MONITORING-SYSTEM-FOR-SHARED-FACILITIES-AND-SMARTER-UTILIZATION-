<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Support\CalendarColor;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\RedirectResponse;
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

        $userRequests = Requests::query()
            ->where('User_ID', Auth::id())
            ->with(['facility', 'event'])
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
            'events' => Events::query()->where('Status', 'Upcoming')->orderBy('Event_Title')->get(),
            'requests' => $userRequests,
            'requestSort' => $requestSort,
            'requestStatus' => $requestStatus,
            'totalUserRequests' => Requests::query()->where('User_ID', Auth::id())->count(),
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
            ->with('request.facility')
            ->where('Status', 'Booked')
            ->get()
            ->map(function (Schedule $schedule): array {
                $facilityName = $schedule->request?->facility?->Facility_Name
                    ?? "Request #{$schedule->Request_ID}";
                $colors = CalendarColor::forValue($facilityName);
                $isEnded = $schedule->request?->Status === 'Ended';

                return [
                    'id' => $schedule->SID,
                    'title' => $facilityName,
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
