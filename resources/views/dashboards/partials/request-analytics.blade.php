@php
    $dashboardChartPrefix ??= 'dashboard';
    $facilityUtilizationRates ??= [];
    $bookingDemandHeatmap ??= [];
    $requestOutcomesTrend ??= ['labels' => [], 'series' => []];
    $reviewTimeTrend ??= [];
    $capacityUtilization ??= [];
    $cancellationRates ??= [];
    $facilityDecisionRates ??= [];
    $facilityRatings ??= [];
    $amenityUsage ??= [];
    $amenityDemand = collect($amenityUsage)->map(fn ($count, $name) => [
        'amenity' => $name,
        'count' => (int) $count,
    ])->values()->all();
    $heatmapHours = range(8, 17);
    $heatmapMaximum = max(1, collect($bookingDemandHeatmap)->flatten()->max() ?? 0);
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900';
    $chartData = [
        'utilization' => $facilityUtilizationRates,
        'outcomes' => $requestOutcomesTrend,
        'reviews' => $reviewTimeTrend,
        'capacity' => $capacityUtilization,
        'cancellations' => $cancellationRates,
        'decisions' => $facilityDecisionRates,
        'ratings' => $facilityRatings,
        'amenities' => $amenityDemand,
    ];
@endphp

<div class="space-y-5">
    <div class="grid gap-5 xl:grid-cols-2">
        <section class="{{ $card }}">
            <h3 class="text-lg font-bold">Booking Demand Heatmap</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Approved bookings by weekday and hour.</p>
            <div class="mt-5 overflow-x-auto">
                <div class="grid gap-1 text-center text-xs" style="min-width: 560px; grid-template-columns: 68px repeat(10, minmax(42px, 1fr));">
                    <div></div>
                    @foreach ($heatmapHours as $hour)
                        <div class="pb-2 font-semibold text-slate-500 dark:text-zinc-400">{{ \Carbon\Carbon::createFromTime($hour)->format('g A') }}</div>
                    @endforeach
                    @foreach ($bookingDemandHeatmap as $day => $hours)
                        <div class="flex items-center font-semibold text-slate-600 dark:text-zinc-300">{{ substr($day, 0, 3) }}</div>
                        @foreach ($heatmapHours as $hour)
                            @php
                                $count = (int) ($hours[$hour] ?? 0);
                                $opacity = $count > 0 ? max(.18, $count / $heatmapMaximum) : .05;
                            @endphp
                            <div class="flex h-10 items-center justify-center rounded-md border border-emerald-100 text-xs font-bold text-emerald-950 dark:border-emerald-900/40 dark:text-emerald-100"
                                style="background-color: rgb(16 185 129 / {{ $opacity }})"
                                title="{{ $day }}, {{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}: {{ $count }} {{ Str::plural('booking', $count) }}">{{ $count ?: '' }}</div>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-zinc-400">Darker cells indicate higher booking demand.</p>
        </section>

        <section class="{{ $card }}">
            <h3 class="text-lg font-bold">Facility Utilization Rate</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Booked hours as a percentage of available hours ({{ $availabilityBaseline ?? '8:00 AM–6:00 PM daily' }}).</p>
            <div class="mt-5" style="height: {{ max(320, count($facilityUtilizationRates) * 44) }}px;"><canvas id="{{ $dashboardChartPrefix }}UtilizationRateChart"></canvas></div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="{{ $card }}">
            <h3 class="text-lg font-bold">Request Outcomes Over Time</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Monthly request outcomes in the selected period.</p>
            <div class="mt-5 h-72"><canvas id="{{ $dashboardChartPrefix }}OutcomesChart"></canvas></div>
        </section>
        <section class="{{ $card }}">
            <h3 class="text-lg font-bold">Average Review Time Trend</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Average hours from submission to approval or rejection.</p>
            <div class="mt-5 h-72"><canvas id="{{ $dashboardChartPrefix }}ReviewTrendChart"></canvas></div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="{{ $card }}">
            <h3 class="text-lg font-bold">Capacity Utilization</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Expected attendance compared with maximum facility capacity.</p>
            <div class="mt-5 h-72"><canvas id="{{ $dashboardChartPrefix }}CapacityUtilizationChart"></canvas></div>
        </section>
        <section class="{{ $card }}">
            <h3 class="text-lg font-bold">Cancellation Rate by Facility</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Cancelled requests as a percentage of facility requests.</p>
            <div class="mt-5 h-72"><canvas id="{{ $dashboardChartPrefix }}CancellationRateChart"></canvas></div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
    <section class="{{ $card }}">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div><h3 class="text-lg font-bold">Approval / Rejection Rate by Facility</h3><p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Secondary analytics for decided requests.</p></div>
        </div>
        <div class="mt-5 h-72"><canvas id="{{ $dashboardChartPrefix }}DecisionRateChart"></canvas></div>
    </section>
    <section class="{{ $card }}">
        <h3 class="text-lg font-bold">Facility Ratings</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Average user feedback rating for each facility.</p>
        <div class="mt-5 h-72"><canvas id="{{ $dashboardChartPrefix }}FacilityRatingChart"></canvas></div>
    </section>
    </div>

    <section class="{{ $card }}">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-bold">Amenity Demand</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Most frequently requested amenities in the selected period.</p>
            </div>
        </div>
        @if ($amenityDemand !== [])
            <div class="mt-5" style="height: {{ max(260, count($amenityDemand) * 42) }}px;"><canvas id="{{ $dashboardChartPrefix }}AmenityDemandChart"></canvas></div>
        @else
            <div class="mt-5 flex h-40 items-center justify-center rounded-xl border border-dashed border-slate-200 text-sm text-slate-500 dark:border-zinc-700 dark:text-zinc-400">No amenity requests in this period.</div>
        @endif
    </section>
