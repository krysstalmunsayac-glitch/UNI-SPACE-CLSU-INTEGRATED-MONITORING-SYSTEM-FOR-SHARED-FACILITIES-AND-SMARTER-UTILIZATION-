<x-layouts.app>
    @php
        $totalUsers = $totalUsers ?? 0;
        $totalRequests = $totalRequests ?? 0;
        $pendingRequests = $pendingRequests ?? 0;
        $approvedRequests = $approvedRequests ?? 0;
        $recentRequests = $recentRequests ?? collect();

        $summaryCards = [
            ['label' => 'Total Requests', 'value' => $totalRequests, 'note' => 'Submitted in selected dates', 'tone' => 'cyan'],
            ['label' => 'Pending', 'value' => $pendingRequests, 'note' => 'Awaiting review', 'tone' => 'amber'],
            ['label' => 'Approved', 'value' => $approvedRequests, 'note' => 'Approved in selected dates', 'tone' => 'emerald'],
            ['label' => 'Rejected', 'value' => $dashboardStatusCounts['Rejected'] ?? 0, 'note' => 'Rejected in selected dates', 'tone' => 'rose'],
            ['label' => 'Cancelled', 'value' => $dashboardStatusCounts['Cancelled'] ?? 0, 'note' => 'Cancelled in selected dates', 'tone' => 'orange'],
            ['label' => 'Total Users', 'value' => $totalUsers, 'note' => 'Registered user accounts', 'tone' => 'plain'],
            ['label' => 'Total Facilities', 'value' => $facilityCount ?? 0, 'note' => 'Managed shared spaces', 'tone' => 'plain'],
            ['label' => 'Facility Utilization', 'value' => ($overallFacilityUtilizationRate ?? 0).'%', 'note' => $availabilityBaseline ?? '', 'tone' => 'teal'],
            ['label' => 'Approval Rate', 'value' => isset($approvalRate) ? $approvalRate.'%' : '—', 'note' => 'Approved vs rejected', 'tone' => 'green'],
            ['label' => 'Response Rate', 'value' => ($responseRateTotalCount ?? 0) > 0 ? number_format($responseRate ?? 0, 1).'%' : '0%', 'note' => ($respondedRequestCount ?? 0).' of '.($responseRateTotalCount ?? 0).' requests processed.', 'tone' => 'green'],
            ['label' => 'Most Used Facility', 'value' => $mostUsedFacility['name'] ?? 'N/A', 'note' => ($mostUsedFacility['count'] ?? 0).' requests in selected dates', 'tone' => 'indigo', 'valueClass' => 'text-xl leading-tight'],
        ];
    @endphp

    <div class="admin-dashboard-canvas bg-slate-50 text-slate-950 dark:bg-zinc-950 dark:text-white">
        <div class="mx-auto w-full max-w-[1600px] space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-4 border-b border-slate-100 p-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center dark:border-zinc-800">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Super Admin Dashboard</p>
                        <h1 class="mt-1 text-3xl font-bold tracking-tight">System Overview</h1>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-zinc-400">
                            Review platform activity, user growth, facility demand, and request outcomes from one workspace.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <a href="{{ route('Request') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950">
                            View Requests
                        </a>
                        <a href="{{ route('Facility') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:border-emerald-500 dark:hover:text-emerald-300">
                            Manage Facilities
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    @foreach ($summaryCards as $card)
                        <article @class([
                            'flex min-h-32 flex-col rounded-xl border p-4',
                            'border-slate-200 bg-white text-slate-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white' => $card['tone'] === 'plain',
                            'border-slate-200 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950' => $card['tone'] === 'slate',
                            'border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-950/25' => $card['tone'] === 'blue',
                            'border-violet-200 bg-violet-50 dark:border-violet-900/50 dark:bg-violet-950/25' => $card['tone'] === 'violet',
                            'border-cyan-200 bg-cyan-50 dark:border-cyan-900/50 dark:bg-cyan-950/25' => $card['tone'] === 'cyan',
                            'border-emerald-100 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20' => $card['tone'] === 'emerald',
                            'border-rose-100 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20' => $card['tone'] === 'rose',
                            'border-amber-100 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20' => $card['tone'] === 'amber',
                            'border-orange-200 bg-orange-50 dark:border-orange-900/50 dark:bg-orange-950/25' => $card['tone'] === 'orange',
                            'border-teal-200 bg-teal-50 dark:border-teal-900/50 dark:bg-teal-950/25' => $card['tone'] === 'teal',
                            'border-green-200 bg-green-50 dark:border-green-900/50 dark:bg-green-950/25' => $card['tone'] === 'green',
                            'border-indigo-200 bg-indigo-50 dark:border-indigo-900/50 dark:bg-indigo-950/25' => $card['tone'] === 'indigo',
                        ])>
                            <p @class([
                                'text-xs font-bold uppercase tracking-wide',
                                'text-slate-950 dark:text-white' => $card['tone'] === 'plain',
                                'text-slate-500 dark:text-zinc-400' => $card['tone'] === 'slate',
                                'text-blue-700 dark:text-blue-300' => $card['tone'] === 'blue',
                                'text-violet-700 dark:text-violet-300' => $card['tone'] === 'violet',
                                'text-cyan-700 dark:text-cyan-300' => $card['tone'] === 'cyan',
                                'text-emerald-700 dark:text-emerald-300' => $card['tone'] === 'emerald',
                                'text-rose-700 dark:text-rose-300' => $card['tone'] === 'rose',
                                'text-amber-700 dark:text-amber-300' => $card['tone'] === 'amber',
                                'text-orange-700 dark:text-orange-300' => $card['tone'] === 'orange',
                                'text-teal-700 dark:text-teal-300' => $card['tone'] === 'teal',
                                'text-green-700 dark:text-green-300' => $card['tone'] === 'green',
                                'text-indigo-700 dark:text-indigo-300' => $card['tone'] === 'indigo',
                            ])>{{ $card['label'] }}</p>
                            <div class="mt-2 font-bold {{ $card['valueClass'] ?? 'text-3xl leading-none' }}">{{ $card['value'] }}</div>
                            <p @class([
                                'mt-auto pt-2 text-sm leading-snug',
                                'text-slate-950 dark:text-white' => $card['tone'] === 'plain',
                                'text-slate-500 dark:text-zinc-400' => $card['tone'] !== 'plain',
                            ])>{{ $card['note'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            @include('dashboards.partials.analytics-date-range')

            <section class="space-y-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight">Request Analytics</h2>
                        <p class="text-sm text-slate-500 dark:text-zinc-400">Capacity, status, and record activity across all facilities.</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-500 dark:text-zinc-400">{{ $analyticsDateLabel }}</span>
                </div>

                @include('dashboards.partials.request-analytics', ['dashboardChartPrefix' => 'superAdmin'])
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold">Recent Requests</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Latest incoming facility request records.</p>
                    </div>
                    <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $recentRequests->count() }} shown
                    </span>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    @forelse ($recentRequests as $request)
                        <article class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">#{{ $request->RID ?? 'N/A' }} {{ $request->requesterName() }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500 dark:text-zinc-400">{{ $request->facility?->Facility_Name ?? 'No facility selected' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 dark:bg-zinc-900 dark:text-zinc-300">
                                {{ $request->Status ?? 'Unknown' }}
                            </span>
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                            No recent requests are available yet.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
