@php
    $dashboardChartPrefix = $dashboardChartPrefix ?? 'dashboard';
    $expectedCapacityToday = $expectedCapacityToday ?? 0;
    $capacityMetricLabel = $capacityMetricLabel ?? 'Expected Today';
    $capacityMetricNote = $capacityMetricNote ?? 'Active expected attendees for today.';
    $dashboardStatusCounts = $dashboardStatusCounts ?? [];
    $dailyCapacityLabels = $dailyCapacityLabels ?? [];
    $dailyCapacityTotals = $dailyCapacityTotals ?? [];
    $dailyRequestTotals = $dailyRequestTotals ?? [];
    $facilityCapacitySeries = $facilityCapacitySeries ?? [];
    $facilityUsageSeries = $facilityUsageSeries ?? [];
    $mostUsedFacility = $mostUsedFacility ?? null;
    $facilityTypeUsage = $facilityTypeUsage ?? [];
    $eventTypeUsage = $eventTypeUsage ?? [];
    $mostUsedEventType = $mostUsedEventType ?? null;
    $amenityUsage = $amenityUsage ?? [];
    $mostUsedAmenity = $mostUsedAmenity ?? null;
    $facilityStatusBreakdown = $facilityStatusBreakdown ?? [];
    $rejectedRequestRecords = $rejectedRequestRecords ?? collect();
    $cancelledRequestRecords = $cancelledRequestRecords ?? collect();
    $approvalRate = $approvalRate ?? null;
    $approvalOutcomeCounts = $approvalOutcomeCounts ?? ['Approved' => 0, 'Rejected' => 0];
    $averageReviewHours = $averageReviewHours ?? null;
    $reviewedRequestCount = $reviewedRequestCount ?? 0;
    $peakBookingDays = $peakBookingDays ?? [];
    $peakBookingHour = $peakBookingHour ?? null;
    $facilityUtilization = $facilityUtilization ?? [];

    $capacityTrendData = collect($dailyCapacityLabels)->map(fn ($label, $index) => [
        'date' => $label,
        'capacity' => (int) ($dailyCapacityTotals[$index] ?? 0),
        'requests' => (int) ($dailyRequestTotals[$index] ?? 0),
    ])->values()->all();

    $facilityTypeChartData = collect($facilityTypeUsage)->map(fn ($count, $type) => [
        'type' => ucfirst($type),
        'count' => (int) $count,
    ])->values()->all();

    $eventTypeChartData = collect($eventTypeUsage)->map(fn ($count, $type) => [
        'type' => $type,
        'count' => (int) $count,
    ])->values()->all();

    $amenityChartData = collect($amenityUsage)->map(fn ($count, $name) => [
        'name' => $name,
        'count' => (int) $count,
    ])->values()->all();

    $requestStatusChartData = collect(['Pending', 'Approved', 'Rejected', 'Cancelled'])->map(fn ($status) => [
        'status' => $status,
        'count' => (int) ($dashboardStatusCounts[$status] ?? 0),
    ])->values()->all();

@endphp

