<x-layouts.app>
    @php
        $summaryCards = [
            ['label' => 'Assigned Facilities', 'value' => $facilityCount ?? 0, 'note' => 'Spaces under your office', 'tone' => 'slate'],
            ['label' => 'Total Requests', 'value' => $rangeRequests ?? 0, 'note' => 'Submitted in selected dates', 'tone' => 'slate'],
            ['label' => 'Pending', 'value' => $dashboardStatusCounts['Pending'] ?? 0, 'note' => 'Awaiting review', 'tone' => 'amber'],
            ['label' => 'Approved', 'value' => $dashboardStatusCounts['Approved'] ?? 0, 'note' => 'Approved in selected dates', 'tone' => 'emerald'],
            ['label' => 'Rejected', 'value' => $dashboardStatusCounts['Rejected'] ?? 0, 'note' => 'Rejected in selected dates', 'tone' => 'rose'],
            ['label' => 'Cancelled', 'value' => $dashboardStatusCounts['Cancelled'] ?? 0, 'note' => 'Cancelled in selected dates', 'tone' => 'amber'],
        ];
    @endphp

    <div class="admin-dashboard-canvas bg-slate-50 text-slate-950 dark:bg-zinc-950 dark:text-white">
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-5 border-b border-slate-100 p-6 lg:grid-cols-[1fr_auto] lg:items-end dark:border-zinc-800">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Office Admin Dashboard</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight">Assigned Facility Overview</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500 dark:text-zinc-400">
                            Track request flow, expected capacity, and facility activity for the spaces assigned to your office.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('Request') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950">
                            View Requests
                        </a>
                        <a href="{{ route('Facility') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:border-emerald-500 dark:hover:text-emerald-300">
                            My Facilities
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    @foreach ($summaryCards as $card)
                        <article @class([
                            'rounded-xl border p-4',
                            'border-slate-200 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950' => $card['tone'] === 'slate',
                            'border-emerald-100 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20' => $card['tone'] === 'emerald',
                            'border-amber-100 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20' => $card['tone'] === 'amber',
                            'border-rose-100 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20' => $card['tone'] === 'rose',
                        ])>
                            <p @class([
                                'text-xs font-bold uppercase tracking-wide',
                                'text-slate-500 dark:text-zinc-400' => $card['tone'] === 'slate',
                                'text-emerald-700 dark:text-emerald-300' => $card['tone'] === 'emerald',
                                'text-amber-700 dark:text-amber-300' => $card['tone'] === 'amber',
                                'text-rose-700 dark:text-rose-300' => $card['tone'] === 'rose',
                            ])>{{ $card['label'] }}</p>
                            <div class="mt-3 text-3xl font-bold">{{ $card['value'] }}</div>
                            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">{{ $card['note'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            @include('dashboards.partials.analytics-date-range')

            <section class="space-y-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight">Request Analytics</h2>
                        <p class="text-sm text-slate-500 dark:text-zinc-400">Capacity, status, and records for your assigned facilities.</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-500 dark:text-zinc-400">{{ $analyticsDateLabel }}</span>
                </div>

                @include('dashboards.partials.request-analytics', ['dashboardChartPrefix' => 'officeAdmin'])
            </section>
        </div>
    </div>
</x-layouts.app>
