<div class="admin-booking-calendar min-w-0 max-w-full overflow-hidden border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-950">
    <aside class="admin-booking-sidebar">
        <div class="admin-booking-date-card">
            <div class="text-sm font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                {{ now()->format('D.') }}
            </div>
            <div class="mt-2 text-lg font-bold text-emerald-950 dark:text-white">{{ now()->format('F Y') }}</div>
            <div class="mt-2 text-7xl font-black leading-none text-emerald-950 dark:text-white">{{ now()->format('j') }}</div>
            <div class="mt-4 text-xs font-semibold text-emerald-900/60 dark:text-zinc-400">
                Day {{ now()->dayOfYear }}, Week {{ now()->weekOfYear }}
            </div>
        </div>

        <div class="grid gap-2">
            <button
                type="button"
                class="admin-calendar-control"
                x-bind:class="{ 'is-active': viewMode === 'weekly' }"
                x-on:click="switchView('timeGridWeek', 'weekly')"
            >
                Week view
            </button>
            <button
                type="button"
                class="admin-calendar-control"
                x-bind:class="{ 'is-active': viewMode === 'monthly' }"
                x-on:click="switchView('dayGridMonth', 'monthly')"
            >
                Month view
            </button>
            <button
                type="button"
                class="admin-calendar-control"
                x-on:click="calendar?.today(); calendar?.updateSize()"
            >
                Today
            </button>
        </div>

        <p class="mt-5 text-xs leading-5 text-emerald-900/60 dark:text-zinc-400">
            Click an existing event to review or update it.
        </p>

        <div class="mt-5 rounded-xl border border-emerald-100 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-950">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">
                Facility type colors
            </p>

            <div class="mt-3 space-y-2">
                @forelse ($this->facilityTypeLegend as $type)
                    <div class="flex items-center gap-2 text-xs font-semibold text-emerald-950 dark:text-zinc-200">
                        <span
                            class="h-3 w-3 shrink-0 rounded-full ring-2 ring-white dark:ring-zinc-950"
                            style="background-color: {{ $type['bg'] }};"
                        ></span>
                        <span class="truncate">{{ $type['label'] }}</span>
                    </div>
                @empty
                    <p class="text-xs text-emerald-900/60 dark:text-zinc-400">No facility types yet.</p>
                @endforelse
            </div>

            <div class="mt-4 space-y-2 border-t border-emerald-100 pt-3 dark:border-white/10">
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-950 dark:text-zinc-200">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-zinc-400 ring-2 ring-white dark:ring-zinc-950"></span>
                    <span>Blocked schedule</span>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-950 dark:text-zinc-200">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-red-600 ring-2 ring-white dark:ring-zinc-950"></span>
                    <span>Ended event</span>
                </div>
            </div>
        </div>
    </aside>

    <section class="min-w-0 max-w-full overflow-hidden p-3 sm:p-5">
        <div
            wire:ignore
            x-ref="calendar"
            id="fc-calendar"
            class="schedule-calendar"
            style="min-height: 640px;"
        ></div>
    </section>
</div>
