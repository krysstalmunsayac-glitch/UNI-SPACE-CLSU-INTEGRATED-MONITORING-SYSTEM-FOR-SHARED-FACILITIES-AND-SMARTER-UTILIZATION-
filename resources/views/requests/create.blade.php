<x-layouts.home.header>
    <flux:main class="py-12 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-8">
            <div class="rounded-2xl bg-white/90 p-6 shadow-lg shadow-emerald-950/5 ring-1 ring-emerald-900/10 backdrop-blur dark:bg-zinc-950/90 dark:ring-white/5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">Facility request</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-emerald-950 dark:text-white">{{ $facility->Facility_Name }}</h1>
                        <p class="mt-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">Submit a request for this facility, select available amenities, and choose the date and time you need.</p>
                    </div>
                    <div class="flex flex-col gap-3 rounded-2xl border border-emerald-900/10 bg-emerald-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Status</p>
                        <span class="w-fit rounded-full bg-emerald-700 px-3 py-1 text-sm font-semibold text-white">{{ $facility->Status }}</span>
                        <p class="text-sm text-emerald-900/70 dark:text-zinc-300">Capacity: {{ $facility->Capacity ?? 'N/A' }}</p>
                        <p class="text-sm text-emerald-900/70 dark:text-zinc-300">Location: {{ $facility->Location ?? 'Unspecified' }}</p>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <div
                class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.85fr)]"
                x-data="{
                    step: {{ $errors->has('Event_Title') || $errors->has('Description') || $errors->has('Type_Event') || $errors->has('Other_Event_Type') || $errors->has('Status') ? 1 : (old('_step', 1)) }},
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
                    }
                }"
                x-on:keydown.escape.window="if (activePhoto !== null) closePhoto()"
                x-on:keydown.left.window="if (activePhoto !== null && photos.length > 1) previousPhoto()"
                x-on:keydown.right.window="if (activePhoto !== null && photos.length > 1) nextPhoto()"
            >
                <section aria-labelledby="facility-gallery-heading">
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">Facility gallery</p>
                            <h2 id="facility-gallery-heading" class="mt-1 text-xl font-black text-emerald-950 dark:text-white">
                                Explore the space
                            </h2>
                        </div>
                        @if ($facility->images->isNotEmpty())
                            <p class="text-sm text-emerald-900/60 dark:text-zinc-400">
                                {{ $facility->images->count() }} {{ Str::plural('photo', $facility->images->count()) }}
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        @forelse ($facility->images as $image)
                            <button
                                type="button"
                                class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-emerald-50 text-left shadow-lg shadow-emerald-950/5 ring-1 ring-emerald-900/10 transition hover:ring-2 hover:ring-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:bg-zinc-900 dark:ring-white/10"
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
                            <div class="col-span-2 flex min-h-72 flex-col items-center justify-center rounded-2xl bg-emerald-50 p-8 text-center shadow-lg shadow-emerald-950/5 ring-1 ring-emerald-900/10 dark:bg-zinc-900 dark:ring-white/10">
                                <img src="{{ $facility->primaryImageUrl() }}" alt="" class="mb-5 h-20 w-20 object-contain opacity-70">
                                <p class="font-semibold text-emerald-950 dark:text-white">No facility photos yet</p>
                                <p class="mt-1 text-sm text-emerald-900/60 dark:text-zinc-400">Photos of this facility will appear here when available.</p>
                            </div>
                        @endforelse
                    </div>
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

                <div class="space-y-6 lg:sticky lg:top-6">
                    <flux:card>
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                                    :class="step === 1 ? 'bg-emerald-700 text-white' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'"
                                >1</span>
                                <span class="text-sm font-medium" :class="step === 1 ? 'text-emerald-950 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">Event details</span>
                            </div>
                            <div class="h-px flex-1 bg-emerald-900/10 dark:bg-white/10"></div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                                    :class="step === 2 ? 'bg-emerald-700 text-white' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'"
                                >2</span>
                                <span class="text-sm font-medium" :class="step === 2 ? 'text-emerald-950 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">Request details</span>
                            </div>
                        </div>

                        <form
                            x-ref="requestForm"
                            action="{{ route('requests.store', $facility) }}"
                            method="POST"
                            enctype="multipart/form-data"
                            class="space-y-4"
                        >
                            @csrf

                            {{-- STEP 1: Event details --}}
                            <div x-show="step === 1" x-cloak class="space-y-4">
                                <flux:heading size="lg">Event details</flux:heading>
                                <p class="text-sm text-emerald-900/70 dark:text-zinc-300">Tell us about the event this request is for.</p>

                                <div>
                                    <flux:input
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
                                    <flux:textarea
                                        label="Description"
                                        name="Description"
                                        rows="4"
                                        required
                                        minlength="5"
                                        maxlength="2000"
                                    >{{ old('Description') }}</flux:textarea>
                                    @error('Description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <flux:select label="Event type" name="Type_Event" x-model="eventType" required>
                                            <flux:select.option value="">Select a type</flux:select.option>
                                            {{-- Adjust these to match your Type_Event enum/values --}}
                                            <flux:select.option value="Meeting" :selected="old('Type_Event') == 'Meeting'">Meeting</flux:select.option>
                                            <flux:select.option value="Seminar" :selected="old('Type_Event') == 'Seminar'">Seminar</flux:select.option>
                                            <flux:select.option value="Workshop" :selected="old('Type_Event') == 'Workshop'">Workshop</flux:select.option>
                                            <flux:select.option value="Conference" :selected="old('Type_Event') == 'Conference'">Conference</flux:select.option>
                                            <flux:select.option value="Other" :selected="old('Type_Event') == 'Other'">Other</flux:select.option>
                                        </flux:select>
                                        @error('Type_Event') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div x-cloak x-show="eventType === 'Other'" x-transition>
                                        <flux:input
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
                                        Next: Request details
                                    </button>
                                </div>
                            </div>

                            {{-- STEP 2: Facility request details --}}
                            <div x-show="step === 2" x-cloak class="space-y-4">
                                <flux:heading size="lg">Request details</flux:heading>

                                <div>
                                    <flux:checkbox.group label="Amenities">
                                        @forelse ($facility->amenities->where('Status', 'Available') as $amenity)
                                            <flux:checkbox
                                                name="Amenity_ID[]"
                                                value="{{ $amenity->AID }}"
                                                label="{{ $amenity->name }}{{ $amenity->reservation_limit ? ' ('.$amenity->reservation_limit.' available)' : '' }}"
                                                :checked="in_array(
                                                    (string) $amenity->AID,
                                                    old('Amenity_ID', [])
                                                )"
                                            />
                                        @empty
                                            <p class="text-sm text-emerald-900/70 dark:text-zinc-300">
                                                No amenities are currently attached to this facility.
                                            </p>
                                        @endforelse
                                    </flux:checkbox.group>

                                    @error('Amenity_ID')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror

                                    @error('Amenity_ID.*')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <flux:input
                                            label="Proposed date"
                                            name="Proposed_Date"
                                            type="date"
                                            min="{{ now()->addDays(3)->toDateString() }}"
                                            value="{{ old('Proposed_Date', now()->addDays(3)->toDateString()) }}"
                                            x-bind:required="step === 2"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Book at least 3 days before your event.</p>
                                        @error('Proposed_Date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div></div>

                                    <div>
                                        <flux:input label="Start time" name="Proposed_Start_Time" type="time" value="{{ old('Proposed_Start_Time', '09:00') }}" x-bind:required="step === 2" />
                                        @error('Proposed_Start_Time') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <flux:input label="End time" name="Proposed_End_Time" type="time" value="{{ old('Proposed_End_Time', '10:00') }}" x-bind:required="step === 2" />
                                        @error('Proposed_End_Time') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <flux:input label="Purpose" name="Purpose" value="{{ old('Purpose') }}" x-bind:required="step === 2" minlength="5" maxlength="1000" />
                                        @error('Purpose') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <flux:input label="Expected attendees" name="Capacity" type="number" min="1" max="{{ $facility->Capacity ?? 100000 }}" value="{{ old('Capacity') }}" />
                                        @error('Capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <flux:input
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
                                    <flux:button type="submit" class="w-full sm:w-auto">Send request</flux:button>
                                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-900/10 px-4 py-2 text-sm font-medium text-emerald-900 transition hover:border-emerald-700 hover:bg-emerald-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-zinc-800">Back to home</a>
                                </div>
                            </div>
                        </form>
                    </flux:card>
                </div>
            </div>
        </div>
    </flux:main>
</x-layouts.home.header>
