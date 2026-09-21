<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use App\Services\Reports\PdfReportExporter;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardAnalyticsService $analytics) {}

    public function analyticsPdf(HttpRequest $httpRequest, PdfReportExporter $exporter)
    {
        [$dateFrom, $dateTo] = $this->analytics->analyticsDateRange($httpRequest);
        $user = $httpRequest->user();
        $requestScope = FacilityRequest::withTrashed()
            ->when($user->isAdmin(), fn (Builder $query) => $query
                ->whereHas('facility.assignedAdmins', fn (Builder $adminQuery) => $adminQuery
                    ->where('users.id', $user->id)));
        $facilities = Facility::query()
            ->when($user->isAdmin(), fn (Builder $query) => $query->assignedToAdmin($user))
            ->orderBy('Facility_Name')
            ->get();
        $rangeQuery = (clone $requestScope)->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $requestMetrics = $this->analytics->requestDashboardMetrics($rangeQuery, $dateFrom, $dateTo);
        $analytics = $this->analytics->operationalAnalytics($requestScope, $facilities, $dateFrom, $dateTo);
        $amenityDemand = collect($requestMetrics['amenityUsage'] ?? [])->map(fn ($count, $name) => [
            'amenity' => $name,
            'count' => (int) $count,
        ])->values()->all();

        $content = $exporter->analytics([
            ...$analytics,
            'amenityDemand' => $amenityDemand,
            'kpis' => [
                'Facility' => $facilities->count(),
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
        $focusedFacilityId = $httpRequest->integer('map_facility');
        if ($focusedFacilityId && ! FacilityRequest::withTrashed()
            ->where('User_ID', Auth::id())
            ->where('Facility_ID', $focusedFacilityId)
            ->exists()) {
            $focusedFacilityId = null;
        }

        $requestMetrics = $this->analytics->requestDashboardMetrics(
            FacilityRequest::withTrashed()->where('User_ID', Auth::id())
        );

        $facilities = Facility::query()
            ->with(['images', 'amenities' => fn ($query) => $query
                ->where('amenities.Status', 'Available')
                ->orderBy('amenities.name')])
            ->orderBy('Facility_Name')
            ->get();

        $categoryLabels = [
            'auditorium' => 'Auditoriums',
            'amphitheater' => 'Amphitheaters',
            'little_theater' => 'Little theaters',
            'classroom' => 'Classrooms',
            'conference' => 'Conference spaces',
            'laboratory' => 'Laboratories',
            'sports' => 'Sports facilities',
            'other' => 'Other spaces',
        ];

        $facilityCategories = $facilities
            ->filter(fn (Facility $facility) => filled($facility->facility_type))
            ->groupBy(fn (Facility $facility) => strtolower($facility->facility_type))
            ->map(function ($group, string $type) use ($categoryLabels): array {
                $featured = $group->first(fn (Facility $facility) => $facility->images->isNotEmpty() || filled($facility->Image_URL));

                return [
                    'type' => $type,
                    'name' => $categoryLabels[$type] ?? ucfirst($type).' spaces',
                    'count' => $group->count(),
                    'image' => $featured?->primaryImageUrl() ?? asset('images/siel-space-slide-02.jpg'),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values();

        return view('dashboards.user', [
            'facilities' => $facilities,
            'facilityCategories' => $facilityCategories,
            'showsUnavailableFacilities' => true,
            'mapFacilities' => Facility::query()
                ->orderBy('Facility_Name')
                ->get([
                    'FID', 'Facility_Name', 'Location', 'Status', 'facility_type',
                    'Capacity', 'Latitude', 'Longitude',
                ]),
            'focusedFacilityId' => $focusedFacilityId,
            'events' => Event::query()->orderBy('Event_Title')->get(),
            'schedules' => $this->analytics->publicScheduleEvents(),
            ...$requestMetrics,
        ]);
    }

    public function superAdmin(HttpRequest $httpRequest): View
    {
        [$dateFrom, $dateTo] = $this->analytics->analyticsDateRange($httpRequest);
        $analyticsScope = FacilityRequest::withTrashed();
        $analyticsQuery = (clone $analyticsScope)
            ->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $monthlyLabels = [];
        $monthlyRequestTotals = [];
        $monthlyUserTotals = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyLabels[] = $month->format('M Y');
            $monthlyRequestTotals[] = FacilityRequest::query()->whereYear('Created_at', $month->year)->whereMonth('Created_at', $month->month)->count();
            $monthlyUserTotals[] = User::query()->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count();
        }

        $requestMetrics = $this->analytics->requestDashboardMetrics($analyticsQuery, $dateFrom, $dateTo);
        $responseRateMetrics = $this->analytics->responseRateMetrics($analyticsQuery);
        $facilities = Facility::query()->orderBy('Facility_Name')->get();
        $operationalAnalytics = $this->analytics->operationalAnalytics($analyticsScope, $facilities, $dateFrom, $dateTo);

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
            ...$responseRateMetrics,
            ...$operationalAnalytics,
        ]);
    }

    public function officeAdmin(HttpRequest $httpRequest): View
    {
        $user = Auth::user();
        [$dateFrom, $dateTo] = $this->analytics->analyticsDateRange($httpRequest);
        $requestScope = FacilityRequest::withTrashed()
            ->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', $user?->id));
        $requestMetricsQuery = (clone $requestScope)
            ->whereBetween('Created_at', [$dateFrom, $dateTo]);
        $facilityQuery = Facility::query()->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user?->id));
        $facilities = (clone $facilityQuery)->orderBy('Facility_Name')->get();

        $requestMetrics = $this->analytics->requestDashboardMetrics($requestMetricsQuery, $dateFrom, $dateTo);
        $responseRateMetrics = $this->analytics->responseRateMetrics($requestMetricsQuery);
        $operationalAnalytics = $this->analytics->operationalAnalytics($requestScope, $facilities, $dateFrom, $dateTo);

        return view('dashboards.office-admin', [
            'facilityCount' => $facilityQuery->count(),
            'rangeRequests' => (clone $requestMetricsQuery)->count(),
            'analyticsDateFrom' => $dateFrom->toDateString(),
            'analyticsDateTo' => $dateTo->toDateString(),
            'analyticsDateLabel' => $dateFrom->format('M d, Y').' – '.$dateTo->format('M d, Y'),
            ...$requestMetrics,
            ...$responseRateMetrics,
            ...$operationalAnalytics,
        ]);
    }

    public function facilityRedirect(): RedirectResponse
    {
        $user = Auth::user();

        return match (true) {
            $user?->isSuperAdmin() => redirect()->route('facilities.super-admin.index'),
            $user?->isAdmin() => redirect()->route('facilities.office-admin.index'),
            default => abort(403),
        };
    }
}
