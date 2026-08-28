<x-layouts.home.header>
    <section id="home" class="scroll-mt-20 bg-gradient-to-b from-white to-emerald-50/40 dark:from-zinc-950 dark:to-emerald-950/10">
        <div class="mx-auto grid min-h-[520px] max-w-7xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <div class="mx-auto max-w-2xl lg:mx-0">
                <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">
                    External user dashboard
                </p>
                <h1 class="mt-4 text-5xl font-black leading-[0.95] tracking-tight text-emerald-950 dark:text-white sm:text-6xl lg:text-7xl">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="mt-8 max-w-xl text-xl leading-8 text-emerald-900/75 dark:text-emerald-100/80">
                    Browse available campus spaces, check the booking calendar, and send your reservation request from one familiar SIEL SPACE dashboard.
                </p>
                <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                    <a href="#facilities" class="group inline-flex items-center justify-center rounded-xl bg-emerald-700 px-7 py-4 text-base font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:bg-emerald-800">
                        Browse Facilities
                        <svg aria-hidden="true" class="ml-3 h-5 w-5 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m13 6 6 6-6 6"></path>
                        </svg>
                    </a>
                    <a href="#calendar" class="inline-flex items-center justify-center rounded-xl bg-emerald-50 px-7 py-4 text-base font-bold text-emerald-950 transition hover:bg-emerald-100 dark:bg-zinc-900 dark:text-emerald-100 dark:hover:bg-zinc-800">
                        View Calendar
                        <svg aria-hidden="true" class="ml-3 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                            <path d="M16 2v4M8 2v4M3 10h18"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="hidden lg:block">
                <div class="rounded-[2rem] border border-emerald-900/10 bg-white p-6 shadow-2xl shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-900">
                    <div class="grid grid-cols-7 gap-3 text-center text-sm font-semibold text-emerald-900/70 dark:text-zinc-300">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                            <div>{{ $day }}</div>
                        @endforeach
                        @foreach ([18, 19, 20, 21, 22, 23, 24] as $day)
                            <div @class([
                                'rounded-xl py-3 text-2xl font-black',
                                'bg-emerald-600 text-white' => $day === 20,
                                'text-emerald-950 dark:text-white' => $day !== 20,
                            ])>{{ $day }}</div>
                        @endforeach
                    </div>
                    <div class="mt-6 grid grid-cols-[80px_repeat(7,minmax(0,1fr))] overflow-hidden rounded-2xl border border-emerald-900/10 text-sm dark:border-white/10">
                        @foreach (['08:00', '09:00', '10:00', '11:00', '12:00', '13:00'] as $time)
                            <div class="border-b border-emerald-900/10 bg-emerald-50 p-4 font-semibold text-emerald-700 dark:border-white/10 dark:bg-zinc-950 dark:text-emerald-300">{{ $time }}</div>
                            @for ($i = 0; $i < 7; $i++)
                                <div class="min-h-16 overflow-hidden border-b border-l border-emerald-900/10 p-2 dark:border-white/10">
                                    @if (($time === '09:00' && $i === 4) || ($time === '10:00' && $i === 3) || ($time === '13:00' && $i === 4))
                                        <div class="{{ $time === '13:00' ? 'bg-yellow-400 text-emerald-950' : 'bg-emerald-600 text-white' }} max-w-full truncate rounded-xl px-2 py-2 text-center text-[10px] font-bold leading-none shadow-sm">
                                            {{ $time === '13:00' ? 'Workshop' : 'Reserved' }}
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('pages.partials.about-content')

    <section id="facilities" class="border-t border-emerald-900/10 bg-white py-20 dark:border-white/10 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-5xl font-black tracking-tight text-emerald-950 dark:text-white">Find your perfect space</h2>
                <p class="mt-5 text-xl text-emerald-900/70 dark:text-zinc-300">
                    Search study rooms, event halls, laboratories, and collaborative workspaces before creating your request.
                </p>
            </div>

            <div class="mt-14 grid gap-4 rounded-2xl border border-emerald-900/10 bg-white/80 p-3 shadow-sm dark:border-white/10 dark:bg-zinc-900/70 lg:grid-cols-[1fr_190px_220px]">
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Search</span>
                    <span class="relative block">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-emerald-700 dark:text-emerald-300">⌕</span>
                        <input id="facility-search" type="search" placeholder="Search facilities..." class="h-14 w-full rounded-xl border border-emerald-900/10 bg-white pl-12 pr-4 text-base text-emerald-950 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                    </span>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Capacity</span>
                    <span class="relative block">
                        <select id="capacity-filter" class="h-14 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-base font-semibold text-emerald-950 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                            <option value="all">All capacities</option>
                            <option value="small">70-150</option>
                            <option value="medium">151-300</option>
                            <option value="large">301+</option>
                            <option value="custom">Other / Specific capacity</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
                    <input id="capacity-custom" type="number" min="70" max="2000" placeholder="Enter 70-2,000" class="mt-2 hidden h-12 w-full rounded-xl border border-emerald-900/10 bg-white px-4 text-base font-semibold text-emerald-950 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Facility type</span>
                    <span class="relative block">
                        <select id="type-filter" class="h-14 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-base font-semibold text-emerald-950 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                            <option value="all">All facility types</option>
                            @foreach ($facilities->pluck('facility_type')->filter()->unique()->sort()->values() as $type)
                                <option value="{{ strtolower($type) }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
                </label>
            </div>

            <p class="mt-5 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300">
                <span>Showing</span>
                <span id="facility-count">{{ $facilities->count() }}</span>
                <span>of {{ $facilities->count() }} facilities</span>
            </p>

            <div id="facility-grid" class="mt-14 grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($facilities as $facility)
                    @php
                        $capacity = (int) ($facility->Capacity ?? 0);
                        $capacityGroup = $capacity > 300 ? 'large' : ($capacity > 150 ? 'medium' : 'small');
                        $facilityType = strtolower($facility->facility_type ?? 'other');
                    @endphp
                    <article
                        class="facility-card group {{ $loop->index >= 6 ? 'hidden' : '' }} overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-900"
                        data-name="{{ strtolower($facility->Facility_Name.' '.$facility->Description.' '.$facility->Location) }}"
                        data-capacity="{{ $capacityGroup }}"
                        data-capacity-value="{{ $capacity }}"
                        data-type="{{ $facilityType }}"
                    >
                        <a href="{{ route('requests.create', $facility) }}" class="relative block aspect-[16/10] overflow-hidden bg-emerald-50 dark:bg-zinc-800">
                            <img
                                src="{{ $facility->primaryImageUrl() }}"
                                alt="{{ $facility->Facility_Name }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                loading="lazy"
                            >
                            <span class="absolute left-4 top-4 rounded-full bg-yellow-400 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-950">
                                {{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Facility' }}
                            </span>
                        </a>
                        <div class="p-5">
                            <h3 class="text-xl font-black text-emerald-950 dark:text-white">{{ $facility->Facility_Name }}</h3>
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">
                                {{ $facility->Description ?? 'Campus facility available for reservation.' }}
                            </p>
                            <div class="mt-5 flex flex-wrap items-center gap-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                <span>{{ $facility->Location ?? 'Campus' }}</span>
                                <span>•</span>
                                <span>{{ $facility->Capacity ?? 'N/A' }} capacity</span>
                            </div>
                            <a href="{{ route('requests.create', $facility) }}" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 font-bold text-white transition hover:bg-emerald-800">
                                Reserve
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-emerald-900/20 bg-emerald-50 p-10 text-center text-emerald-900 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300">
                        No facilities are currently available for reservation.
                    </div>
                @endforelse
            </div>

            @if ($facilities->count() > 6)
                <div class="mt-10 flex justify-center">
                    <button
                        id="facility-see-more"
                        type="button"
                        class="rounded-xl border-2 border-emerald-600 bg-white px-7 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 dark:bg-zinc-900 dark:text-emerald-300"
                    >
                        See more
                    </button>
                </div>
            @endif
        </div>
    </section>

    <livewire:facility-request-list />

    <section id="calendar" class="bg-white dark:bg-zinc-950">
        <div class="bg-emerald-800 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-5xl font-black tracking-tight">Booking calendar</h2>
                <p class="mt-5 text-xl text-emerald-50">View all upcoming facility reservations before choosing a date.</p>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <x-public-booking-calendar calendar-id="user-dashboard-calendar" :events="$schedules" />
        </div>
    </section>

    <style>
        .dashboard-reveal {
            opacity: 0;
            transform: translateY(1.25rem);
            transition: opacity 700ms ease, transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: opacity, transform;
        }

        .dashboard-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .dashboard-reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }

        #map-facility-type:focus,
        #map-facility-filter:focus {
            outline: 3px solid rgba(255, 255, 255, 0.35);
            outline-offset: 2px;
            box-shadow: none;
        }

        .campus-map-container {
            max-width: 1600px;
        }

        .campus-map-canvas {
            height: 560px;
            min-height: 560px;
        }

        @media (min-width: 1024px) {
            .campus-map-layout {
                grid-template-columns: 420px minmax(0, 1fr);
                align-items: stretch;
            }

            .campus-map-canvas {
                height: 760px;
                min-height: 760px;
            }
        }
    </style>

    <section id="map" class="scroll-mt-28 border-t border-emerald-900/10 bg-emerald-50/50 py-20 dark:border-white/10 dark:bg-zinc-900" style="scroll-margin-top: 12rem;">
        <div class="campus-map-container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="campus-map-layout grid gap-6">
                <div class="rounded-2xl bg-emerald-800 p-8 text-white">
                    <h2 class="text-4xl font-black">Campus map</h2>
                    <p class="mt-4 text-lg leading-8 text-emerald-50">
                        Explore the CLSU campus map before sending your facility request.
                    </p>

                    <div class="mt-8 space-y-3 text-sm font-semibold text-emerald-50">
                        <p>Central Luzon State University</p>
                        <p>Science City of Munoz, Nueva Ecija</p>
                    </div>

                    <div class="mt-8 space-y-4 rounded-xl border border-white/20 bg-white/10 p-4">
                        <div>
                            <label for="map-facility-type" class="text-xs font-black uppercase tracking-wider text-emerald-100">Facility type</label>
                            <div class="mt-2" style="position: relative;">
                                <select id="map-facility-type" class="w-full rounded-xl border border-white/20 bg-white py-2.5 pl-3 text-sm font-bold text-emerald-950 outline-none focus:ring-4 focus:ring-white/20" style="appearance: none; padding-right: 3rem;">
                                    <option value="all">All facility types</option>
                                </select>
                                <svg class="map-select-chevron" data-select="map-facility-type" role="button" aria-label="Open facility type options" tabindex="0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 1rem; top: 50%; width: 1.1rem; height: 1.1rem; transform: translateY(-50%); color: #047857; cursor: pointer;">
                                    <path d="m6 8 4 4 4-4" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label for="map-facility-filter" class="text-xs font-black uppercase tracking-wider text-emerald-100">Locate facility</label>
                            <div class="mt-2" style="position: relative;">
                                <select id="map-facility-filter" class="w-full rounded-xl border border-white/20 bg-white py-2.5 pl-3 text-sm font-bold text-emerald-950 outline-none focus:ring-4 focus:ring-white/20" style="appearance: none; padding-right: 3rem;">
                                    <option value="all">All Facilities</option>
                                </select>
                                <svg class="map-select-chevron" data-select="map-facility-filter" role="button" aria-label="Open facility options" tabindex="0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 1rem; top: 50%; width: 1.1rem; height: 1.1rem; transform: translateY(-50%); color: #047857; cursor: pointer;">
                                    <path d="m6 8 4 4 4-4" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <button id="get-facility-directions" type="button" disabled class="w-full rounded-xl bg-white px-4 py-3 text-sm font-black text-emerald-800 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60">
                                Directions from CLSU Main Gate
                            </button>
                            <button id="get-my-location-directions" type="button" disabled class="mt-2 w-full rounded-xl border border-white/40 bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60">
                                Directions from My Location
                            </button>
                            <p id="map-location-status" class="mt-2 text-xs leading-5 text-emerald-50" aria-live="polite">Select a facility to view directions.</p>
                            <a id="open-navigation-link" href="#" target="_blank" rel="noopener noreferrer" class="mt-3 hidden w-full items-center justify-center rounded-xl border border-white/40 px-4 py-2.5 text-center text-xs font-black text-white transition hover:bg-white/10">
                                Open Walking Navigation
                            </a>
                        </div>
                        <div>
                            <button id="dashboard-locate-me" type="button" class="w-full rounded-xl border border-white/40 bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/20 disabled:cursor-wait disabled:opacity-60">
                                <span aria-hidden="true">⌖</span> Use my location
                            </button>
                            <p id="dashboard-location-status" class="mt-2 text-xs leading-5 text-emerald-50" aria-live="polite"></p>
                        </div>
                    </div>

                </div>

                <div class="relative z-0 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-950">
                    <div id="user-campus-map" class="campus-map-canvas relative z-0 flex w-full items-center justify-center bg-emerald-50 text-sm font-bold text-emerald-900 dark:bg-zinc-900 dark:text-emerald-200">
                        Loading campus map...
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="help" class="bg-white py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-5xl font-black tracking-tight text-emerald-950 dark:text-white">How can we help?</h2>
                <p class="mt-5 text-xl text-emerald-900/70 dark:text-zinc-300">Find answers to common questions and learn how to make the most of SIEL SPACE.</p>
            </div>

            <div class="mt-14 space-y-5">
                @foreach ([
                    'How do I reserve a facility?' => 'Choose an available facility, click Reserve, then complete the request form.',
                    'Can I check existing reservations first?' => 'Yes. Use the booking calendar on this dashboard to review scheduled reservations.',
                    'How will I know if my request is approved?' => 'SIEL SPACE will notify you when your request status changes.',
                    'How far in advance should I book?' => 'Submit your request as early as possible. Requests are handled first-come, first-served.',
                ] as $question => $answer)
                    <details class="group rounded-xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-black text-emerald-950 dark:text-white">
                            {{ $question }}
                            <span class="text-emerald-700 transition group-open:rotate-180">v</span>
                        </summary>
                        <p class="mt-4 text-emerald-900/70 dark:text-zinc-300">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            window.initUserDashboard = window.initUserDashboard || function () {
                const revealElements = [
                    ...document.querySelectorAll('#home > div, #about section > div, #facilities > div, #calendar > div, #requests > div, #map > div, #help > div'),
                    ...document.querySelectorAll('.facility-card, #requests details, #help details'),
                ];

                if (!window.userDashboardRevealObserver && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    window.userDashboardRevealObserver = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            entry.target.classList.toggle('is-visible', entry.isIntersecting);
                        });
                    }, { threshold: 0.12, rootMargin: '0px 0px -48px' });
                }

                [...new Set(revealElements)].forEach((element, index) => {
                    if (element.dataset.dashboardRevealObserved) return;
                    element.dataset.dashboardRevealObserved = 'true';
                    element.classList.add('dashboard-reveal');
                    element.style.transitionDelay = `${Math.min(index % 4, 3) * 70}ms`;

                    if (window.userDashboardRevealObserver) {
                        window.userDashboardRevealObserver.observe(element);
                    } else {
                        element.classList.add('is-visible');
                    }
                });

                const searchInput = document.getElementById('facility-search');
                const capacityFilter = document.getElementById('capacity-filter');
                const customCapacity = document.getElementById('capacity-custom');
                const typeFilter = document.getElementById('type-filter');
                const cards = [...document.querySelectorAll('.facility-card')];
                const count = document.getElementById('facility-count');
                const seeMoreButton = document.getElementById('facility-see-more');
                let facilitiesExpanded = false;

                if (searchInput && capacityFilter && typeFilter && count && !searchInput.dataset.initialized) {
                    searchInput.dataset.initialized = 'true';

                    const filterFacilities = () => {
                        const search = searchInput.value.trim().toLowerCase();
                        const capacity = capacityFilter.value;
                        const requestedCapacity = Math.min(2000, Math.max(70, Number(customCapacity?.value) || 70));
                        const type = typeFilter.value;
                        const matchingCards = cards.filter((card) => {
                            const matchesSearch = !search || card.dataset.name.includes(search);
                            const matchesCapacity = capacity === 'all'
                                || (capacity === 'custom'
                                    ? Number(card.dataset.capacityValue) >= requestedCapacity
                                    : card.dataset.capacity === capacity);
                            const matchesType = type === 'all' || card.dataset.type === type;

                            return matchesSearch && matchesCapacity && matchesType;
                        });

                        cards.forEach((card) => card.classList.add('hidden'));
                        const visibleCards = facilitiesExpanded ? matchingCards : matchingCards.slice(0, 6);
                        visibleCards.forEach((card) => {
                            card.classList.remove('hidden');
                        });

                        count.textContent = visibleCards.length;

                        if (seeMoreButton) {
                            seeMoreButton.classList.toggle('hidden', matchingCards.length <= 6);
                            seeMoreButton.textContent = facilitiesExpanded
                                ? 'Show less'
                                : `See more (${matchingCards.length - 6})`;
                        }
                    };

                    const resetAndFilter = () => {
                        facilitiesExpanded = false;
                        customCapacity?.classList.toggle('hidden', capacityFilter.value !== 'custom');
                        filterFacilities();
                    };

                    searchInput.addEventListener('input', resetAndFilter);
                    capacityFilter.addEventListener('change', resetAndFilter);
                    customCapacity?.addEventListener('input', resetAndFilter);
                    typeFilter.addEventListener('change', resetAndFilter);
                    seeMoreButton?.addEventListener('click', () => {
                        facilitiesExpanded = !facilitiesExpanded;
                        filterFacilities();
                    });
                    filterFacilities();
                }

                const mapElement = document.getElementById('user-campus-map');
                if (mapElement && window.L && !mapElement.dataset.initialized) {
                    mapElement.dataset.initialized = 'true';

                    document.querySelectorAll('.map-select-chevron').forEach(chevron => {
                        const openSelect = () => {
                            const select = document.getElementById(chevron.dataset.select);
                            if (!select) return;
                            select.focus();
                            if (typeof select.showPicker === 'function') select.showPicker();
                        };
                        chevron.addEventListener('click', openSelect);
                        chevron.addEventListener('keydown', event => {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                openSelect();
                            }
                        });
                    });

                    const campusCenter = [15.7354, 120.9335];
                    // CLSU Main Gate at the campus access-road junction with Maharlika Highway.
                    const mainGateCoordinates = [15.7301879, 120.9300414];
                    const map = L.map(mapElement, {
                        scrollWheelZoom: false,
                    }).setView(campusCenter, 16);
                    mapElement.classList.remove('flex', 'items-center', 'justify-center');

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const mainGateIcon = L.divIcon({
                        className: '',
                        html: '<div style="display:grid;place-items:center;width:42px;height:42px;border:4px solid white;border-radius:9999px;background:#047857;color:white;font-size:22px;box-shadow:0 4px 14px rgba(0,0,0,.4)">&#9873;</div>',
                        iconSize: [42, 42],
                        iconAnchor: [21, 21],
                        popupAnchor: [0, -24],
                    });
                    const mainGateMarker = L.marker(mainGateCoordinates, {
                        icon: mainGateIcon,
                        title: 'CLSU Main Gate',
                        zIndexOffset: 2000,
                    }).addTo(map)
                        .bindPopup('<strong>CLSU Main Gate</strong><br><small>Main campus entrance</small>')
                        .bindTooltip('CLSU Main Gate', { permanent: true, direction: 'top', offset: [0, -22], className: 'font-bold' });

                    const facilities = @js($mapFacilities->map(fn ($facility) => [
                        'FID' => $facility->FID,
                        'Facility_Name' => $facility->Facility_Name,
                        'Location' => $facility->Location,
                        'Status' => $facility->Status,
                        'facility_type' => $facility->facility_type,
                        'Capacity' => $facility->Capacity,
                        'Latitude' => $facility->Latitude,
                        'Longitude' => $facility->Longitude,
                    ])->values());
                    const focusedFacilityId = @js($focusedFacilityId);
                    const bounds = L.latLngBounds();
                    const navigationPanel = document.getElementById('map-navigation-panel');
                    const facilitySelect = document.getElementById('map-facility-filter');
                    const facilityTypeSelect = document.getElementById('map-facility-type');
                    const selectedFacilityName = document.getElementById('map-selected-facility');
                    const selectedFacilityLocation = document.getElementById('map-selected-location');
                    const directionsButton = document.getElementById('get-facility-directions');
                    const myLocationDirectionsButton = document.getElementById('get-my-location-directions');
                    const locationStatus = document.getElementById('map-location-status');
                    const navigationLink = document.getElementById('open-navigation-link');
                    const locateMeButton = document.getElementById('dashboard-locate-me');
                    const userLocationStatus = document.getElementById('dashboard-location-status');
                    let focusedDestination = null;
                    let focusHighlight = null;
                    let guidanceLine = null;
                    let userCoordinates = null;
                    let userLocationMarker = null;
                    let userAccuracyCircle = null;
                    let locationWatchId = null;
                    let hasInitialLocation = false;
                    let lastAutomaticRouteCoordinates = null;
                    let lastAutomaticRouteAt = 0;
                    const facilityMarkers = new Map();
                    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
                    })[character]);
                    const fallbackCoordinates = facility => {
                        const hash = [...String(facility.FID ?? facility.Facility_Name)].reduce((total, character) => ((total * 31) + character.charCodeAt(0)) >>> 0, 0);
                        const angle = (hash % 360) * (Math.PI / 180);
                        const radius = 0.00035 + ((hash % 8) * 0.00012);
                        return [campusCenter[0] + Math.sin(angle) * radius, campusCenter[1] + Math.cos(angle) * radius];
                    };
                    const addFacilityMarker = (facility, coordinates, approximate = false) => {
                        const marker = L.marker(coordinates, {
                            title: facility.Facility_Name,
                        }).addTo(map).bindPopup(
                            `<strong>${escapeHtml(facility.Facility_Name)}</strong><br>` +
                            `${escapeHtml(facility.Location || 'CLSU Main Campus')}<br>` +
                            `<small>${escapeHtml(facility.facility_type || 'Facility')} · Capacity: ${escapeHtml(facility.Capacity || 'N/A')}</small><br>` +
                            `<small>${escapeHtml(facility.Status || '')}${approximate ? ' · Approximate campus pin' : ''}</small>`
                        );
                        bounds.extend(coordinates);
                        facilityMarkers.set(Number(facility.FID), { facility, coordinates, approximate, marker });
                    };

                    const showDirections = async (useMyLocation = false) => {
                        if (!focusedDestination || !directionsButton) {
                            return;
                        }

                        if (useMyLocation && !userCoordinates) {
                            if (locationStatus) locationStatus.textContent = 'Enable live location before requesting directions from your position.';
                            return;
                        }

                        const routeOrigin = useMyLocation ? userCoordinates : mainGateCoordinates;
                        const routeOriginLabel = useMyLocation ? 'your location' : 'the CLSU Main Gate';
                        if (directionsButton) directionsButton.disabled = true;
                        if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = true;
                        guidanceLine?.remove();
                        guidanceLine = null;
                        if (locationStatus) locationStatus.textContent = `Loading the walkable route from ${routeOriginLabel}...`;
                        if (navigationLink) {
                            const route = `${routeOrigin[0]},${routeOrigin[1]};${focusedDestination.coordinates[0]},${focusedDestination.coordinates[1]}`;
                            navigationLink.href = `https://www.openstreetmap.org/directions?engine=fossgis_osrm_foot&route=${encodeURIComponent(route)}`;
                            navigationLink.classList.remove('hidden');
                            navigationLink.classList.add('inline-flex');
                        }

                        if (focusedDestination.approximate) {
                            if (locationStatus) locationStatus.textContent = 'A route cannot be drawn until exact coordinates are saved for this facility.';
                            if (directionsButton) directionsButton.disabled = false;
                            return;
                        }

                        try {
                            const [destinationLatitude, destinationLongitude] = focusedDestination.coordinates;
                            const routeUrl = `https://routing.openstreetmap.de/routed-foot/route/v1/driving/${routeOrigin[1]},${routeOrigin[0]};${destinationLongitude},${destinationLatitude}?overview=full&geometries=geojson&steps=true`;
                            const response = await fetch(routeUrl, { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error(`Routing failed with status ${response.status}`);
                            const route = (await response.json()).routes?.[0];
                            if (!route?.geometry?.coordinates?.length) throw new Error('No pedestrian route returned');

                            const routeCoordinates = route.geometry.coordinates.map(([longitude, latitude]) => [latitude, longitude]);
                            guidanceLine = L.polyline(routeCoordinates, {
                                color: '#2563eb',
                                opacity: 0.9,
                                weight: 6,
                            }).addTo(map);
                            map.fitBounds(guidanceLine.getBounds().pad(0.12), { maxZoom: 18 });
                            if (locationStatus) {
                                const routeDistance = route.distance < 1000
                                    ? `${Math.round(route.distance)} m`
                                    : `${(route.distance / 1000).toFixed(1)} km`;
                                const routeMinutes = Math.max(1, Math.round(route.duration / 60));
                                locationStatus.textContent = `Walkable route from ${routeOriginLabel}: ${routeDistance}, about ${routeMinutes} minutes.`;
                            }
                        } catch {
                            if (locationStatus) {
                                locationStatus.textContent = 'A verified walkable pathway could not be loaded. No artificial straight-line route was drawn.';
                            }
                            map.fitBounds(L.latLngBounds([routeOrigin, focusedDestination.coordinates]).pad(0.2), { maxZoom: 18 });
                        } finally {
                            if (directionsButton) directionsButton.disabled = false;
                            if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = !userCoordinates;
                        }

                    };

                    locateMeButton?.addEventListener('click', () => {
                        if (!navigator.geolocation) {
                            if (userLocationStatus) userLocationStatus.textContent = 'Location services are not supported by this browser.';
                            return;
                        }

                        if (locationWatchId !== null) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                            locateMeButton.innerHTML = '<span aria-hidden="true">⌖</span> Follow my location';
                            if (userLocationStatus) userLocationStatus.textContent = 'Live location tracking paused.';
                            return;
                        }

                        locateMeButton.disabled = true;
                        if (userLocationStatus) userLocationStatus.textContent = 'Finding your current location...';
                        hasInitialLocation = false;

                        locationWatchId = navigator.geolocation.watchPosition(
                            ({ coords }) => {
                                userCoordinates = [coords.latitude, coords.longitude];

                                if (userAccuracyCircle) {
                                    userAccuracyCircle.setLatLng(userCoordinates).setRadius(coords.accuracy);
                                } else {
                                    userAccuracyCircle = L.circle(userCoordinates, {
                                        radius: coords.accuracy,
                                        color: '#047857',
                                        fillColor: '#10b981',
                                        fillOpacity: 0.12,
                                        weight: 2,
                                    }).addTo(map);
                                }

                                if (userLocationMarker) {
                                    userLocationMarker.setLatLng(userCoordinates);
                                } else {
                                    userLocationMarker = L.circleMarker(userCoordinates, {
                                        radius: 9,
                                        color: '#ffffff',
                                        fillColor: '#047857',
                                        fillOpacity: 1,
                                        weight: 3,
                                    }).addTo(map).bindPopup('<strong>Your live location</strong>').openPopup();
                                }

                                locateMeButton.innerHTML = '<span aria-hidden="true">●</span> Stop following';
                                locateMeButton.disabled = false;
                                if (myLocationDirectionsButton && focusedDestination && !focusedDestination.approximate) {
                                    myLocationDirectionsButton.disabled = false;
                                }
                                if (userLocationStatus) userLocationStatus.textContent = `Following your location within approximately ${Math.round(coords.accuracy)} meters.`;

                                map.setView(userCoordinates, Math.max(map.getZoom(), 18), { animate: true });

                                const now = Date.now();
                                const movedDistance = lastAutomaticRouteCoordinates
                                    ? map.distance(lastAutomaticRouteCoordinates, userCoordinates)
                                    : Infinity;
                                if (
                                    focusedDestination
                                    && !focusedDestination.approximate
                                    && movedDistance >= 15
                                    && now - lastAutomaticRouteAt >= 15000
                                ) {
                                    lastAutomaticRouteCoordinates = [...userCoordinates];
                                    lastAutomaticRouteAt = now;
                                    showDirections(true);
                                }

                                hasInitialLocation = true;
                            },
                            (error) => {
                                const messages = {
                                    1: 'Location permission was denied. Allow it in your browser and try again.',
                                    2: 'Your location is currently unavailable. Please try again.',
                                    3: 'Finding your location took too long. Please try again.',
                                };
                                if (userLocationStatus) userLocationStatus.textContent = messages[error.code] || 'Your location could not be found.';
                                locateMeButton.disabled = false;
                                locateMeButton.innerHTML = '<span aria-hidden="true">⌖</span> Follow my location';
                                if (locationWatchId !== null) navigator.geolocation.clearWatch(locationWatchId);
                                locationWatchId = null;
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 },
                        );
                    });

                    directionsButton?.addEventListener('click', () => showDirections(false));
                    myLocationDirectionsButton?.addEventListener('click', () => showDirections(true));
                    const selectFacility = facilityId => {
                        focusHighlight?.remove();
                        focusHighlight = null;
                        guidanceLine?.remove();
                        guidanceLine = null;
                        map.closePopup();

                        if (facilityId === 'all') {
                            focusedDestination = null;
                            if (directionsButton) directionsButton.disabled = true;
                            if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = true;
                            if (locationStatus) locationStatus.textContent = 'Select a facility to view directions.';
                            navigationPanel?.classList.add('hidden');
                            navigationLink?.classList.add('hidden');
                            navigationLink?.classList.remove('inline-flex');
                            const selectedType = facilityTypeSelect?.value || 'all';
                            const visibleBounds = L.latLngBounds();
                            facilityMarkers.forEach(({ facility, coordinates, marker }) => {
                                const matchesType = selectedType === 'all' || (facility.facility_type || 'Other') === selectedType;
                                if (matchesType && !map.hasLayer(marker)) marker.addTo(map);
                                if (!matchesType && map.hasLayer(marker)) map.removeLayer(marker);
                                marker.setOpacity(1);
                                marker.setZIndexOffset(0);
                                if (matchesType) visibleBounds.extend(coordinates);
                            });
                            if (visibleBounds.isValid()) map.fitBounds(visibleBounds.pad(0.18), { maxZoom: 17 });
                            return;
                        }

                        const selected = facilityMarkers.get(Number(facilityId));
                        if (!selected) return;
                        focusedDestination = selected;
                        navigationLink?.classList.add('hidden');
                        navigationLink?.classList.remove('inline-flex');
                        if (directionsButton) directionsButton.disabled = selected.approximate;
                        if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = selected.approximate || !userCoordinates;
                        if (locationStatus) {
                            locationStatus.textContent = selected.approximate
                                ? 'Directions require an exact saved facility location.'
                                : 'Ready to show directions from the CLSU Main Gate.';
                        }
                        facilityMarkers.forEach(({ marker }, id) => {
                            if (!map.hasLayer(marker)) marker.addTo(map);
                            marker.setOpacity(id === Number(facilityId) ? 1 : 0.35);
                            marker.setZIndexOffset(id === Number(facilityId) ? 1000 : 0);
                        });
                        focusHighlight = L.circle(selected.coordinates, {
                            radius: 28,
                            color: '#f59e0b',
                            fillColor: '#fbbf24',
                            fillOpacity: 0.3,
                            weight: 5,
                        }).addTo(map);
                        if (selectedFacilityName) selectedFacilityName.textContent = selected.facility.Facility_Name;
                        if (selectedFacilityLocation) selectedFacilityLocation.textContent = `${selected.facility.Location || 'CLSU Main Campus'} · ${selected.facility.facility_type || 'Facility'} · Capacity: ${selected.facility.Capacity || 'N/A'}`;
                        map.setView(selected.coordinates, 19);
                        selected.marker.openPopup();
                        if (!selected.approximate) {
                            if (userCoordinates) {
                                lastAutomaticRouteCoordinates = [...userCoordinates];
                                lastAutomaticRouteAt = Date.now();
                                showDirections(true);
                            } else {
                                showDirections(false);
                            }
                        }
                    };

                    const populateFacilityFilters = () => {
                        const availableFacilities = facilities.filter(facility => facility.Status === 'Available' || Number(facility.FID) === Number(focusedFacilityId));
                        const facilityTypes = [...new Set(availableFacilities.map(facility => facility.facility_type || 'Other'))].sort();
                        facilityTypes.forEach(type => facilityTypeSelect?.add(new Option(type, type)));

                        const updateFacilityOptions = () => {
                            if (!facilitySelect) return;
                            const selectedType = facilityTypeSelect?.value || 'all';
                            facilitySelect.replaceChildren(new Option('All Facilities', 'all'));
                            availableFacilities
                                .filter(facility => selectedType === 'all' || (facility.facility_type || 'Other') === selectedType)
                                .sort((left, right) => left.Facility_Name.localeCompare(right.Facility_Name))
                                .forEach(facility => facilitySelect.add(new Option(facility.Facility_Name, facility.FID)));
                        };

                        facilityTypeSelect?.addEventListener('change', () => {
                            updateFacilityOptions();
                            selectFacility('all');
                        });
                        facilitySelect?.addEventListener('change', event => selectFacility(event.target.value));
                        updateFacilityOptions();
                    };
                    const locateFacilities = async () => {
                        for (const facility of facilities) {
                            const savedLatitude = Number(facility.Latitude);
                            const savedLongitude = Number(facility.Longitude);
                            if (
                                Number.isFinite(savedLatitude)
                                && Number.isFinite(savedLongitude)
                                && savedLatitude !== 0
                                && savedLongitude !== 0
                            ) {
                                addFacilityMarker(facility, [savedLatitude, savedLongitude]);
                                continue;
                            }

                            const cacheKey = `clsu-facility-map-${facility.FID}-${facility.Location || ''}`;
                            let cached = null;

                            try {
                                cached = JSON.parse(localStorage.getItem(cacheKey) || 'null');
                            } catch {
                                localStorage.removeItem(cacheKey);
                            }

                            if (cached?.length === 2) {
                                addFacilityMarker(facility, cached);
                                continue;
                            }

                            const query = [facility.Facility_Name, facility.Location, 'Central Luzon State University', 'Science City of Muñoz', 'Nueva Ecija'].filter(Boolean).join(', ');

                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`, {
                                    headers: { 'Accept': 'application/json' },
                                });
                                if (!response.ok) throw new Error(`Geocoding failed with status ${response.status}`);
                                const result = (await response.json())[0];
                                const coordinates = result ? [Number(result.lat), Number(result.lon)] : fallbackCoordinates(facility);
                                try {
                                    localStorage.setItem(cacheKey, JSON.stringify(coordinates));
                                } catch {
                                    // The map still works when browser storage is unavailable.
                                }
                                addFacilityMarker(facility, coordinates, !result);
                            } catch {
                                addFacilityMarker(facility, fallbackCoordinates(facility), true);
                            }

                            await new Promise(resolve => setTimeout(resolve, 1050));
                        }

                        populateFacilityFilters();

                        if (focusedFacilityId && facilityMarkers.has(Number(focusedFacilityId))) {
                            if (facilitySelect) facilitySelect.value = String(focusedFacilityId);
                            selectFacility(String(focusedFacilityId));
                        } else {
                            selectFacility('all');
                        }
                    };

                    locateFacilities();

                    setTimeout(() => map.invalidateSize(), 100);
                }
            };

            document.addEventListener('DOMContentLoaded', window.initUserDashboard);
            document.addEventListener('livewire:navigated', window.initUserDashboard);
            window.addEventListener('pageshow', window.initUserDashboard);
        </script>
    @endpush
</x-layouts.home.header>
