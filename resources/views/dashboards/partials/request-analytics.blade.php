@php
    $dashboardChartPrefix = $dashboardChartPrefix ?? 'dashboard';
    $expectedCapacityToday = $expectedCapacityToday ?? 0;
    $capacityMetricLabel = $capacityMetricLabel ?? 'Expected Today';
    $capacityMetricNote = $capacityMetricNote ?? 'Active expected attendees for today.';
    $rejectedRequests = $rejectedRequests ?? 0;
    $cancelledRequests = $cancelledRequests ?? 0;
    $dashboardStatusCounts = $dashboardStatusCounts ?? [];
    $dailyCapacityLabels = $dailyCapacityLabels ?? [];
    $dailyCapacityTotals = $dailyCapacityTotals ?? [];
    $dailyRequestTotals = $dailyRequestTotals ?? [];
    $facilityCapacitySeries = $facilityCapacitySeries ?? [];
    $mostUsedFacility = $mostUsedFacility ?? null;
    $facilityTypeUsage = $facilityTypeUsage ?? [];
    $eventTypeUsage = $eventTypeUsage ?? [];
    $mostUsedEventType = $mostUsedEventType ?? null;
    $amenityUsage = $amenityUsage ?? [];
    $mostUsedAmenity = $mostUsedAmenity ?? null;
    $facilityStatusBreakdown = $facilityStatusBreakdown ?? [];
    $rejectedRequestRecords = $rejectedRequestRecords ?? collect();
    $cancelledRequestRecords = $cancelledRequestRecords ?? collect();

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
@endphp

<div>
    <div class="space-y-5">
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-6">
            <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-950 p-5 text-white shadow-sm dark:border-emerald-400/20 dark:bg-emerald-500/15">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase text-emerald-100 dark:text-emerald-200">{{ $capacityMetricLabel }}</span>
                    <span class="rounded-full bg-white/15 px-2 py-1 text-xs">Analytics</span>
                </div>
                <div class="mt-5 text-4xl font-semibold">{{ $expectedCapacityToday }}</div>
                <p class="mt-2 text-sm text-emerald-100 dark:text-emerald-200">{{ $capacityMetricNote }}</p>
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

            <div class="rounded-[1.75rem] border border-rose-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-xs font-semibold uppercase text-slate-500 dark:text-zinc-400">Rejected</div>
                <div class="mt-5 text-4xl font-semibold text-slate-950 dark:text-white">{{ $rejectedRequests }}</div>
                <p class="mt-2 text-sm text-slate-500 dark:text-zinc-400">Requests marked rejected.</p>
            </div>

            <div class="rounded-[1.75rem] border border-amber-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-xs font-semibold uppercase text-slate-500 dark:text-zinc-400">Cancelled</div>
                <div class="mt-5 text-4xl font-semibold text-slate-950 dark:text-white">{{ $cancelledRequests }}</div>
                <p class="mt-2 text-sm text-slate-500 dark:text-zinc-400">Archived cancellation records.</p>
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
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Request Status</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Status totals by requested facility, including approvals.</p>
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
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Most Used Facility Types</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Request distribution by facility category.</p>
                </div>

                <div class="mt-5">
                    @if (! empty($facilityTypeChartData))
                        <canvas id="{{ $dashboardChartPrefix }}FacilityTypeChart" class="h-64 w-full"></canvas>
                    @else
                        <div class="flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                            No facility-type usage in this period.
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
            const chartKey = @json($dashboardChartPrefix);
            const capacityData = @json($capacityTrendData);
            const facilityCapacitySeries = @json($facilityCapacitySeries);
            const facilityTypeData = @json($facilityTypeChartData);
            const eventTypeData = @json($eventTypeChartData);
            const amenityData = @json($amenityChartData);
            const facilityStatusData = @json($facilityStatusBreakdown);
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
                const facilityColors = ['#14532d', '#10b981', '#2563eb', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#84cc16'];
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
                    type: 'line',
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
                            x: { stacked: true, beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
                            y: { stacked: true, grid: { display: false }, ticks: { color: tickColor } },
                        },
                        plugins: {
                            legend: { labels: { color: legendColor, usePointStyle: true, boxWidth: 8 } },
                        },
                    },
                });
            }

            if (facilityTypeCtx) {
                window.dashboardAnalyticsCharts[facilityTypeCtx.id]?.destroy();
                window.dashboardAnalyticsCharts[facilityTypeCtx.id] = new Chart(facilityTypeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: facilityTypeData.map(item => item.type),
                        datasets: [{
                            data: facilityTypeData.map(item => item.count),
                            backgroundColor: ['#14532d', '#10b981', '#2563eb', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4'],
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: legendColor, usePointStyle: true, boxWidth: 8, padding: 16 },
                            },
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
