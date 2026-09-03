<aside class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950/20" aria-labelledby="booking-summary-heading">
    <h3 id="booking-summary-heading" class="font-bold text-emerald-950 dark:text-white">Booking summary</h3>
    <p class="mt-2 text-lg font-semibold">{{ $facility->Facility_Name }}</p>
    <div class="mt-3 space-y-2 text-sm">
        <template x-for="schedule in dailySchedules" :key="`summary-${schedule.date}`">
            <div class="flex flex-wrap justify-between gap-2 border-b border-emerald-900/10 pb-2">
                <span x-text="new Date(`${schedule.date}T12:00:00`).toLocaleDateString(undefined, {month:'long', day:'numeric', year:'numeric'})"></span>
                <span><strong x-text="`${formatTime(schedule.start)}–${formatTime(schedule.end)}`"></strong> · <span x-text="`${duration(schedule) / 60} hour${duration(schedule) === 60 ? '' : 's'}`"></span></span>
            </div>
        </template>
    </div>
    <div class="mt-3 flex items-center justify-between gap-3 text-sm font-semibold">
        <span>Status</span>
        <span class="rounded-full px-3 py-1" :class="hasBlockingConflict() ? 'bg-red-100 text-red-700' : hasPendingWarning() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700'" x-text="availabilityLoading ? 'Checking…' : hasApprovedConflict() ? 'Unavailable — Already booked' : hasClosure() ? 'Unavailable — Closed or under maintenance' : hasPendingWarning() ? 'Pending request exists' : 'Available'"></span>
    </div>
    <p x-show="hasPendingWarning()" class="mt-2 rounded-lg bg-amber-100 p-3 text-sm text-amber-900">Another request is awaiting approval for this period. You may still submit; the administrator will decide which request to approve.</p>
    <p class="mt-2 text-xs text-zinc-500">A 30-minute preparation and cleanup buffer is reserved around every booking.</p>
</aside>
