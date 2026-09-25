<x-layouts.home.header>
    @php
        $photos = $facility->images
            ->map(fn ($image) => asset('storage/'.ltrim($image->image_path, '/')))
            ->values();

        if ($photos->isEmpty() && filled($facility->Image_URL)) {
            $photos->push($facility->primaryImageUrl());
        }

        $rate = filled($facility->rates)
            ? $facility->rates
            : ($facility->Price !== null
                ? ((float) $facility->Price > 0 ? '₱'.number_format((float) $facility->Price, 2) : 'No rental fee')
                : 'Rate upon inquiry');
        $isAvailable = $facility->Status === 'Available';
        $assignedOfficeAdmin = $facility->assignedAdmins->first();
        $fixedAmenities = $facility->amenities->filter->isPermanent();
        $additionalAmenities = $facility->amenities->reject->isPermanent();
    @endphp

    <main class="min-h-screen bg-zinc-100 pb-20 pt-10 text-zinc-950 sm:pt-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}#facilities" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:text-emerald-900">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Back to facilities
            </a>

            <div class="mt-6 grid gap-8 lg:grid-cols-[1.15fr_.85fr] lg:items-start">
                <section
                    class="overflow-hidden rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6"
                    x-data="{
                        activePhoto: 0,
                        expanded: false,
                        openPhoto() {
                            this.expanded = true;
                            document.body.classList.add('overflow-hidden');
                        },
                        closePhoto() {
                            this.expanded = false;
                            document.body.classList.remove('overflow-hidden');
                        },
                        previousPhoto() {
                            this.activePhoto = (this.activePhoto - 1 + {{ $photos->count() }}) % {{ $photos->count() }};
                        },
                        nextPhoto() {
                            this.activePhoto = (this.activePhoto + 1) % {{ $photos->count() }};
                        }
                    }"
                    x-on:keydown.escape.window="closePhoto()"
                    x-on:keydown.left.window="if (expanded) previousPhoto()"
                    x-on:keydown.right.window="if (expanded) nextPhoto()"
                    aria-labelledby="facility-gallery-title"
                >
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-700">Facility gallery</p>
                            <h2 id="facility-gallery-title" class="mt-2 text-2xl font-black">Explore this space</h2>
                        </div>
                        <p class="text-sm font-semibold text-zinc-500">{{ $photos->count() }} {{ Str::plural('photo', $photos->count()) }}</p>
                    </div>

                    @if ($photos->isNotEmpty())
                        <button
                            type="button"
                            class="group relative mt-5 block aspect-[16/10] w-full cursor-zoom-in overflow-hidden rounded-2xl bg-zinc-200 text-left focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-600/40"
                            x-on:click="openPhoto()"
                            aria-label="Expand selected facility photo"
                        >
                            @foreach ($photos as $index => $photo)
                                <img
                                    x-show="activePhoto === {{ $index }}"
                                    src="{{ $photo }}"
                                    alt="{{ $facility->Facility_Name }} photo {{ $index + 1 }}"
                                    class="h-full w-full object-cover"
                                    @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                >
                            @endforeach
                            <span class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-full bg-black/70 px-4 py-2 text-sm font-bold text-white opacity-0 shadow-lg backdrop-blur-sm transition group-hover:opacity-100 group-focus-visible:opacity-100">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="m20 20-4-4M11 8v6M8 11h6" />
                                </svg>
                                Expand photo
                            </span>
                        </button>

                        @if ($photos->count() > 1)
                            <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                                @foreach ($photos as $index => $photo)
                                    <button type="button" x-on:click="activePhoto = {{ $index }}" class="aspect-[4/3] overflow-hidden rounded-xl border-2 transition" x-bind:class="activePhoto === {{ $index }} ? 'border-emerald-700' : 'border-transparent opacity-70 hover:opacity-100'" aria-label="Show photo {{ $index + 1 }}">
                                        <img src="{{ $photo }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <template x-teleport="body">
                            <div
                                x-cloak
                                x-show="expanded"
                                x-transition.opacity
                                class="fixed inset-x-0 bottom-0 top-16 z-40 flex items-center justify-center bg-emerald-950/95 px-16 py-8 sm:top-20 sm:px-24 lg:px-32"
                                role="dialog"
                                aria-modal="true"
                                aria-label="Expanded facility photo"
                                x-on:click.self="closePhoto()"
                            >
                                <button
                                    type="button"
                                    class="absolute right-4 top-4 z-10 inline-flex size-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-6 sm:top-6"
                                    x-on:click="closePhoto()"
                                    aria-label="Close expanded photo"
                                >
                                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                                        <path d="M6 6l12 12M18 6 6 18" />
                                    </svg>
                                </button>

                                @if ($photos->count() > 1)
                                    <button type="button" class="fixed left-4 top-1/2 z-10 inline-flex size-12 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white/70 bg-black/25 text-white transition hover:border-amber-300 hover:bg-black/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 sm:left-6" x-on:click="previousPhoto()" aria-label="Show previous photo">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                                    </button>

                                    <button type="button" class="fixed right-4 top-1/2 z-10 inline-flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white transition hover:bg-black/55 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-6" x-on:click="nextPhoto()" aria-label="Show next photo">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                    </button>
                                @endif

                                @foreach ($photos as $index => $photo)
                                    <img
                                        x-show="activePhoto === {{ $index }}"
                                        src="{{ $photo }}"
                                        alt="{{ $facility->Facility_Name }} photo {{ $index + 1 }} enlarged"
                                        class="max-h-[calc(100vh-10rem)] max-w-full rounded-2xl object-contain shadow-2xl"
                                    >
                                @endforeach

                                @if ($photos->count() > 1)
                                    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-black/40 px-5 py-2 text-sm font-black text-white" aria-live="polite">
                                        <span x-text="activePhoto + 1"></span> / {{ $photos->count() }}
                                    </div>
                                @endif
                            </div>
                        </template>
                    @else
                        <div class="mt-5 flex aspect-[16/10] items-center justify-center rounded-2xl bg-slate-200 text-center font-bold text-slate-500">
                            No facility images available
                        </div>
                    @endif
                </section>

                <article class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full bg-zinc-200 px-3 py-1 text-xs font-black uppercase tracking-wide text-zinc-700">{{ str($facility->facility_type ?: 'Facility')->headline() }}</span>
                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide text-white',
                            'bg-emerald-700' => $isAvailable,
                            'bg-red-600' => ! $isAvailable,
                        ])>{{ $isAvailable ? 'Available' : 'Unavailable' }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-black leading-tight sm:text-4xl">{{ $facility->Facility_Name }}</h1>
                    <p class="mt-4 leading-7 text-zinc-600">{{ $facility->Description ?: 'Campus facility available for reservation.' }}</p>

                    @if (! $isAvailable)
                        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-950">
                            <div class="flex items-start gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">
                                    <x-ui::icon.calendar-days class="size-5" />
                                </span>
                                <div>
                                    <p class="font-black">Temporarily unavailable</p>
                                    <p class="mt-1 text-sm leading-6 text-amber-900/80">This facility is not accepting reservation requests right now.</p>
                                </div>
                            </div>
                            @if ($facility->Available_At)
                                <p class="mt-4 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-900">
                                    <span class="block text-xs font-black uppercase tracking-wide text-emerald-700">Expected to reopen</span>
                                    <span class="mt-1 block text-base font-black">{{ $facility->Available_At->format('M j, Y \a\t g:i A') }}</span>
                                </p>
                            @else
                                <p class="mt-4 rounded-xl border border-amber-200 bg-white/70 px-4 py-3 text-sm leading-6 text-amber-900">
                                    A reopening date has not been announced yet. Please check back later or contact the managing office for assistance.
                                </p>
                            @endif
                        </div>
                    @endif

                    <dl class="mt-7 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-emerald-50 p-4"><dt class="text-xs font-black uppercase tracking-wide text-emerald-700">Capacity</dt><dd class="mt-2 font-bold">{{ $facility->Capacity ? number_format($facility->Capacity).' people' : 'Not specified' }}</dd></div>
                        <div class="rounded-2xl bg-emerald-50 p-4"><dt class="text-xs font-black uppercase tracking-wide text-emerald-700">Managing office</dt><dd class="mt-2 font-bold">{{ $facility->Office ?: 'Not specified' }}</dd></div>

                        @if ($assignedOfficeAdmin)
                            <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white sm:col-span-2">
                                <div class="flex items-center gap-4 p-4 sm:p-5">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white shadow-sm" aria-hidden="true">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="5" width="18" height="14" rx="2" />
                                            <path d="m3 7 9 6 9-6" />
                                        </svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <dt class="text-xs font-black uppercase tracking-wide text-emerald-700">Facility contact</dt>
                                        <dd class="mt-1 truncate font-bold text-zinc-900">{{ $assignedOfficeAdmin->name }}</dd>
                                        <dd class="text-xs font-medium text-zinc-500">Assigned office administrator</dd>
                                    </div>

                                </div>

                                <a
                                    href="mailto:{{ $assignedOfficeAdmin->email }}"
                                    class="flex items-center justify-between gap-3 border-t border-emerald-100 bg-emerald-50/70 px-4 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-100 sm:px-5"
                                >
                                    <span class="min-w-0 break-all">{{ $assignedOfficeAdmin->email }}</span>
                                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-7 space-y-5 border-t border-zinc-200 pt-6">
                        @if ($fixedAmenities->isNotEmpty())
                            <section>
                                <h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Fixed amenities</h2>
                                <p class="mt-1 text-xs text-zinc-500">Built into the facility and automatically included.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($fixedAmenities as $amenity)
                                        <span class="rounded-full border border-emerald-200 px-3 py-1.5 text-sm font-bold text-emerald-800">{{ $amenity->name }}</span>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($additionalAmenities->isNotEmpty())
                            <section>
                                <h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Additional amenities</h2>
                                <p class="mt-1 text-xs text-zinc-500">Optional items that may be requested during booking.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($additionalAmenities as $amenity)
                                        <span class="rounded-full border border-emerald-200 px-3 py-1.5 text-sm font-bold text-emerald-800">
                                            {{ $amenity->name }} — {{ $amenity->quantityLabel() }}
                                        </span>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($fixedAmenities->isEmpty() && $additionalAmenities->isEmpty())
                            <p class="text-sm text-zinc-500">No amenities listed.</p>
                        @endif
                    </div>

                    <div class="mt-7 grid gap-4 border-t border-zinc-200 pt-6">
                        <div><h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Rates</h2><p class="mt-2 whitespace-pre-line leading-7 text-zinc-600">{{ $rate }}</p></div>
                        <div><h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Protocols and guidelines</h2><p class="mt-2 whitespace-pre-line leading-7 text-zinc-600">{{ $facility->protocols_and_guidelines ?: ($facility->Protocols ?: 'No protocols listed.') }}</p></div>
                    </div>

                    @if ($isAvailable)
                        <a href="{{ route('requests.create', ['facilitySlug' => $facility->slug]) }}" class="mt-8 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-6 py-3 font-black text-white transition hover:bg-emerald-800">
                            Book This Facility
                        </a>
                    @else
                        <span class="mt-8 inline-flex min-h-12 w-full cursor-not-allowed items-center justify-center rounded-xl bg-zinc-200 px-6 py-3 font-black text-zinc-500">
                            Currently Unavailable
                        </span>
                    @endif
                    @guest
                        <p class="mt-3 text-center text-xs font-semibold text-zinc-500">You will be asked to sign in before submitting a booking request.</p>
                    @endguest
                </article>
            </div>
        </div>
    </main>
</x-layouts.home.header>
