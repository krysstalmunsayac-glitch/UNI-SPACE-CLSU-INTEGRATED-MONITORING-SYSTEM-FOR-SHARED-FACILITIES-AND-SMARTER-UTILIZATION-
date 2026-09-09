<x-layouts.home.header>
    <section id="home" class="scroll-mt-20 bg-white dark:bg-zinc-950">
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
                        class="facility-card group {{ $loop->index >= 6 ? 'hidden' : '' }} flex h-full flex-col overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-900"
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
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-xl font-black text-emerald-950 dark:text-white">{{ $facility->Facility_Name }}</h3>
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">
                                {{ $facility->Description ?? 'Campus facility available for reservation.' }}
                            </p>
                            <div class="mt-5 flex flex-wrap items-center gap-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                <span>{{ $facility->Location ?? 'Campus' }}</span>
                                <span>•</span>
                                <span>{{ $facility->Capacity ?? 'N/A' }} capacity</span>
                            </div>
                            <div class="mt-auto pt-6">
                                <a href="{{ route('requests.create', $facility) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 font-bold text-white transition hover:bg-emerald-800">
                                    Book
                                </a>
                            </div>
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
        #about {
            overflow-x: clip;
        }

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

        .dashboard-reveal.about-from-left {
            transform: translateX(-1.5rem);
        }

        .dashboard-reveal.about-from-right {
            transform: translateX(1.5rem);
        }

        .dashboard-reveal.about-from-left.is-visible,
        .dashboard-reveal.about-from-right.is-visible {
            transform: translateX(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .dashboard-reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }

    </style>

    @include('partials.campus-map')

    <section id="help" class="bg-white py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-5xl font-black tracking-tight text-emerald-950 dark:text-white">How can we help?</h2>
                <p class="mt-5 text-xl text-emerald-900/70 dark:text-zinc-300">Find answers to common questions and learn how to make the most of SIEL SPACE.</p>
            </div>

            <div class="mt-14 space-y-5">
                @foreach ([
                    'How do I reserve a facility?' => 'Choose an available facility, click Book, then complete the request form.',
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
                    ...document.querySelectorAll('#about > section:first-child > div > div, #about article, .facility-card, #requests details, #help details'),
                ];

                if (!window.userDashboardRevealObserver && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    window.userDashboardRevealObserver = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (!entry.isIntersecting) return;

                            entry.target.classList.add('is-visible');
                            window.userDashboardRevealObserver.unobserve(entry.target);
                        });
                    }, { threshold: 0.12, rootMargin: '0px 0px -48px' });
                }

                [...new Set(revealElements)].forEach((element, index) => {
                    if (element.dataset.dashboardRevealObserved) return;
                    element.dataset.dashboardRevealObserved = 'true';
                    element.classList.add('dashboard-reveal');
                    if (element.matches('#about article')) {
                        const aboutCards = [...document.querySelectorAll('#about article')];
                        element.classList.add(aboutCards.indexOf(element) % 2 === 0 ? 'about-from-left' : 'about-from-right');
                    } else if (element.matches('#about > section:first-child > div > div')) {
                        const aboutIntroColumns = [...document.querySelectorAll('#about > section:first-child > div > div')];
                        element.classList.add(aboutIntroColumns.indexOf(element) === 0 ? 'about-from-left' : 'about-from-right');
                    }
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

                @include('partials.campus-map-script')
            };

            document.addEventListener('DOMContentLoaded', window.initUserDashboard);
            document.addEventListener('livewire:navigated', window.initUserDashboard);
            window.addEventListener('pageshow', window.initUserDashboard);
        </script>
    @endpush
</x-layouts.home.header>
