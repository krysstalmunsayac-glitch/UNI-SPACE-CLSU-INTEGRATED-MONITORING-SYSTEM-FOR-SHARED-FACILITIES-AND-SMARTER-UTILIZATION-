@php
    $stats = $this->scheduleStats;
    $roleLabel = auth()->user()?->isAdmin() ? 'Office Admin Schedule' : 'Super Admin Schedule';
@endphp

<section class="mb-5 overflow-hidden rounded-lg border border-emerald-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/60 dark:bg-zinc-950 dark:text-emerald-300">
                    {{ $roleLabel }}
                </div>

                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm dark:bg-zinc-950 dark:text-emerald-300">
                        <x-ui::icon.calendar-days class="size-6" />
                    </span>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                            Facility Schedule
                        </h1>

                        <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                            Monitor bookings, block unavailable slots, and keep facility calendars updated.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if (auth()->user()?->isSuperAdmin())
        <form wire:submit="saveBookingRules" class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 xl:flex-row xl:items-start xl:justify-between dark:border-zinc-800">
            <div class="min-w-0 max-w-2xl">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Booking notice</h2>
                <p id="booking-notice-help" class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Choose the minimum notice for new reservations and date changes across all facilities. Use 0 for same-day bookings.</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-zinc-400">Super admins can schedule inside this period. Existing bookings stay valid.</p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-start xl:shrink-0">
                <div class="w-full sm:w-40">
                    <x-ui::input wire:model="noticeDays" type="number" min="0" max="365" step="1" label="Minimum days" aria-describedby="booking-notice-help" required />
                </div>
                <x-ui::button type="submit" variant="primary" class="w-full sm:mt-7 sm:w-auto" wire:target="saveBookingRules">Save booking rule</x-ui::button>
            </div>
        </form>
    @endif

    <div class="grid gap-3 border-b border-gray-100 px-5 py-4 sm:grid-cols-2 xl:grid-cols-5 dark:border-zinc-800">
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70">
            <p class="text-xs font-medium text-gray-500 dark:text-zinc-400">Visible schedules</p>
            <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stats['total'] }}</p>
        </div>

        <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-900/50 dark:bg-emerald-950/20">
            <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Booked</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-900 dark:text-emerald-100">{{ $stats['booked'] }}</p>
        </div>

        <div class="rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/20">
            <p class="text-xs font-medium text-amber-700 dark:text-amber-300">Upcoming</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ $stats['upcoming'] }}</p>
        </div>

        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70">
            <p class="text-xs font-medium text-slate-600 dark:text-zinc-400">Blocked</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-zinc-100">{{ $stats['blocked'] }}</p>
        </div>

        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70">
            <p class="text-xs font-medium text-gray-500 dark:text-zinc-400">Facilities</p>
            <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stats['facilities'] }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-[minmax(180px,260px)_1fr_auto] lg:max-w-3xl">
            <x-ui::select
                wire:model.live="facilityFilter"
                placeholder="All facilities"
                class="w-full"
            >
                @foreach ($this->facilitiesList as $facility)
                    <x-ui::select.option value="{{ $facility->FID }}">
                        {{ $facility->Facility_Name }}
                    </x-ui::select.option>
                @endforeach
            </x-ui::select>

            <x-ui::input
                wire:model.live.debounce.400ms="searchInput"
                placeholder="Search by facility or purpose..."
                icon="magnifying-glass"
                class="w-full"
            />

        </div>

        <div class="text-sm font-medium text-gray-500 dark:text-zinc-400">
            {{ now()->format('M d, Y') }}
        </div>
    </div>
</section>