</div>

@once
    @push('scripts')<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>@endpush
@endonce

@push('scripts')
<script>
(() => {
    const prefix = @json($dashboardChartPrefix);
    const data = @json($chartData);
    window.dashboardAnalyticsCharts ||= {};
    const init = () => {
        if (!window.Chart) return window.setTimeout(init, 100);
        const dark = document.documentElement.classList.contains('dark');
        const text = dark ? '#d4d4d8' : '#475569';
        const grid = dark ? 'rgba(113,113,122,.22)' : 'rgba(148,163,184,.22)';
        const canvas = name => document.getElementById(prefix + name);
        const replace = (element, config) => {
            if (!element) return;
            window.dashboardAnalyticsCharts[element.id]?.destroy();
            window.dashboardAnalyticsCharts[element.id] = new Chart(element, config);
        };
        const percentOptions = {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, max: 100, grid: { color: grid }, ticks: { color: text, callback: value => value + '%' } }, y: { grid: { display: false }, ticks: { color: text, autoSkip: false } } },
            plugins: { legend: { display: false } },
        };
        replace(canvas('UtilizationRateChart'), {
            type: 'bar', data: { labels: data.utilization.map(x => x.facility), datasets: [{ data: data.utilization.map(x => x.rate), backgroundColor: data.utilization.map(x => x.rate >= 70 ? '#047857' : x.rate >= 35 ? '#10b981' : '#34d399'), borderRadius: 7, minBarLength: 3 }] },
            options: { ...percentOptions, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => { const x = data.utilization[c.dataIndex]; return `${x.rate}% — ${x.bookedHours} of ${x.availableHours} hours`; } } } } },
        });
        const colors = { Pending: '#f59e0b', Approved: '#10b981', Rejected: '#f43f5e', Cancelled: '#64748b' };
        replace(canvas('OutcomesChart'), {
            type: 'bar', data: { labels: data.outcomes.labels, datasets: Object.entries(data.outcomes.series).map(([status, values]) => ({ label: status, data: values, backgroundColor: colors[status], borderRadius: 4 })) },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true, grid: { display: false }, ticks: { color: text } }, y: { stacked: true, beginAtZero: true, grid: { color: grid }, ticks: { color: text, precision: 0 } } }, plugins: { legend: { labels: { color: text, usePointStyle: true } } } },
        });
        replace(canvas('ReviewTrendChart'), {
            type: 'line', data: { labels: data.reviews.map(x => x.label), datasets: [{ label: 'Average review hours', data: data.reviews.map(x => x.hours), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .3, spanGaps: true }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false }, ticks: { color: text } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: text, callback: value => value + 'h' } } }, plugins: { legend: { labels: { color: text } } } },
        });
        replace(canvas('CapacityUtilizationChart'), { type: 'bar', data: { labels: data.capacity.map(x => x.facility), datasets: [{ data: data.capacity.map(x => x.rate), backgroundColor: '#2563eb', borderRadius: 7 }] }, options: percentOptions });
        replace(canvas('CancellationRateChart'), { type: 'bar', data: { labels: data.cancellations.map(x => x.facility), datasets: [{ data: data.cancellations.map(x => x.rate), backgroundColor: '#f59e0b', borderRadius: 7 }] }, options: percentOptions });
        replace(canvas('DecisionRateChart'), {
            type: 'bar', data: { labels: data.decisions.map(x => x.facility), datasets: [{ label: 'Approved', data: data.decisions.map(x => x.approved), backgroundColor: '#10b981' }, { label: 'Rejected', data: data.decisions.map(x => x.rejected), backgroundColor: '#f43f5e' }] },
            options: { ...percentOptions, scales: { x: { stacked: true, beginAtZero: true, max: 100, grid: { color: grid }, ticks: { color: text, callback: value => value + '%' } }, y: { stacked: true, grid: { display: false }, ticks: { color: text } } }, plugins: { legend: { labels: { color: text, usePointStyle: true } } } },
        });
        replace(canvas('FacilityRatingChart'), {
            type: 'bar',
            data: { labels: data.ratings.map(x => x.facility), datasets: [{ data: data.ratings.map(x => x.rating), backgroundColor: '#eab308', borderRadius: 7 }] },
            options: { ...percentOptions, scales: { x: { beginAtZero: true, max: 5, grid: { color: grid }, ticks: { color: text, stepSize: 1, callback: value => value + ' ★' } }, y: { grid: { display: false }, ticks: { color: text } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => { const x = data.ratings[c.dataIndex]; return `${x.rating} / 5 from ${x.count} rating${x.count === 1 ? '' : 's'}`; } } } } },
        });
        replace(canvas('AmenityDemandChart'), {
            type: 'bar',
            data: { labels: data.amenities.map(x => x.amenity), datasets: [{ label: 'Requests', data: data.amenities.map(x => x.count), backgroundColor: '#7c3aed', borderRadius: 7 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { color: text, precision: 0, stepSize: 1 }, title: { display: true, text: 'Number of requests', color: text } }, y: { grid: { display: false }, ticks: { color: text, autoSkip: false } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => `${c.raw} request${c.raw === 1 ? '' : 's'}` } } } },
        });
    };
    document.addEventListener('DOMContentLoaded', init, { once: true });
    document.addEventListener('livewire:navigated', init);
    if (document.readyState !== 'loading') init();
})();
</script>
@endpush
