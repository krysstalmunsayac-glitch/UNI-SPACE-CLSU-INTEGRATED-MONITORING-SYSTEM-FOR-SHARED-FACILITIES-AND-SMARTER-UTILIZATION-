<x-layouts.home.header>
    <section class="border-b border-emerald-900/10 bg-white dark:border-white/10 dark:bg-zinc-950">
        <div class="mx-auto grid min-h-[520px] max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">About UNI Space</p>
                <h1 class="mt-4 text-5xl font-black leading-tight tracking-tight text-emerald-950 dark:text-white sm:text-6xl">
                    A smarter way to manage campus spaces
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-emerald-900/70 dark:text-zinc-300">
                    UNI Space is a facility reservation platform built for Central Luzon State University. It helps students, faculty, and staff browse available spaces, submit requests, and track approvals in one organized place.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('home') }}#facilities" class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
                        Browse facilities
                    </a>
                    <a href="{{ route('home') }}#calendar" class="inline-flex items-center justify-center rounded-xl border border-emerald-700 px-6 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-zinc-900">
                        View calendar
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <img
                    src="https://placehold.co/640x780/e8f5ee/14532d?text=Campus+Facility"
                    alt="Placeholder campus facility"
                    class="h-full min-h-[360px] w-full rounded-2xl object-cover shadow-xl shadow-emerald-950/10 sm:row-span-2"
                >
                <img
                    src="https://placehold.co/520x360/dcfce7/166534?text=Study+Space"
                    alt="Placeholder study space"
                    class="h-44 w-full rounded-2xl object-cover shadow-lg shadow-emerald-950/10 sm:h-full"
                >
                <img
                    src="https://placehold.co/520x360/fef3c7/14532d?text=Event+Hall"
                    alt="Placeholder event hall"
                    class="h-44 w-full rounded-2xl object-cover shadow-lg shadow-emerald-950/10 sm:h-full"
                >
            </div>
        </div>
    </section>

    <section class="bg-emerald-50/60 py-16 dark:bg-zinc-900">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ([
                ['value' => '25+', 'label' => 'Facilities listed'],
                ['value' => '4', 'label' => 'Request statuses'],
                ['value' => '24/7', 'label' => 'Schedule visibility'],
                ['value' => '10 days', 'label' => 'Auto archive window'],
            ] as $stat)
                <div class="rounded-xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-950">
                    <p class="text-4xl font-black text-emerald-800 dark:text-emerald-300">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-sm font-semibold text-emerald-900/70 dark:text-zinc-300">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-white py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Our purpose</p>
                    <h2 class="mt-4 text-4xl font-black tracking-tight text-emerald-950 dark:text-white">Built around clear, fair, and trackable reservations</h2>
                    <p class="mt-5 text-lg leading-8 text-emerald-900/70 dark:text-zinc-300">
                        The system is designed to reduce manual coordination, keep request information complete, and give admins a faster way to review facility usage.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([
                        ['title' => 'Simple booking flow', 'text' => 'Users can choose a facility, provide event details, and submit a request with fewer back-and-forth steps.'],
                        ['title' => 'Transparent request tracking', 'text' => 'Each request shows a clear status so users know whether it is pending, approved, cancelled, or rejected.'],
                        ['title' => 'Admin-ready management', 'text' => 'Admins can review requests, check capacity, archive records, and keep facility activity organized.'],
                        ['title' => 'Campus-wide visibility', 'text' => 'The public calendar helps everyone see upcoming reservations and planned facility use.'],
                    ] as $item)
                        <article class="rounded-xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                            <h3 class="text-xl font-black text-emerald-950 dark:text-white">{{ $item['title'] }}</h3>
                            <p class="mt-3 leading-7 text-emerald-900/70 dark:text-zinc-300">{{ $item['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-emerald-900/10 bg-emerald-800 py-20 text-white dark:border-white/10">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <img
                src="https://placehold.co/900x620/064e3b/ffffff?text=Team+Placeholder"
                alt="Placeholder team working on facility reservations"
                class="h-full min-h-[320px] w-full rounded-2xl object-cover"
            >
            <div class="flex flex-col justify-center">
                <p class="text-sm font-black uppercase tracking-[0.2em] text-emerald-100">Who we serve</p>
                <h2 class="mt-4 text-4xl font-black tracking-tight">For students, faculty, staff, and campus offices</h2>
                <p class="mt-5 text-lg leading-8 text-emerald-50">
                    UNI Space keeps facility requests accessible to end users while giving office admins and super admins the tools they need to manage records responsibly.
                </p>
                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach (['Student activities', 'Academic events', 'Office meetings', 'Campus programs'] as $useCase)
                        <div class="rounded-xl bg-white/10 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/15">
                            {{ $useCase }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-20 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Placeholder team</p>
                <h2 class="mt-4 text-4xl font-black tracking-tight text-emerald-950 dark:text-white">People behind the platform</h2>
                <p class="mt-5 text-lg leading-8 text-emerald-900/70 dark:text-zinc-300">
                    Replace these sample profiles with the actual project team, office contacts, or system maintainers.
                </p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['name' => 'Juan Dela Cruz', 'role' => 'Project Lead', 'image' => 'https://placehold.co/520x520/dcfce7/14532d?text=Profile+1'],
                    ['name' => 'Maria Santos', 'role' => 'System Analyst', 'image' => 'https://placehold.co/520x520/e8f5ee/14532d?text=Profile+2'],
                    ['name' => 'Alex Reyes', 'role' => 'UI Developer', 'image' => 'https://placehold.co/520x520/fef3c7/14532d?text=Profile+3'],
                ] as $member)
                    <article class="overflow-hidden rounded-xl border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <img src="{{ $member['image'] }}" alt="{{ $member['name'] }}" class="aspect-square w-full object-cover">
                        <div class="p-5">
                            <h3 class="text-xl font-black text-emerald-950 dark:text-white">{{ $member['name'] }}</h3>
                            <p class="mt-1 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $member['role'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.home.header>