<div>
    <div class="space-y-5">
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-[#e6c200] bg-[#FFD700] p-5 text-emerald-950 shadow-sm dark:border-[#FFD700]/60 dark:bg-[#FFD700]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase text-emerald-950">{{ $capacityMetricLabel }}</span>
                    <span class="rounded-full bg-emerald-950/10 px-2 py-1 text-xs">Analytics</span>
                </div>
                <div class="mt-5 text-4xl font-semibold">{{ $expectedCapacityToday }}</div>
                <p class="mt-2 text-sm text-emerald-950/75">{{ $capacityMetricNote }}</p>
            </div>

            <div class="rounded-[1.75rem] border border-emerald-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-xs font-semibold uppercase text-slate-500 dark:text-zinc-400">Most Used Facility</div>
                <div class="mt-5 truncate text-2xl font-semibold text-slate-950 dark:text-white">
                    {{ $mostUsedFacility['name'] ?? 'No data' }}
                </div>
                <p class="mt-2 text-sm text-slate-500 dark:text-zinc-400">
                    {{ $mostUsedFacility ? $mostUsedFacility['count'].' request records' : 'No facility requests yet.' }}
                </p>
            </div>

            <div class="rounded-[1.75rem] border border-sky-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-xs font-semibold uppercase text-slate-500 dark:text-zinc-400">Most Used Event Type</div>
                <div class="mt-5 truncate text-2xl font-semibold text-slate-950 dark:text-white">
                    {{ $mostUsedEventType['type'] ?? 'No data' }}
                </div>
                <p class="mt-2 text-sm text-slate-500 dark:text-zinc-400">
                    {{ $mostUsedEventType ? $mostUsedEventType['count'].' request records' : 'No event types recorded.' }}
                </p>
            </div>

            <div class="rounded-[1.75rem] border border-violet-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-xs font-semibold uppercase text-slate-500 dark:text-zinc-400">Most Used Amenity</div>
                <div class="mt-5 truncate text-2xl font-semibold text-slate-950 dark:text-white">
                    {{ $mostUsedAmenity['name'] ?? 'No data' }}
                </div>
                <p class="mt-2 text-sm text-slate-500 dark:text-zinc-400">
                    {{ $mostUsedAmenity ? $mostUsedAmenity['count'].' request records' : 'No requested amenities recorded.' }}
                </p>
            </div>

        </div>

        <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Expected Capacity</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Capacity and request volume by proposed date.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">{{ count($dailyCapacityLabels) }} days</span>
            </div>

            <div class="mt-5">
                @if (! empty($capacityTrendData))
                    <canvas id="{{ $dashboardChartPrefix }}CapacityLineChart" class="h-72 w-full"></canvas>
                @else
                    <div class="flex min-h-72 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                        No capacity trend data available.
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Request Status by Facility</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Compare request decisions for each facility.</p>
                    </div>
                    <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $analyticsDateLabel ?? 'Selected date range' }}
                    </span>
                </div>

                <div class="mt-5">
                    @if (! empty($facilityStatusBreakdown))
                        <canvas id="{{ $dashboardChartPrefix }}StatusBarChart" class="h-64 w-full"></canvas>
                    @else
                        <div class="flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                            No facility request statuses in this period.
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Facility Usage by Date</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Daily request activity for each facility.</p>
                </div>

                <div class="mt-5">
                    @if (! empty($facilityUsageSeries))
                        <canvas id="{{ $dashboardChartPrefix }}FacilityTypeChart" class="h-64 w-full"></canvas>
                    @else
                        <div class="flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                            No facility usage in this period.
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Most Used Event Types</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Request distribution by the event types entered by users.</p>
                </div>

                <div class="mt-5">
                    @if (! empty($eventTypeChartData))
                        <canvas id="{{ $dashboardChartPrefix }}EventTypeChart" class="h-64 w-full"></canvas>
                    @else
                        <div class="flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                            No event-type usage in this period.
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Most Used Amenities</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">How often each amenity was included in facility requests.</p>
                </div>

                <div class="mt-5">
                    @if (! empty($amenityChartData))
                        <canvas id="{{ $dashboardChartPrefix }}AmenityChart" class="h-64 w-full"></canvas>
                    @else
                        <div class="flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                            No amenity usage in this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Approval Rate</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Approved outcomes compared with rejected outcomes.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $analyticsDateLabel ?? 'Selected range' }}</span>
                </div>
                @if ($approvalRate !== null)
                    <div class="relative mt-5 h-64">
                        <canvas id="{{ $dashboardChartPrefix }}ApprovalRateChart" class="h-full w-full"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($approvalRate, 1) }}%</span>
                        </div>
                    </div>
                @else
                    <div class="mt-5 flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">No decided requests in this period.</div>
                @endif
            </div>

            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Average Review Time</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Average time from submission to approval or rejection.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $analyticsDateLabel ?? 'Selected range' }}</span>
                </div>
                <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-2xl bg-emerald-50 text-center dark:bg-emerald-400/10">
                    @if ($averageReviewHours !== null)
                        <div class="text-5xl font-bold text-emerald-800 dark:text-emerald-300">{{ number_format($averageReviewHours, 1) }}</div>
                        <div class="mt-2 text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">hours</div>
                        <p class="mt-4 text-sm text-emerald-700/80 dark:text-emerald-300/80">Based on {{ $reviewedRequestCount }} reviewed {{ Str::plural('request', $reviewedRequestCount) }}.</p>
                    @else
                        <p class="text-sm text-slate-500 dark:text-zinc-400">No completed reviews in this period.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Peak Booking Days</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Requests grouped by their proposed event day.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $analyticsDateLabel ?? 'Selected range' }}</span>
                </div>
                @if (collect($peakBookingDays)->sum('count') > 0)
                    <div class="mt-5 h-56"><canvas id="{{ $dashboardChartPrefix }}PeakDaysChart" class="h-full w-full"></canvas></div>
                    <p class="mt-3 text-center text-sm text-slate-500 dark:text-zinc-400">
                        Peak starting hour: <strong class="text-slate-800 dark:text-zinc-200">{{ $peakBookingHour ? $peakBookingHour['hour'].' ('.$peakBookingHour['count'].' requests)' : 'No data' }}</strong>
                    </p>
                @else
                    <div class="mt-5 flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">No booking-day data in this period.</div>
                @endif
            </div>

            <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Facility Booking-Hour Share</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Each facility’s share of approved booking hours.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $analyticsDateLabel ?? 'Selected range' }}</span>
                </div>
                @if (! empty($facilityUtilization))
                    <div class="mt-5 h-64"><canvas id="{{ $dashboardChartPrefix }}UtilizationChart" class="h-full w-full"></canvas></div>
                @else
                    <div class="mt-5 flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">No approved booking hours in this period.</div>
                @endif
            </div>
        </div>
    </div>

