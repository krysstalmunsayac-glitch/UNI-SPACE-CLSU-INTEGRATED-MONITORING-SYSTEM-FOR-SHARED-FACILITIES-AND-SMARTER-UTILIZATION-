<x-layouts.home.header>
    <x-ui::main class="bg-gradient-to-b from-emerald-50/70 via-white to-white px-4 py-8 dark:from-emerald-950/20 dark:via-zinc-950 dark:to-zinc-950 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-950">
                <div class="h-1.5 bg-gradient-to-r from-emerald-700 via-emerald-500 to-yellow-400"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200">
                                <span class="size-2 rounded-full bg-emerald-600"></span>
                                Facility reservation
                            </div>
                            <h1 class="text-3xl font-black tracking-tight text-emerald-950 dark:text-white sm:text-4xl">{{ $facility->Facility_Name }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-900/65 dark:text-zinc-300">Tell us about your event, choose a schedule, and select the amenities you need.</p>
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
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Location</p>
                                <p class="mt-1 line-clamp-2 font-black text-emerald-950 dark:text-white">{{ $facility->Location ?? 'Not specified' }}</p>
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
                x-data="{
                    step: {{ $errors->hasAny(['Amenity_ID', 'Amenity_ID.*', 'Proposed_Date', 'Proposed_End_Date', 'Daily_Schedules', 'Daily_Schedules.*', 'Purpose_Categories', 'Purpose_Categories.*', 'Other_Purpose', 'Reservation_Frequency', 'Facility_Importance', 'Requirements_Fit', 'Reserve_Again_Intent', 'Capacity', 'attachment']) ? 2 : 1 }},
                    submitting: false,
                    dailySchedules: @js(old('Daily_Schedules', [])),
                    sharedStartTime: @js(data_get(old('Daily_Schedules', []), '0.start', '09:00')),
                    sharedEndTime: @js(data_get(old('Daily_Schedules', []), '0.end', '10:00')),
                    slots: @js($scheduling['slots']),
                    endSlots: [...@js($scheduling['slots']), @js($scheduling['closes_at'])],
                    availability: {},
                    availabilityLoading: false,
                    availabilityError: '',
                    customizeDailyTimes: @js(collect(old('Daily_Schedules', []))->map(fn ($schedule) => ($schedule['start'] ?? '').'|'.($schedule['end'] ?? ''))->unique()->count() > 1),
                    eventType: @js(old('Type_Event', '')),
                    photos: @js($facility->images->map(fn ($image) => asset('storage/'.ltrim($image->image_path, '/')))->values()),
                    activePhoto: null,
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
                    },
                    minimumEndTime(startTime) {
                        if (!startTime) return null;
                        const [hours, minutes] = startTime.split(':').map(Number);
                        const minimumMinutes = (hours * 60) + minutes + 60;
                        if (minimumMinutes >= 1440) return '23:59';
                        return `${String(Math.floor(minimumMinutes / 60)).padStart(2, '0')}:${String(minimumMinutes % 60).padStart(2, '0')}`;
                    },
                    addMinutes(time, minutes) {
                        const [hours, mins] = time.split(':').map(Number);
                        const total = Math.min((hours * 60) + mins + minutes, 20 * 60);
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
                    scheduleStatus(schedule) { return this.slotStatus(schedule.date, schedule.start, schedule.end); },
                    hasApprovedConflict() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'approved'); },
                    hasPendingWarning() { return !this.hasApprovedConflict() && this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'pending'); },
                    hasClosure() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'unavailable'); },
                    hasBlockingConflict() { return this.hasApprovedConflict() || this.hasClosure(); },
                    sharedStartDisabled(slot) { return this.dailySchedules.some(schedule => ['approved', 'unavailable'].includes(this.slotStatus(schedule.date, slot, this.addMinutes(slot, 60)))); },
                    sharedEndDisabled(slot) { return this.dailySchedules.some(schedule => ['approved', 'unavailable'].includes(this.slotStatus(schedule.date, this.sharedStartTime, slot))); },
                    duration(schedule) {
                        if (!schedule?.start || !schedule?.end) return 0;
                        const parts = value => value.split(':').map(Number);
                        const [sh, sm] = parts(schedule.start); const [eh, em] = parts(schedule.end);
                        return ((eh * 60) + em) - ((sh * 60) + sm);
                    },
                    formatTime(time) { return new Date(`2000-01-01T${time}:00`).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); },
                    useOneTimeForAllDays() {
                        this.customizeDailyTimes = false;
                        this.applySharedTime();
                    }
                }"
                x-init="$nextTick(() => syncDailySchedules())"
                x-on:keydown.escape.window="if (activePhoto !== null) closePhoto()"
                x-on:keydown.left.window="if (activePhoto !== null && photos.length > 1) previousPhoto()"
                x-on:keydown.right.window="if (activePhoto !== null && photos.length > 1) nextPhoto()"
            >
                <section aria-labelledby="facility-gallery-heading" class="order-2 rounded-3xl border border-emerald-900/10 bg-white p-5 shadow-lg shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-950 sm:p-6 lg:order-1">
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
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-emerald-950/55 to-transparent px-4 pb-3 pt-8">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-white">Photo {{ $loop->iteration }}</span>
                                        <span class="rounded-full bg-black/35 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur">Expand</span>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-2 flex min-h-52 flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-300 bg-gradient-to-br from-emerald-50 to-white p-8 text-center dark:border-emerald-700 dark:from-emerald-950/30 dark:to-zinc-900">
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
                                <span class="text-sm font-semibold" :class="step === 2 ? 'text-emerald-950 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">Schedule & amenities</span>
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
                            action="{{ route('requests.store', $facility) }}"
                            method="POST"
                            enctype="multipart/form-data"
                            x-on:submit="submitting = true"
                            class="space-y-4"
                        >
                            @csrf
                            <input type="hidden" name="_step" value="2">

                            {{-- STEP 1: Event details --}}
                            <div x-show="step === 1" x-cloak class="space-y-4">
                                <x-ui::heading size="lg">Event details</x-ui::heading>
                                <p class="text-sm text-emerald-900/70 dark:text-zinc-300">Tell us about the event this request is for.</p>

                                <div>
                                    <x-ui::input
                                        label="Event title"
                                        name="Event_Title"
                                        value="{{ old('Event_Title') }}"
                                        required
                                        minlength="3"
                                        maxlength="255"
                                    />
                                    @error('Event_Title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <x-ui::textarea
                                        label="Description"
                                        name="Description"
                                        rows="4"
                                        required
                                        minlength="5"
                                        maxlength="2000"
                                    >{{ old('Description') }}</x-ui::textarea>
                                    @error('Description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-ui::select label="Event type" name="Type_Event" x-model="eventType" required>
                                            <x-ui::select.option value="">Select a type</x-ui::select.option>
                                            {{-- Adjust these to match your Type_Event enum/values --}}
                                            <x-ui::select.option value="Meeting" :selected="old('Type_Event') == 'Meeting'">Meeting</x-ui::select.option>
                                            <x-ui::select.option value="Seminar" :selected="old('Type_Event') == 'Seminar'">Seminar</x-ui::select.option>
                                            <x-ui::select.option value="Workshop" :selected="old('Type_Event') == 'Workshop'">Workshop</x-ui::select.option>
                                            <x-ui::select.option value="Conference" :selected="old('Type_Event') == 'Conference'">Conference</x-ui::select.option>
                                            <x-ui::select.option value="Other" :selected="old('Type_Event') == 'Other'">Other</x-ui::select.option>
                                        </x-ui::select>
                                        @error('Type_Event') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div x-cloak x-show="eventType === 'Other'" x-transition>
                                        <x-ui::input
                                            label="Specify event type"
                                            name="Other_Event_Type"
                                            value="{{ old('Other_Event_Type') }}"
                                            placeholder="e.g. Recognition ceremony"
                                            maxlength="100"
                                            x-bind:required="eventType === 'Other'"
                                        />
                                        @error('Other_Event_Type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800"
                                        x-on:click="
                                            if ($refs.requestForm.reportValidity()) {
                                                step = 2;
                                                window.scrollTo({ top: 0, behavior: 'smooth' });
                                            }
                                        "
                                    >
                                        Continue to schedule
                                    </button>
                                </div>
                            </div>

                            {{-- STEP 2: Facility request details --}}
                            <div x-show="step === 2" x-cloak class="space-y-4">
                                <x-ui::heading size="lg">Schedule & amenities</x-ui::heading>

                                <div>
                                    <x-ui::checkbox.group label="Amenities">
                                        @forelse ($availableAmenities as $amenity)
                                            <x-ui::checkbox
                                                name="Amenity_ID[]"
                                                value="{{ $amenity->AID }}"
                                                label="{{ $amenity->name }}{{ $amenity->reservation_limit ? ' (limit: '.$amenity->reservation_limit.' concurrent reservations)' : ' (unlimited)' }}"
                                                :checked="in_array(
                                                    (string) $amenity->AID,
                                                    old('Amenity_ID', [])
                                                )"
                                            />
                                        @empty
                                            <p class="text-sm text-emerald-900/70 dark:text-zinc-300">
                                                No amenities are currently available for this facility.
                                            </p>
                                        @endforelse
                                    </x-ui::checkbox.group>

                                    @error('Amenity_ID')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror

                                    @error('Amenity_ID.*')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-ui::input
                                            label="First event day"
                                            name="Proposed_Date"
                                            type="date"
                                            x-ref="startDate"
                                            x-on:change="syncDailySchedules()"
                                            min="{{ now()->addDays(3)->toDateString() }}"
                                            value="{{ old('Proposed_Date', now()->addDays(3)->toDateString()) }}"
                                            x-bind:required="step === 2"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Book at least 3 days before your event.</p>
                                    </div>

                                    <div>
                                        <x-ui::input
                                            label="Last event day"
                                            name="Proposed_End_Date"
                                            type="date"
                                            x-ref="endDate"
                                            x-on:change="syncDailySchedules()"
                                            x-bind:min="$refs.startDate?.value || '{{ now()->addDays(3)->toDateString() }}'"
                                            min="{{ now()->addDays(3)->toDateString() }}"
                                            value="{{ old('Proposed_End_Date', old('Proposed_Date', now()->addDays(3)->toDateString())) }}"
                                            x-bind:required="step === 2"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Use the same date for a one-day event.</p>
                                    </div>

                                    <div class="space-y-3 sm:col-span-2">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-sm font-bold text-emerald-950 dark:text-white">Event time</h3>
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400" x-text="dailySchedules.length <= 1 ? 'Choose the start and end time.' : customizeDailyTimes ? 'Set a time for each event day.' : `This time will apply to all ${dailySchedules.length} event days.`"></p>
                                            </div>
                                            <button
                                                x-show="dailySchedules.length > 1"
                                                type="button"
                                                x-on:click="customizeDailyTimes ? useOneTimeForAllDays() : customizeDailyTimes = true"
                                                class="inline-flex h-9 items-center justify-center rounded-lg border border-emerald-600 bg-white px-3 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500 dark:bg-zinc-950 dark:text-emerald-300 dark:hover:bg-emerald-950/30"
                                                x-text="customizeDailyTimes ? 'Use one time for all days' : 'Customize each day'"
                                            ></button>
                                        </div>

                                        <div x-show="!customizeDailyTimes" class="grid gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20 sm:grid-cols-[1.2fr_1fr_1fr] sm:items-end">
                                            <div>
                                                <span class="block text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300" x-text="dailySchedules.length === 1 ? 'Booking day' : 'Booking period'"></span>
                                                <span class="mt-2 block font-semibold text-emerald-950 dark:text-white" x-text="dailySchedules.length === 1 ? new Date(`${dailySchedules[0]?.date}T12:00:00`).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : `${dailySchedules.length} consecutive days`"></span>
                                            </div>
                                            <label class="block">
                                                <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">Start time</span>
                                                <select x-model="sharedStartTime" x-on:change="chooseSharedStart($event.target.value)" x-bind:required="!customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                                                    <template x-for="slot in slots" :key="slot"><option :value="slot" :disabled="sharedStartDisabled(slot)" x-text="`${formatTime(slot)}${sharedStartDisabled(slot) ? ' — Already Booked' : ''}`"></option></template>
                                                </select>
                                            </label>
                                            <label class="block">
                                                <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">End time <span class="font-normal text-zinc-500">(1 hour minimum)</span></span>
                                                <select x-model="sharedEndTime" x-on:change="applySharedTime()" x-bind:required="!customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                                                    <template x-for="slot in endSlots.filter(slot => slot >= minimumEndTime(sharedStartTime))" :key="slot"><option :value="slot" :disabled="sharedEndDisabled(slot)" x-text="`${formatTime(slot)}${sharedEndDisabled(slot) ? ' — Already Booked' : ''}`"></option></template>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" aria-live="polite">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-sm font-bold text-emerald-950 dark:text-white">Available booking hours: 7:00 AM–8:00 PM</p>
                                                <p class="text-xs text-zinc-500">30-minute preparation + 30-minute cleanup buffer</p>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs" aria-label="Availability color guide">
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#10b981"></i> Available</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#f59e0b"></i> Pending request</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#ef4444"></i> Already booked</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#a1a1aa"></i> Closed / maintenance</span>
                                            </div>
                                            <p x-show="availabilityLoading" class="mt-3 text-sm text-zinc-500">Checking availability…</p>
                                            <p x-show="availabilityError" x-text="availabilityError" class="mt-3 text-sm font-medium text-red-600"></p>
                                            <div x-show="!availabilityLoading && !availabilityError" class="mt-4 space-y-3">
                                                <template x-for="schedule in dailySchedules" :key="`timeline-${schedule.date}`">
                                                    <div>
                                                        <div class="mb-1 flex justify-between text-xs"><span class="font-semibold" x-text="new Date(`${schedule.date}T12:00:00`).toLocaleDateString(undefined, {weekday:'short', month:'short', day:'numeric'})"></span><span class="capitalize" x-text="scheduleStatus(schedule) === 'approved' ? 'Already Booked' : scheduleStatus(schedule) === 'pending' ? 'Pending Request' : scheduleStatus(schedule)"></span></div>
                                                        <div class="flex h-5 overflow-hidden rounded-full bg-zinc-200" role="img" :aria-label="`Daily availability for ${schedule.date}`">
                                                            <template x-for="slot in slots" :key="`${schedule.date}-${slot}`"><span class="flex-1 border-r border-white/40" :style="`background:${slotStatus(schedule.date, slot, addMinutes(slot, 30)) === 'available' ? '#10b981' : slotStatus(schedule.date, slot, addMinutes(slot, 30)) === 'pending' ? '#f59e0b' : slotStatus(schedule.date, slot, addMinutes(slot, 30)) === 'approved' ? '#ef4444' : '#a1a1aa'}`"></span></template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        @include('requests.partials.booking-summary')

                                        <template x-for="(schedule, index) in dailySchedules" :key="schedule.date">
                                            <div>
                                                <input type="hidden" x-bind:name="`Daily_Schedules[${index}][date]`" x-bind:value="schedule.date">
                                                <input type="hidden" x-bind:name="!customizeDailyTimes ? `Daily_Schedules[${index}][start]` : null" x-bind:value="schedule.start">
                                                <input type="hidden" x-bind:name="!customizeDailyTimes ? `Daily_Schedules[${index}][end]` : null" x-bind:value="schedule.end">

                                                <div x-show="customizeDailyTimes" class="grid gap-3 rounded-xl border border-emerald-900/10 bg-emerald-50/60 p-4 dark:border-white/10 dark:bg-zinc-900 sm:grid-cols-[1.2fr_1fr_1fr] sm:items-end">
                                                    <div>
                                                        <span class="block text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Booking day</span>
                                                        <span class="mt-2 block font-semibold text-emerald-950 dark:text-white" x-text="new Date(`${schedule.date}T12:00:00`).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })"></span>
                                                    </div>
                                                    <label class="block">
                                                        <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">Start time</span>
                                                        <select x-bind:name="customizeDailyTimes ? `Daily_Schedules[${index}][start]` : null" x-model="schedule.start" x-on:change="chooseDayStart(schedule, $event.target.value)" x-bind:required="customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm dark:border-white/10 dark:bg-zinc-950 dark:text-white"><template x-for="slot in slots" :key="slot"><option :value="slot" :disabled="['approved', 'unavailable'].includes(slotStatus(schedule.date, slot, addMinutes(slot, 60)))" x-text="`${formatTime(slot)}${slotStatus(schedule.date, slot, addMinutes(slot, 60)) === 'approved' ? ' — Already Booked' : ''}`"></option></template></select>
                                                    </label>
                                                    <label class="block">
                                                        <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">End time <span class="font-normal text-zinc-500">(1 hour minimum)</span></span>
                                                        <select x-bind:name="customizeDailyTimes ? `Daily_Schedules[${index}][end]` : null" x-model="schedule.end" x-bind:required="customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm dark:border-white/10 dark:bg-zinc-950 dark:text-white"><template x-for="slot in endSlots.filter(slot => slot >= minimumEndTime(schedule.start))" :key="slot"><option :value="slot" :disabled="['approved', 'unavailable'].includes(slotStatus(schedule.date, schedule.start, slot))" x-text="`${formatTime(slot)}${slotStatus(schedule.date, schedule.start, slot) === 'approved' ? ' — Already Booked' : ''}`"></option></template></select>
                                                    </label>
                                                </div>
                                            </div>
                                        </template>

                                        @error('Daily_Schedules') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        @error('Daily_Schedules.*.date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        @error('Daily_Schedules.*.start') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        @error('Daily_Schedules.*.end') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    @include('requests.partials.purpose-questionnaire')

                                    <div class="sm:col-span-2">
                                        <x-ui::input label="Expected attendees" name="Capacity" type="number" min="1" max="{{ $facility->Capacity ?? 100000 }}" value="{{ old('Capacity') }}" />
                                        @error('Capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <x-ui::input
                                            type="file"
                                            name="attachment"
                                            label="Request letter"
                                            accept=".pdf,application/pdf"
                                        />
                                        <p class="mt-1 text-xs text-emerald-900/70 dark:text-zinc-300">PDF — max 5 MB.</p>
                                        @error('attachment') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-emerald-900/10 px-4 py-2 text-sm font-medium text-emerald-900 transition hover:border-emerald-700 hover:bg-emerald-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        x-on:click="step = 1; window.scrollTo({ top: 0, behavior: 'smooth' })"
                                    >
                                        Back
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:pointer-events-none disabled:opacity-60 sm:w-auto"
                                        data-ui-confirm="Are you sure you want to submit this reservation request? Please review the selected facility, date, time, and amenities before continuing."
                                        data-ui-confirm-title="Confirm request submission"
                                        data-ui-confirm-label="Submit request"
                                        x-bind:disabled="submitting || availabilityLoading || availabilityError || hasBlockingConflict()"
                                        x-bind:aria-busy="submitting"
                                    >
                                        <span x-show="!submitting">Send request</span>
                                        <span x-cloak x-show="submitting">Submitting…</span>
                                    </button>
                                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-900/10 px-4 py-2 text-sm font-medium text-emerald-900 transition hover:border-emerald-700 hover:bg-emerald-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-zinc-800">Back to home</a>
                                </div>
                            </div>
                        </form>
                        </div>
                    </x-ui::card>
                </div>
            </div>
        </div>
    </x-ui::main>
</x-layouts.home.header>
