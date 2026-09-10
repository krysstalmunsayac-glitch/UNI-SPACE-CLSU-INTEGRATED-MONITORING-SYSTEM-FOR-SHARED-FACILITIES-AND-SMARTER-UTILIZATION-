<x-layouts.home.header>
    <style>
        #facility-grid .facility-card { border-radius: 1rem; box-shadow: none; }
        #facility-grid .facility-card:hover { border-color: #009639; box-shadow: 0 10px 24px rgba(24, 24, 27, .08); }
        #map { background: #f4f4f5; }
        #map .campus-map-layout > div { border-radius: 1rem; }
        #map .campus-map-layout > div:last-child { box-shadow: none; }
    </style>
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

        <div class="relative mx-auto grid min-h-[100svh] max-w-7xl items-center gap-8 px-4 pb-16 pt-24 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[.28em] text-yellow-400">External user dashboard</p>
                <h1 class="mt-4 text-5xl font-black leading-[.98] tracking-tight sm:text-6xl lg:text-7xl">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-white/85 sm:text-xl">Browse available campus spaces, check the booking calendar, and manage your reservation requests from one SIEL SPACE dashboard.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#facilities" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#009639] px-7 py-3 font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-yellow-400">Browse Facilities</a>
                    <a href="#requests" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white bg-white px-7 py-3 font-bold text-zinc-950 transition hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">My Requests</a>
                </div>
            </div>

            <aside class="overflow-hidden rounded-2xl border-t-4 border-yellow-400 bg-white p-7 text-zinc-950 sm:p-8" aria-label="External user reservation tools">
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
                <button type="button" class="h-2.5 w-8 rounded-full border border-white transition-colors" x-bind:class="active === {{ $loop->index }} ? 'bg-yellow-400' : 'bg-white/40 hover:bg-white'" x-on:click="goTo({{ $loop->index }})" aria-label="Show image {{ $loop->iteration }} of {{ count($heroSlides) }}" x-bind:aria-current="active === {{ $loop->index }} ? 'true' : null"></button>
            @endforeach
        </div>
    </section>

    <section id="about" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[.75fr_1.25fr] lg:items-end">
                <div><p class="text-sm font-black uppercase tracking-[.2em] text-[#009639]">Simple reservations</p><h2 class="mt-3 text-4xl font-black tracking-tight text-zinc-950 sm:text-5xl">How SIEL Space works</h2></div>
                <p class="max-w-2xl text-lg leading-8 text-zinc-600">Spend less time searching for venues and more time preparing your activity. SIEL Space brings facility information and booking schedules together.</p>
            </div>
            <div class="mt-12 grid border-y border-zinc-200 md:grid-cols-3">
                @foreach ([['01', 'Find', 'Browse campus venues and compare their capacity, location, amenities, and rate.'], ['02', 'Check Availability', 'Use the calendar to confirm that your preferred date and time are open.'], ['03', 'Send Request', 'Choose a facility, complete the required details, and track the request status.']] as [$number, $title, $description])
                    <article class="border-b border-zinc-200 py-8 md:border-b-0 md:border-l md:px-8 md:first:border-l-0 md:first:pl-0">
                        <p class="text-sm font-black text-[#009639]">{{ $number }}</p><h3 class="mt-4 text-2xl font-black text-zinc-950">{{ $title }}</h3><p class="mt-3 leading-7 text-zinc-600">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="facilities" class="scroll-mt-20 bg-zinc-100 py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($facilityCategories->isNotEmpty())
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div><p class="text-sm font-black uppercase tracking-[.2em] text-[#009639]">Explore the campus</p><h2 class="mt-3 text-4xl font-black tracking-tight text-zinc-950 sm:text-5xl">Browse by facility type</h2></div>
                    <p class="max-w-md text-zinc-600">Choose a category to narrow the available spaces shown below.</p>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ($facilityCategories as $category)
                        <button type="button" data-category-filter="{{ $category['type'] }}" class="group relative min-h-52 overflow-hidden rounded-2xl border border-zinc-300 bg-zinc-900 text-left focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            <img src="{{ $category['image'] }}" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            <span class="absolute inset-0 bg-black/50 transition group-hover:bg-black/65" aria-hidden="true"></span>
                            <span class="absolute inset-x-0 bottom-0 block p-5 text-white"><span class="block text-xl font-black leading-tight">{{ $category['name'] }}</span><span class="mt-2 block text-sm font-semibold text-yellow-300">{{ $category['count'] }} {{ Str::plural('space', $category['count']) }}</span></span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="mt-20 border-t border-zinc-300 pt-14">
                <p class="text-sm font-black uppercase tracking-[.2em] text-[#009639]">Facility directory</p><h2 class="mt-3 text-4xl font-black tracking-tight text-zinc-950 sm:text-5xl">Available facilities</h2><p class="mt-4 max-w-2xl text-lg text-zinc-600">Compare spaces using their essential details, then book the one that fits your activity.</p>
            </div>
            <div class="mt-10 grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 lg:grid-cols-[1fr_190px_220px]">
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-zinc-700">Search</span>
                    <span class="relative block">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-emerald-700 dark:text-emerald-300">⌕</span>
                        <input id="facility-search" type="search" placeholder="Search facilities..." class="h-14 w-full rounded-xl border border-zinc-300 bg-white pl-12 pr-4 text-zinc-950 outline-none focus:border-[#009639] focus:ring-2 focus:ring-[#009639]/15">
                    </span>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-zinc-700">Capacity</span>
                    <span class="relative block">
                        <select id="capacity-filter" class="h-14 w-full appearance-none rounded-xl border border-zinc-300 bg-white px-4 pr-11 font-semibold text-zinc-950 outline-none focus:border-[#009639] focus:ring-2 focus:ring-[#009639]/15">
                            <option value="all">All capacities</option>
                            <option value="small">70-150</option>
                            <option value="medium">151-300</option>
                            <option value="large">301+</option>
                            <option value="custom">Other / Specific capacity</option>
                        </select>

                    </span>
                    <input id="capacity-custom" type="number" min="1" max="2000" placeholder="Required capacity" class="mt-2 hidden h-12 w-full rounded-xl border border-zinc-300 bg-white px-4 text-zinc-950 outline-none focus:border-[#009639]">
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-zinc-700">Facility type</span>
                    <span class="relative block">
                        <select id="type-filter" class="h-14 w-full appearance-none rounded-xl border border-zinc-300 bg-white px-4 pr-11 font-semibold text-zinc-950 outline-none focus:border-[#009639] focus:ring-2 focus:ring-[#009639]/15">
                            <option value="all">All facility types</option>
                            @foreach ($facilities->pluck('facility_type')->filter()->unique()->sort()->values() as $type)
                                <option value="{{ strtolower($type) }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>

                    </span>
                </label>
            </div>

            <p class="mt-5 text-sm font-bold text-zinc-600"><span id="facility-count">{{ min(6, $facilities->count()) }}</span> of {{ $facilities->count() }} facilities shown</p>

            <div id="facility-grid" class="mt-8 grid gap-7 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($facilities as $facility)
                    @include('pages.partials.facility-summary-card', [
                        'facility' => $facility,
                        'hidden' => $loop->index >= 6,
                    ])
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center text-zinc-600">
                        No facilities are currently available for reservation.
                    </div>
                @endforelse
            </div>

            @if ($facilities->count() > 6)
                <div class="mt-10 flex justify-center">
                    <button
                        id="facility-see-more"
                        type="button"
                        class="rounded-xl border-2 border-[#009639] bg-white px-7 py-3 text-sm font-black text-[#007a2f] transition hover:bg-[#009639] hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-400"
                    >
                        See more
                    </button>
                </div>
            @endif
        </div>
    </section>

    <livewire:facility-request-list />

    <section id="calendar" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-black uppercase tracking-[.2em] text-[#009639]">Plan your visit</p>
            <div class="mt-3 flex flex-col justify-between gap-4 lg:flex-row lg:items-end"><h2 class="text-4xl font-black tracking-tight text-zinc-950 sm:text-5xl">Booking calendar</h2><p class="max-w-xl text-zinc-600">Check upcoming events and facility reservations before choosing your date.</p></div>
            <div class="mt-10 border-t border-zinc-200 pt-8"><x-public-booking-calendar calendar-id="user-dashboard-calendar" :events="$schedules" /></div>
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

    <section class="relative overflow-hidden bg-zinc-950 text-white">
        <img src="{{ asset('images/siel-space-slide-02.jpg') }}" alt="Interior of a CLSU auditorium" class="absolute inset-0 h-full w-full object-cover object-center">
        <div class="absolute inset-0 bg-black/65" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
            <p class="text-sm font-black uppercase tracking-[.2em] text-yellow-400">Your reservations</p><h2 class="mt-4 max-w-3xl text-4xl font-black tracking-tight sm:text-6xl">Ready to manage your requests?</h2><p class="mt-5 max-w-2xl text-lg leading-8 text-white/80">Review submitted requests, follow their status, or find another campus space for your next activity.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row"><a href="#requests" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#009639] px-7 py-3 font-bold text-white transition hover:bg-emerald-800">My Requests</a><a href="#facilities" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white bg-white px-7 py-3 font-bold text-zinc-950 transition hover:bg-yellow-400">Browse Facilities</a></div>
        </div>
    </section>

    <section id="help" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[.7fr_1.3fr] lg:px-8">
            <div><p class="text-sm font-black uppercase tracking-[.2em] text-[#009639]">Support</p><h2 class="mt-3 text-4xl font-black tracking-tight text-zinc-950">How can we help?</h2><p class="mt-5 leading-7 text-zinc-600">Central Luzon State University<br>Science City of Muñoz, Nueva Ecija 3120</p></div>
            <div class="border-t border-zinc-200">
                @foreach (['How do I reserve a facility?' => 'Choose an available facility, click Book, then complete the request form.', 'Can I check existing reservations first?' => 'Yes. Use the booking calendar on this dashboard to review scheduled reservations.', 'How will I know if my request is approved?' => 'SIEL SPACE will notify you when your request status changes.', 'How far in advance should I book?' => 'Submit your request as early as possible. Requests are handled first-come, first-served.'] as $question => $answer)
                    <details class="group border-b border-zinc-200 py-5"><summary class="flex cursor-pointer list-none items-center justify-between text-lg font-black text-zinc-950">{{ $question }}<span class="ml-4 text-[#009639] transition group-open:rotate-180">⌄</span></summary><p class="mt-3 max-w-2xl leading-7 text-zinc-600">{{ $answer }}</p></details>
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
                        const requestedCapacity = Math.min(2000, Math.max(1, Number(customCapacity?.value) || 1));
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
                    document.querySelectorAll('[data-category-filter]').forEach((button) => button.addEventListener('click', () => {
                        typeFilter.value = button.dataset.categoryFilter;
                        resetAndFilter();
                        document.querySelector('#facility-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }));
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