</div>

<div class="grid gap-5 md:grid-cols-2">
    <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Rejected Records</h2>
        <div class="mt-4 space-y-3">
            @forelse ($rejectedRequestRecords as $request)
                <div class="rounded-2xl bg-rose-50 px-4 py-3 dark:bg-rose-400/10">
                    <p class="truncate text-sm font-semibold text-slate-950 dark:text-white">#{{ $request->RID }} {{ $request->user?->name ?? 'Unknown' }}</p>
                    <p class="truncate text-xs text-rose-700 dark:text-rose-300">{{ $request->facility?->Facility_Name ?? 'No facility' }}</p>
                </div>
            @empty
                <p class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:bg-zinc-900 dark:text-zinc-400">No rejected records.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Cancelled Records</h2>
        <div class="mt-4 space-y-3">
            @forelse ($cancelledRequestRecords as $request)
                <div class="rounded-2xl bg-amber-50 px-4 py-3 dark:bg-amber-400/10">
                    <p class="truncate text-sm font-semibold text-slate-950 dark:text-white">#{{ $request->RID }} {{ $request->user?->name ?? 'Unknown' }}</p>
                    <p class="truncate text-xs text-amber-700 dark:text-amber-300">{{ $request->facility?->Facility_Name ?? 'No facility' }}</p>
                </div>
            @empty
                <p class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:bg-zinc-900 dark:text-zinc-400">No cancelled records.</p>
            @endforelse
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
@endonce

