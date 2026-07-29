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
                class="admin-calendar-control {{ $view === 'weekly' ? 'is-active' : '' }}"
                x-on:click="switchView('timeGridWeek'); $wire.setView('weekly')"
            >
                Week view
            </button>
            <button
                type="button"
                class="admin-calendar-control {{ $view === 'monthly' ? 'is-active' : '' }}"
                x-on:click="switchView('dayGridMonth'); $wire.setView('monthly')"
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
            Click a date to add a schedule. Click an existing event to review or update it.
        </p>
    </aside>

    <section class="min-w-0 max-w-full overflow-hidden p-3 sm:p-5">
        <div
            wire:ignore
            id="fc-calendar"
            class="schedule-calendar"
            style="min-height: 640px;"
        ></div>
    </section>
</div>
