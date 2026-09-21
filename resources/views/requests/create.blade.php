<x-dynamic-component :component="$guestBooking ? 'layouts.app' : 'layouts.home.header'">
    @php($assignedOfficeAdmin = $facility->assignedAdmins->first())
    <x-ui::main class="bg-emerald-50/70 px-4 py-8 dark:bg-zinc-950 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-950">
                <div class="h-1.5 bg-emerald-700"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200">
                                <span class="size-2 rounded-full bg-emerald-600"></span>
                                {{ $guestBooking ? 'Guest facility reservation' : 'Facility reservation' }}
                            </div>
                            <h1 class="text-3xl font-black tracking-tight text-emerald-950 dark:text-white sm:text-4xl">{{ $facility->Facility_Name }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-900/65 dark:text-zinc-300">
                                {{ $guestBooking
                                    ? 'Reserve this facility on behalf of an authorized guest, visitor, VIP, partner, or university official.'
                                    : 'Tell us about your event, choose a schedule, and select the amenities you need.' }}
                            </p>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-3 lg:min-w-[34rem]">
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Availability</p>
                                <p class="mt-1 font-black text-emerald-950 dark:text-white">{{ $facility->Status }}</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Capacity</p>
                                <p class="mt-1 font-black text-emerald-950 dark:text-white">{{ $facility->Capacity ? number_format($facility->Capacity).' people' : 'Not specified' }}</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Office</p>
                                <p class="mt-1 line-clamp-2 font-black text-emerald-950 dark:text-white">{{ $facility->Office ?? 'Not specified' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <div
                class="grid items-start gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(28rem,1.1fr)]"
                x-data="bookingRequestForm({
                    step: {{ $errors->hasAny(['Amenity_ID', 'Amenity_ID.*', 'Amenity_Quantity', 'Amenity_Quantity.*', 'Purpose_Categories', 'Purpose_Categories.*', 'Other_Purpose', 'Capacity', 'attachment']) ? 3 : ($errors->hasAny(['Proposed_Date', 'Proposed_End_Date', 'Daily_Schedules', 'Daily_Schedules.*']) ? 2 : 1) }},
                    selectedAmenities: @js(array_map('strval', old('Amenity_ID', []))),
                    dailySchedules: @js(old('Daily_Schedules', [])),
                    sharedStartTime: @js(data_get(old('Daily_Schedules', []), '0.start', '')),
                    sharedEndTime: @js(data_get(old('Daily_Schedules', []), '0.end', '')),
                    slots: @js($scheduling['slots']),
                    endSlots: [...@js($scheduling['slots']), @js($scheduling['closes_at'])],
                    availabilityUrl: @js($scheduling['availability_url']),
                    bookingToday: @js(today()->toDateString()),
                    bookingCurrentTime: @js(now()->format('H:i')),
                    customizeDailyTimes: @js(collect(old('Daily_Schedules', []))->map(fn ($schedule) => ($schedule['start'] ?? '').'|'.($schedule['end'] ?? ''))->unique()->count() > 1),
                    eventType: @js(old('Type_Event', '')),
                    photos: @js($facility->images->map(fn ($image) => asset('storage/'.ltrim($image->image_path, '/')))->values()),
                    activePhoto: null
                    {{-- Methods are registered in resources/js/requests/booking-form.js. --}}
                    /*
                    openPhoto(index) {
                        this.activePhoto = index;
                        document.body.classList.add('overflow-hidden');
                    },
                    closePhoto() {
                        this.activePhoto = null;
                        document.body.classList.remove('overflow-hidden');
                    },
                    previousPhoto() {
                        this.activePhoto = (this.activePhoto - 1 + this.photos.length) % this.photos.length;
                    },
                    nextPhoto() {
                        this.activePhoto = (this.activePhoto + 1) % this.photos.length;
                    },
                    syncDailySchedules() {
                        const startValue = this.$refs.startDate?.value;
                        let endValue = this.$refs.endDate?.value;
                        if (!startValue || !endValue) return;
                        if (endValue < startValue) {
                            endValue = startValue;
                            this.$refs.endDate.value = startValue;
                        }

                        const previous = new Map(this.dailySchedules.map(schedule => [schedule.date, schedule]));
                        const current = new Date(`${startValue}T12:00:00`);
                        const last = new Date(`${endValue}T12:00:00`);
                        const schedules = [];
                        const formatDate = date => {
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            return `${year}-${month}-${day}`;
                        };

                        while (current <= last && schedules.length < 31) {
                            const date = formatDate(current);
                            schedules.push(previous.get(date) ?? { date, start: this.sharedStartTime, end: this.sharedEndTime });
                            current.setDate(current.getDate() + 1);
                        }

                        this.dailySchedules = schedules;
                        if (!this.customizeDailyTimes) this.applySharedTime();
                        this.loadAvailability();
                    },
                    applySharedTime() {
                        this.dailySchedules = this.dailySchedules.map(schedule => ({
                            ...schedule,
                            start: this.sharedStartTime,
                            end: this.sharedEndTime,
                        }));
                        if (this.dailySchedules.length && this.dailySchedules.every(schedule => schedule.start && schedule.end)) {
                            this.scheduleValidationError = '';
                        }
                    },
                    minimumEndTime(startTime) {
                        if (!startTime) return null;
                        const [hours, minutes] = startTime.split(':').map(Number);
                        const minimumMinutes = (hours * 60) + minutes + 60;
                        if (minimumMinutes >= 1440) return '24:00';
                        return `${String(Math.floor(minimumMinutes / 60)).padStart(2, '0')}:${String(minimumMinutes % 60).padStart(2, '0')}`;
                    },
                    addMinutes(time, minutes) {
                        const [hours, mins] = time.split(':').map(Number);
                        const total = Math.min((hours * 60) + mins + minutes, 24 * 60);
                        return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
                    },
                    chooseSharedStart(time) {
                        this.sharedStartTime = time;
                        this.sharedEndTime = this.addMinutes(time, 60);
                        this.applySharedTime();
                    },
                    chooseDayStart(schedule, time) {
                        schedule.start = time;
                        schedule.end = this.addMinutes(time, 60);
                        if (this.dailySchedules.every(item => item.start && item.end)) this.scheduleValidationError = '';
                    },
                    async loadAvailability() {
                        const from = this.$refs.startDate?.value;
                        const to = this.$refs.endDate?.value;
                        if (!from || !to) return;
                        this.availabilityLoading = true;
                        this.availabilityError = '';
                        try {
                            const url = new URL(@js($scheduling['availability_url']), window.location.origin);
                            url.searchParams.set('from', from);
                            url.searchParams.set('to', to);
                            const response = await fetch(url, { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error('Availability could not be loaded.');
                            this.availability = (await response.json()).days;
                        } catch (error) {
                            this.availability = {};
                            this.availabilityError = error.message;
                        } finally { this.availabilityLoading = false; }
                    },
                    slotStatus(date, start, end) {
                        if (date < this.bookingToday || (date === this.bookingToday && start <= this.bookingCurrentTime)) return 'past';
                        const day = this.availability[date];
                        if (!day || day.closed) return 'unavailable';
                        let status = 'available';
                        for (const range of day.ranges || []) {
                            if (start < range.blocked_end && end > range.blocked_start) {
                                if (range.status === 'approved') return 'approved';
                                status = 'pending';
                            }
                        }
                        return status;
                    },
                    scheduleStatus(schedule) {
                        if (!schedule?.start || !schedule?.end) return 'incomplete';
                        return this.slotStatus(schedule.date, schedule.start, schedule.end);
                    },
                    hasApprovedConflict() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'approved'); },
                    hasPendingWarning() { return !this.hasApprovedConflict() && this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'pending'); },
                    hasClosure() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'unavailable'); },
                    hasPastTime() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'past'); },
                    hasBlockingConflict() { return this.hasApprovedConflict() || this.hasClosure() || this.hasPastTime(); },
                    sharedStartDisabled(slot) { return this.dailySchedules.some(schedule => ['approved', 'unavailable', 'past'].includes(this.slotStatus(schedule.date, slot, this.addMinutes(slot, 60)))); },
                    sharedEndDisabled(slot) { return this.dailySchedules.some(schedule => ['approved', 'unavailable', 'past'].includes(this.slotStatus(schedule.date, this.sharedStartTime, slot))); },
                    slotLabel(status) {
                        return status === 'approved' ? ' — Already Booked'
                            : status === 'past' ? ' — Time Elapsed'
                            : status === 'unavailable' ? ' — Unavailable'
                            : '';
                    },
                    duration(schedule) {
                        if (!schedule?.start || !schedule?.end) return 0;
                        const parts = value => value.split(':').map(Number);
                        const [sh, sm] = parts(schedule.start); const [eh, em] = parts(schedule.end);
                        return ((eh * 60) + em) - ((sh * 60) + sm);
                    },
                    formatTime(time) {
                        if (time === '24:00') return '12:00 AM';
                        return new Date(`2000-01-01T${time}:00`).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    },
                    useOneTimeForAllDays() {
                        this.customizeDailyTimes = false;
                        this.applySharedTime();
                    }
                    */
                })"
                x-init="$nextTick(() => syncDailySchedules())"
                x-on:keydown.escape.window="if (activePhoto !== null) closePhoto()"
                x-on:keydown.left.window="if (activePhoto !== null && photos.length > 1) previousPhoto()"
                x-on:keydown.right.window="if (activePhoto !== null && photos.length > 1) nextPhoto()"
            >
                <section id="facility-overview" aria-labelledby="facility-gallery-heading" class="order-2 scroll-mt-24 rounded-3xl border border-emerald-900/10 bg-white p-5 shadow-lg shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-950 sm:p-6 lg:order-1">
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Facility overview</p>
                            <h2 id="facility-gallery-heading" class="mt-1 text-2xl font-black text-emerald-950 dark:text-white">
                                Explore this space
                            </h2>
                        </div>
                        @if ($facility->images->isNotEmpty())
                            <p class="text-sm text-emerald-900/60 dark:text-zinc-400">
                                {{ $facility->images->count() }} {{ Str::plural('photo', $facility->images->count()) }}
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @forelse ($facility->images as $image)
                            <button
                                type="button"
                                @class([
                                    'group relative aspect-[16/10] overflow-hidden rounded-2xl bg-emerald-50 text-left ring-1 ring-emerald-900/10 transition hover:-translate-y-0.5 hover:shadow-xl hover:ring-2 hover:ring-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:bg-zinc-900 dark:ring-white/10',
                                    'sm:col-span-2' => $facility->images->count() === 1,
                                ])
                                x-on:click="openPhoto({{ $loop->index }})"
                                aria-label="Expand facility photo {{ $loop->iteration }}"
                            >
                                <img
                                    src="{{ asset('storage/'.ltrim($image->image_path, '/')) }}"
                                    alt="{{ $facility->Facility_Name }} facility photo {{ $loop->iteration }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                >
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-emerald-950/70 px-4 pb-3 pt-8">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-white">Photo {{ $loop->iteration }}</span>
                                        <span class="rounded-full bg-black/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur">Expand</span>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-2 flex min-h-52 flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-300 bg-emerald-50 p-8 text-center dark:border-emerald-700 dark:bg-zinc-900">
                                <div class="mb-4 flex size-20 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-emerald-900/10 dark:bg-zinc-950 dark:ring-white/10">
                                    <img src="{{ $facility->primaryImageUrl() }}" alt="" class="h-14 w-14 object-contain opacity-80">
                                </div>
                                <p class="font-semibold text-emerald-950 dark:text-white">No facility photos yet</p>
                                <p class="mt-1 text-sm text-emerald-900/60 dark:text-zinc-400">Photos of this facility will appear here when available.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($facility->Description)
                        <div class="mt-5 border-t border-emerald-900/10 pt-5 dark:border-white/10">
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-300">About the facility</p>
                            <p class="mt-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">{{ $facility->Description }}</p>
                        </div>
                    @endif

                    @if ($assignedOfficeAdmin)
                        <div class="mt-5 overflow-hidden rounded-2xl border border-emerald-200 bg-white dark:border-emerald-500/25 dark:bg-zinc-900">
                            <div class="flex items-center gap-4 p-4">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white shadow-sm" aria-hidden="true">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="5" width="18" height="14" rx="2" />
                                        <path d="m3 7 9 6 9-6" />
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Facility contact</p>
                                    <p class="mt-1 truncate font-bold text-emerald-950 dark:text-white">{{ $assignedOfficeAdmin->name }}</p>
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Assigned office administrator</p>
                                </div>
                            </div>
                            <a href="mailto:{{ $assignedOfficeAdmin->email }}" class="flex items-center justify-between gap-3 border-t border-emerald-100 bg-emerald-50/70 px-4 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-950/20 dark:text-emerald-200 dark:hover:bg-emerald-950/40">
                                <span class="min-w-0 break-all">{{ $assignedOfficeAdmin->email }}</span>
                                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                            </a>
                        </div>
                    @endif

                    @if ($facility->rates || $facility->protocols_and_guidelines)
                        <div class="mt-5 grid gap-4 border-t border-emerald-900/10 pt-5 dark:border-white/10 sm:grid-cols-2">
                            @if ($facility->rates)
                                <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/30">
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Rates</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-emerald-900/75 dark:text-zinc-300">{{ $facility->rates }}</p>
                                </div>
                            @endif

                            @if ($facility->protocols_and_guidelines)
                                <div class="rounded-2xl bg-yellow-50 p-4 dark:bg-yellow-400/10">
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Protocols and Guidelines</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-emerald-900/75 dark:text-zinc-300">{{ $facility->protocols_and_guidelines }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </section>

                <template x-teleport="body">
                    <div
                        x-cloak
                        x-show="activePhoto !== null"
                        x-transition.opacity
                        class="fixed inset-x-0 bottom-0 top-16 z-[100] flex items-center justify-center overflow-hidden bg-emerald-950/95 p-3 backdrop-blur-sm sm:top-20 sm:p-5"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Facility photo viewer"
                        x-on:click.self="closePhoto()"
                    >
                        <button
                            type="button"
                            class="absolute right-3 top-3 z-10 inline-flex size-10 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-yellow-300 sm:right-5 sm:top-5"
                            x-on:click="closePhoto()"
                            aria-label="Close photo viewer"
                        >
                            &times;
                        </button>

                        <button
                            x-show="photos.length > 1"
                            type="button"
                            class="absolute left-2 z-10 inline-flex size-10 items-center justify-center rounded-full bg-black/40 text-3xl text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-yellow-300 sm:left-5"
                            x-on:click="previousPhoto()"
                            aria-label="Previous photo"
                        >
                            &#8249;
                        </button>

                        <div class="flex h-full max-h-full w-full max-w-6xl flex-col items-center justify-center px-10 sm:px-14">
                            <img
                                x-bind:src="activePhoto !== null ? photos[activePhoto] : ''"
                                x-bind:alt="activePhoto !== null ? '{{ addslashes($facility->Facility_Name) }} facility photo ' + (activePhoto + 1) : ''"
                                class="min-h-0 max-h-[calc(100dvh-10rem)] max-w-full flex-1 rounded-2xl object-contain shadow-2xl sm:max-h-[calc(100dvh-12rem)]"
                            >
                            <p class="mt-3 shrink-0 rounded-full bg-black/30 px-4 py-1.5 text-sm font-semibold text-white" x-text="activePhoto !== null ? (activePhoto + 1) + ' / ' + photos.length : ''"></p>
                        </div>

                        <button
                            x-show="photos.length > 1"
                            type="button"
                            class="absolute right-2 z-10 inline-flex size-10 items-center justify-center rounded-full bg-black/40 text-3xl text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-yellow-300 sm:right-5"
                            x-on:click="nextPhoto()"
                            aria-label="Next photo"
                        >
                            &#8250;
                        </button>
                    </div>
                </template>

                <div class="order-1 space-y-6 lg:order-2 lg:sticky lg:top-24">
                    <x-ui::card class="overflow-hidden rounded-3xl border-emerald-900/10 p-0 shadow-xl shadow-emerald-950/10 dark:border-white/10">
                        <div class="border-b border-emerald-900/10 bg-emerald-50/60 px-6 py-5 dark:border-white/10 dark:bg-emerald-950/20">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                                    :class="step === 1 ? 'bg-emerald-700 text-white' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'"
                                >1</span>
                                <span class="text-sm font-semibold" :class="step === 1 ? 'text-emerald-950 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">Event</span>
                            </div>
                            <div class="h-px flex-1 bg-emerald-900/10 dark:bg-white/10"></div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                                    :class="step === 2 ? 'bg-emerald-700 text-white' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'"
                                >2</span>
                                <span class="text-sm font-semibold" :class="step === 2 ? 'text-emerald-950 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">Schedule</span>
                            </div>
                            <div class="h-px flex-1 bg-emerald-900/10 dark:bg-white/10"></div>
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold" :class="step === 3 ? 'bg-emerald-700 text-white' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'">3</span>
                                <span class="text-sm font-semibold" :class="step === 3 ? 'text-emerald-950 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">Request details</span>
                            </div>
                        </div>
                        </div>

                        <div class="p-6 sm:p-8">

                        @error('submission')
                            <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 dark:border-red-500/30 dark:bg-red-950/30 dark:text-red-200">
                                {{ $message }}
                            </div>
                        @enderror

                        <form
                            x-ref="requestForm"
                            action="{{ $guestBooking ? route('admin.requests.store', $facility) : route('requests.store', $facility) }}"
                            method="POST"
                            enctype="multipart/form-data"
                            x-on:submit="submitting = true"
                            class="space-y-4"
                        >
                            @csrf
                            <input type="hidden" name="_step" x-bind:value="step">

                            @include('requests.steps.event-details')

                            {{-- STEPS 2–3: Schedule and request details --}}
                            <div x-show="step >= 2" x-cloak class="space-y-4">
                                <div x-show="step === 2">
                                    <x-ui::heading size="lg">Choose your schedule</x-ui::heading>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Select the event date and the time you want to use the facility.</p>
                                </div>
                                <div x-show="step === 3">
                                    <x-ui::heading size="lg">Amenity & request details</x-ui::heading>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Choose any extras you need, then complete the final details before sending your request.</p>
                                </div>

                                @if ($errors->hasAny(['Proposed_Date', 'Proposed_End_Date', 'Daily_Schedules', 'Daily_Schedules.*']))
                                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-950/30 dark:text-red-200">
                                        <p class="font-bold">Please choose a valid future schedule.</p>
                                        <ul class="mt-2 list-disc space-y-1 pl-5">
                                            @foreach ($errors->all() as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                        @error('Proposed_Date')
                                            @unless ($guestBooking)
                                                <a href="{{ route('dashboard') }}#requests" class="mt-3 inline-flex font-bold underline underline-offset-2">Review or cancel the existing request</a>
                                            @endunless
                                        @enderror
                                    </div>
                                @endif

                                @include('requests.steps.amenities')

                                @include('requests.steps.schedule')

                                @include('requests.steps.review')

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-emerald-900/10 px-4 py-2 text-sm font-medium text-emerald-900 transition hover:border-emerald-700 hover:bg-emerald-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        x-on:click="step = step === 2 ? 1 : 2; window.scrollTo({ top: 0, behavior: 'smooth' })"
                                        x-text="step === 2 ? 'Back to event' : 'Back to schedule'"
                                    >
                                        Back
                                    </button>
                                    <button
                                        x-show="step === 2"
                                        type="button"
                                        class="inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white transition hover:bg-emerald-800 sm:w-auto"
                                        x-on:click="
                                            const invalidField = Array.from($refs.scheduleDetails.querySelectorAll(`input[type='date']`))
                                                .find(field => !field.checkValidity());
                                            if (invalidField) {
                                                invalidField.reportValidity();
                                            } else if (dailySchedules.some(schedule => !schedule.start || !schedule.end)) {
                                                scheduleValidationError = dailySchedules.length > 1
                                                    ? 'Select a start and end time for every event day before continuing.'
                                                    : 'Select a start time and an end time before continuing.';
                                                $nextTick(() => $refs.eventTimeSection?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                                            } else if (hasBlockingConflict()) {
                                                scheduleValidationError = 'The selected schedule is unavailable. Choose an available date and time before continuing.';
                                                $nextTick(() => $refs.eventTimeSection?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                                            } else {
                                                scheduleValidationError = '';
                                                step = 3;
                                                window.scrollTo({ top: 0, behavior: 'smooth' });
                                            }
                                        "
                                    >
                                        Continue to request details
                                    </button>
                                    <button
                                        x-show="step === 3"
                                        type="submit"
                                        class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:pointer-events-none disabled:opacity-60 sm:w-auto"
                                        data-ui-confirm="Are you sure you want to submit this reservation request? Please review the selected facility, date, time, and amenities before continuing."
                                        data-ui-confirm-title="Confirm request submission"
                                        data-ui-confirm-label="Submit request"
                                        x-bind:disabled="submitting || availabilityLoading || availabilityError || hasBlockingConflict()"
                                        x-bind:aria-busy="submitting"
                                    >
                                        <span x-show="!submitting">{{ $guestBooking ? 'Submit guest request' : 'Send request' }}</span>
                                        <span x-cloak x-show="submitting">Submitting…</span>
                                    </button>
                                    <a x-show="step === 3" href="{{ $guestBooking ? (auth()->user()->isSuperAdmin() ? route('facilities.super-admin.index') : route('facilities.office-admin.index')) : route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-900/10 px-4 py-2 text-sm font-medium text-emerald-900 transition hover:border-emerald-700 hover:bg-emerald-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ $guestBooking ? 'Back to facilities' : 'Back to home' }}</a>
                                </div>
                            </div>
                        </form>
                        </div>
                    </x-ui::card>
                </div>
            </div>
        </div>
    </x-ui::main>
</x-dynamic-component>
