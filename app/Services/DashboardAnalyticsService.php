<?php

namespace App\Services;

use App\Services\Analytics\AnalyticsDateRange;
use App\Services\Analytics\OperationalAnalyticsQuery;
use App\Services\Analytics\PublicScheduleQuery;
use App\Services\Analytics\RequestAnalyticsQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Stable dashboard API that coordinates focused analytics queries. */
class DashboardAnalyticsService
{
    public function __construct(
        private readonly RequestAnalyticsQuery $requests,
        private readonly OperationalAnalyticsQuery $operations,
        private readonly AnalyticsDateRange $dateRange,
        private readonly PublicScheduleQuery $publicSchedule,
    ) {}

    public function responseRateMetrics(Builder $baseQuery): array
    {
        return $this->requests->responseRateMetrics($baseQuery);
    }

    public function requestDashboardMetrics(Builder $baseQuery, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        return $this->requests->requestDashboardMetrics($baseQuery, $dateFrom, $dateTo);
    }

    public function operationalAnalytics(Builder $requestScope, Collection $facilities, Carbon $dateFrom, Carbon $dateTo): array
    {
        return $this->operations->operationalAnalytics($requestScope, $facilities, $dateFrom, $dateTo);
    }

    public function analyticsDateRange(Request $request): array
    {
        return $this->dateRange->analyticsDateRange($request);
    }

    public function publicScheduleEvents(): array
    {
        return $this->publicSchedule->publicScheduleEvents();
    }
}
