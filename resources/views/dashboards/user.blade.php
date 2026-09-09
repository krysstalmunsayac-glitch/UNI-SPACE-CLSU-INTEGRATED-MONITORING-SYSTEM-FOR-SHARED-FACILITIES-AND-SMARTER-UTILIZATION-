<x-layouts.home.header>
    @php
        $heroSlides = [
            ['image' => 'images/siel-space-slide-01.jpg', 'alt' => 'CLSU athletic field and grandstand'],
            ['image' => 'images/siel-space-slide-02.jpg', 'alt' => 'CLSU auditorium viewed from the balcony'],
            ['image' => 'images/siel-space-slide-03.jpg', 'alt' => 'Rows of seats inside the CLSU auditorium'],
            ['image' => 'images/siel-space-slide-04.jpg', 'alt' => 'Central aisle and seating inside the CLSU auditorium'],
            ['image' => 'images/siel-space-slide-05.jpg', 'alt' => 'Front entrance of the CLSU auditorium'],
            ['image' => 'images/siel-space-slide-06.jpg', 'alt' => 'Angled exterior view of the CLSU auditorium'],
        ];
    @endphp

    <section
        id="home"
        class="relative min-h-[100svh] scroll-mt-20 overflow-hidden bg-zinc-950 text-white"
        x-data="{
            active: 0,
            total: {{ count($heroSlides) }},
            timer: null,
            init() { this.start(); },
            start() {
                clearInterval(this.timer);
                this.timer = setInterval(() => this.active = (this.active + 1) % this.total, 10000);
            },
            goTo(index) { this.active = index; this.start(); },
            destroy() { clearInterval(this.timer); }
        }"
    >
        @foreach ($heroSlides as $slide)
            <img
                src="{{ asset($slide['image']) }}"
                alt="{{ $slide['alt'] }}"
                class="external-hero-slide absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-1000 ease-in-out"
                style="opacity: {{ $loop->first ? '1' : '0' }}"
                x-bind:style="{ opacity: active === {{ $loop->index }} ? 1 : 0 }"
                x-bind:aria-hidden="active !== {{ $loop->index }}"
                @if ($loop->first) fetchpriority="high" @endif
            >
        @endforeach
        <div class="absolute inset-0 bg-black/55" aria-hidden="true"></div>

        <div class="relative mx-auto grid min-h-[100svh] max-w-[1536px] items-center gap-12 px-4 pb-16 pt-24 sm:px-6 lg:grid-cols-[1.25fr_.75fr] lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[.28em] text-yellow-400">External user dashboard</p>
                <h1 class="mt-4 text-5xl font-black leading-[.98] tracking-tight sm:text-6xl lg:text-7xl">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-white/85 sm:text-xl">Browse available campus spaces, check the booking calendar, and manage your reservation requests from one SIEL SPACE dashboard.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#facilities" class="inline-flex min-h-12 items-center justify-center bg-[#009639] px-7 py-3 font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-yellow-400">Browse Facilities</a>
                    <a href="#requests" class="inline-flex min-h-12 items-center justify-center border border-white bg-white px-7 py-3 font-bold text-zinc-950 transition hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">My Requests</a>
                </div>
            </div>

            <aside class="border-t-4 border-yellow-400 bg-white p-7 text-zinc-950 sm:p-8" aria-label="External user reservation tools">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#009639]">Your reservation hub</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight">Plan with confidence.</h2>
                <p class="mt-3 leading-7 text-zinc-600">Everything you need to choose a venue and follow the progress of your request.</p>
                <dl class="mt-7 grid grid-cols-2 gap-x-6 gap-y-5 border-t border-zinc-200 pt-6">
                    @foreach ([['Facilities', 'Compare spaces'], ['Calendar', 'Check schedules'], ['Requests', 'Track progress'], ['Notifications', 'Receive updates']] as [$term, $description])
                        <div><dt class="font-black text-[#009639]">{{ $term }}</dt><dd class="mt-1 text-sm text-zinc-500">{{ $description }}</dd></div>
                    @endforeach
                </dl>
            </aside>
        </div>

        <div class="absolute bottom-5 left-1/2 z-10 flex -translate-x-1/2 gap-2" role="group" aria-label="Choose hero image">
            @foreach ($heroSlides as $slide)
                <button type="button" class="h-2.5 w-8 border border-white transition-colors" x-bind:class="active === {{ $loop->index }} ? 'bg-yellow-400' : 'bg-white/40 hover:bg-white'" x-on:click="goTo({{ $loop->index }})" aria-label="Show image {{ $loop->iteration }} of {{ count($heroSlides) }}" x-bind:aria-current="active === {{ $loop->index }} ? 'true' : null"></button>
            @endforeach
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
                    @include('pages.partials.facility-summary-card', [
                        'facility' => $facility,
                        'hidden' => $loop->index >= 6,
                    ])
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
            .external-hero-slide {
                transition: none;
            }

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
                    ...document.querySelectorAll('#about section > div, #facilities > div, #calendar > div, #requests > div, #map > div, #help > div'),
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