@push('scripts')
    <script>
        window.dashboardAnalyticsCharts = window.dashboardAnalyticsCharts || {};
        window.dashboardAnalyticsLoadAttempts = window.dashboardAnalyticsLoadAttempts || {};

        window[@json('initDashboardAnalytics'.$dashboardChartPrefix)] = function () {
            const statusCtx = document.getElementById(@json($dashboardChartPrefix . 'StatusBarChart'));
            const capacityCtx = document.getElementById(@json($dashboardChartPrefix . 'CapacityLineChart'));
            const facilityTypeCtx = document.getElementById(@json($dashboardChartPrefix . 'FacilityTypeChart'));
            const eventTypeCtx = document.getElementById(@json($dashboardChartPrefix . 'EventTypeChart'));
            const amenityCtx = document.getElementById(@json($dashboardChartPrefix . 'AmenityChart'));
            const approvalRateCtx = document.getElementById(@json($dashboardChartPrefix . 'ApprovalRateChart'));
            const peakDaysCtx = document.getElementById(@json($dashboardChartPrefix . 'PeakDaysChart'));
            const utilizationCtx = document.getElementById(@json($dashboardChartPrefix . 'UtilizationChart'));
            const chartKey = @json($dashboardChartPrefix);
            const capacityData = @json($capacityTrendData);
            const facilityCapacitySeries = @json($facilityCapacitySeries);
            const facilityUsageSeries = @json($facilityUsageSeries);
            const facilityTypeData = @json($facilityTypeChartData);
            const eventTypeData = @json($eventTypeChartData);
            const amenityData = @json($amenityChartData);
            const facilityStatusData = @json($facilityStatusBreakdown);
            const approvalOutcomeData = @json($approvalOutcomeCounts);
            const peakDaysData = @json($peakBookingDays);
            const utilizationData = @json($facilityUtilization);
            const isDark = document.documentElement.classList.contains('dark');
            const tickColor = isDark ? '#a1a1aa' : '#64748b';
            const legendColor = isDark ? '#e4e4e7' : '#334155';
            const gridColor = isDark ? 'rgba(113, 113, 122, 0.25)' : 'rgba(148, 163, 184, 0.18)';

            if (!window.Chart) {
                window.dashboardAnalyticsLoadAttempts[chartKey] = (window.dashboardAnalyticsLoadAttempts[chartKey] || 0) + 1;

                if (window.dashboardAnalyticsLoadAttempts[chartKey] <= 20) {
                    window.setTimeout(window[@json('initDashboardAnalytics'.$dashboardChartPrefix)], 150);
                }

                return;
            }

            window.dashboardAnalyticsLoadAttempts[chartKey] = 0;

            if (capacityCtx) {
                const facilityColors = ['#009639', '#10b981', '#2563eb', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#84cc16'];
                const facilityDatasets = facilityCapacitySeries.map((series, index) => ({
                    label: series.facility,
                    data: series.totals,
                    borderColor: facilityColors[index % facilityColors.length],
                    backgroundColor: facilityColors[index % facilityColors.length],
                    pointRadius: 3,
                    tension: 0.38,
                    fill: false,
                }));

                window.dashboardAnalyticsCharts[capacityCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[capacityCtx.id] = new Chart(capacityCtx, {
                    type: 'bar',
                    data: {
                        labels: capacityData.map(item => item.date),
                        datasets: [
                            ...facilityDatasets,
                            {
                                label: 'Total Requests',
                                data: capacityData.map(item => item.requests),
                                borderColor: '#64748b',
                                backgroundColor: '#64748b',
                                pointRadius: 3,
                                tension: 0.38,
                                fill: false,
                                borderDash: [5, 5],
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { grid: { display: false }, ticks: { color: tickColor } },
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
                        },
                        plugins: {
                            legend: { labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                        },
                    },
                });
            }

            if (statusCtx) {
                window.dashboardAnalyticsCharts[statusCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[statusCtx.id] = new Chart(statusCtx, {
                    type: 'bar',
                    data: {
                        labels: facilityStatusData.map(item => item.facility),
                        datasets: [
                            { label: 'Pending', data: facilityStatusData.map(item => item.statuses.Pending), backgroundColor: '#f59e0b' },
                            { label: 'Approved', data: facilityStatusData.map(item => item.statuses.Approved), backgroundColor: '#10b981' },
                            { label: 'Rejected', data: facilityStatusData.map(item => item.statuses.Rejected), backgroundColor: '#f43f5e' },
                            { label: 'Cancelled', data: facilityStatusData.map(item => item.statuses.Cancelled), backgroundColor: '#64748b' },
                        ],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true,
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: tickColor, precision: 0, stepSize: 1 },
                                title: { display: true, text: 'Number of requests', color: legendColor, font: { weight: '600' } },
                            },
                            y: {
                                stacked: true,
                                grid: { display: false },
                                ticks: { color: tickColor },
                                title: { display: true, text: 'Facility', color: legendColor, font: { weight: '600' } },
                            },
                        },
                        plugins: {
                            legend: { labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                            tooltip: {
                                callbacks: {
                                    title: items => items[0]?.label || 'Facility',
                                    label: context => `${context.dataset.label}: ${Math.round(context.parsed.x)} request${Math.round(context.parsed.x) === 1 ? '' : 's'}`,
                                },
                            },
                        },
                    },
                });
            }

            if (facilityTypeCtx) {
                const facilityColors = ['#009639', '#2563eb', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#84cc16', '#64748b'];
                window.dashboardAnalyticsCharts[facilityTypeCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[facilityTypeCtx.id] = new Chart(facilityTypeCtx, {
                    type: 'line',
                    data: {
                        labels: capacityData.map(item => item.date),
                        datasets: facilityUsageSeries.map((series, index) => ({
                            label: series.facility,
                            data: series.totals,
                            borderColor: facilityColors[index % facilityColors.length],
                            backgroundColor: facilityColors[index % facilityColors.length],
                            pointRadius: 3,
                            tension: 0.35,
                            fill: false,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { grid: { display: false }, ticks: { color: tickColor, maxRotation: 45, minRotation: 0 } },
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
                        },
                        plugins: {
                            legend: { labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                        },
                    },
                });
            }

            if (eventTypeCtx) {
                window.dashboardAnalyticsCharts[eventTypeCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[eventTypeCtx.id] = new Chart(eventTypeCtx, {
                    type: 'bar',
                    data: {
                        labels: eventTypeData.map(item => item.type),
                        datasets: [{
                            label: 'Requests',
                            data: eventTypeData.map(item => item.count),
                            backgroundColor: '#0f766e',
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { grid: { display: false }, ticks: { color: tickColor } },
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
                        },
                        plugins: {
                            legend: { display: false },
                        },
                    },
                });
            }

            if (amenityCtx) {
                window.dashboardAnalyticsCharts[amenityCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[amenityCtx.id] = new Chart(amenityCtx, {
                    type: 'bar',
                    data: {
                        labels: amenityData.map(item => item.name),
                        datasets: [{
                            label: 'Requests',
                            data: amenityData.map(item => item.count),
                            backgroundColor: '#7c3aed',
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
                            y: { grid: { display: false }, ticks: { color: tickColor } },
                        },
                        plugins: {
                            legend: { display: false },
                        },
                    },
                });
            }

            if (approvalRateCtx) {
                window.dashboardAnalyticsCharts[approvalRateCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[approvalRateCtx.id] = new Chart(approvalRateCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Approved', 'Rejected'],
                        datasets: [{
                            label: 'Decided requests',
                            data: [approvalOutcomeData.Approved || 0, approvalOutcomeData.Rejected || 0],
                            backgroundColor: ['#10b981', '#f43f5e'],
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { position: 'bottom', labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                            tooltip: { callbacks: { label: context => `${context.label}: ${context.raw} requests` } },
                        },
                    },
                });
            }

            if (peakDaysCtx) {
                window.dashboardAnalyticsCharts[peakDaysCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[peakDaysCtx.id] = new Chart(peakDaysCtx, {
                    type: 'bar',
                    data: {
                        labels: peakDaysData.map(item => item.day.slice(0, 3)),
                        datasets: [{
                            label: 'Requests',
                            data: peakDaysData.map(item => item.count),
                            backgroundColor: '#2563eb',
                            borderRadius: 7,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { grid: { display: false }, ticks: { color: tickColor }, title: { display: true, text: 'Event day', color: legendColor } },
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0, stepSize: 1 }, title: { display: true, text: 'Number of requests', color: legendColor } },
                        },
                        plugins: {
                            legend: { labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                            tooltip: { callbacks: { label: context => `Requests: ${Math.round(context.parsed.y)}` } },
                        },
                    },
                });
            }

            if (utilizationCtx) {
                window.dashboardAnalyticsCharts[utilizationCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[utilizationCtx.id] = new Chart(utilizationCtx, {
                    type: 'bar',
                    data: {
                        labels: utilizationData.map(item => item.facility),
                        datasets: [{
                            label: 'Share of approved hours',
                            data: utilizationData.map(item => item.share),
                            backgroundColor: '#7c3aed',
                            borderRadius: 7,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { beginAtZero: true, max: 100, grid: { color: gridColor }, ticks: { color: tickColor, callback: value => `${value}%` }, title: { display: true, text: 'Share of approved booking hours', color: legendColor } },
                            y: { grid: { display: false }, ticks: { color: tickColor }, title: { display: true, text: 'Facility', color: legendColor } },
                        },
                        plugins: {
                            legend: { labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                            tooltip: { callbacks: { label: context => {
                                const item = utilizationData[context.dataIndex];
                                return `${item.share}% (${item.hours} booked hours)`;
                            } } },
                        },
                    },
                });
            }
        };

        window.initCurrentDashboardAnalytics = function () {
            window[@json('initDashboardAnalytics'.$dashboardChartPrefix)]();
        };

        document.addEventListener('DOMContentLoaded', window.initCurrentDashboardAnalytics);
        document.addEventListener('livewire:navigated', window.initCurrentDashboardAnalytics);
        window.addEventListener('pageshow', window.initCurrentDashboardAnalytics);

        if (document.readyState !== 'loading') {
            window.initCurrentDashboardAnalytics();
        }
    </script>
@endpush
