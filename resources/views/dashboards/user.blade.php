<x-layouts.home.header>
    <section class="bg-gradient-to-b from-white to-emerald-50/40 dark:from-zinc-950 dark:to-emerald-950/10">
        <div class="mx-auto grid min-h-[520px] max-w-7xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <div class="mx-auto max-w-2xl lg:mx-0">
                <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">
                    External user dashboard
                </p>
                <h1 class="mt-4 text-5xl font-black leading-[0.95] tracking-tight text-emerald-950 dark:text-white sm:text-6xl lg:text-7xl">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="mt-8 max-w-xl text-xl leading-8 text-emerald-900/75 dark:text-emerald-100/80">
                    Browse available campus spaces, check the booking calendar, and send your reservation request from one familiar UNI Space dashboard.
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
                            <option value="small">1-50</option>
                            <option value="medium">51-150</option>
                            <option value="large">151+</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
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
                        $capacityGroup = $capacity > 150 ? 'large' : ($capacity > 50 ? 'medium' : 'small');
                        $facilityType = strtolower($facility->facility_type ?? 'other');
                    @endphp
                    <article
                        class="facility-card group overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-900"
                        data-name="{{ strtolower($facility->Facility_Name.' '.$facility->Description.' '.$facility->Location) }}"
                        data-capacity="{{ $capacityGroup }}"
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
        </div>
    </section>

    <section id="requests" class="border-t border-emerald-900/10 bg-emerald-50/50 py-20 dark:border-white/10 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">Dashboard</p>
                    <h2 class="mt-3 text-5xl font-black tracking-tight text-emerald-950 dark:text-white">Your facility requests</h2>
                    <p class="mt-5 max-w-3xl text-xl text-emerald-900/70 dark:text-zinc-300">
                        View the status of your submitted requests and update details from the same dashboard.
                    </p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-emerald-800 px-4 py-2 text-sm font-black text-white">
                    {{ $requests->count() }} of {{ $totalUserRequests }} submitted
                </span>
            </div>

            @if (session('success'))
                <div class="mt-8 rounded-2xl border border-emerald-200 bg-white p-4 font-semibold text-emerald-900 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="mt-8 rounded-2xl border border-yellow-300 bg-yellow-50 p-4 font-semibold text-emerald-950 shadow-sm dark:border-yellow-400/40 dark:bg-yellow-400/10 dark:text-yellow-100">
                    {{ session('warning') }}
                </div>
            @endif

            <form action="{{ route('dashboard') }}#requests" method="GET" class="mt-8 grid gap-4 rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-950 sm:grid-cols-2 lg:max-w-2xl">
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Sort by</span>
                    <span class="relative block">
                        <select name="request_sort" class="h-12 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-sm font-semibold text-emerald-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                            <option value="latest" @selected($requestSort === 'latest')>Latest first</option>
                            <option value="oldest" @selected($requestSort === 'oldest')>Oldest first</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Status</span>
                    <span class="relative block">
                        <select name="request_status" class="h-12 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-sm font-semibold text-emerald-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                            <option value="" @selected($requestStatus === '')>All statuses</option>
                            <option value="Pending" @selected($requestStatus === 'Pending')>Pending</option>
                            <option value="Approved" @selected($requestStatus === 'Approved')>Approved</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
                </label>

                <div class="flex flex-wrap gap-3 sm:col-span-2">
                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800">
                        Apply filters
                    </button>
                    @if ($requestSort !== 'latest' || $requestStatus !== '')
                        <a href="{{ route('dashboard') }}#requests" class="rounded-xl border border-emerald-900/10 bg-white px-5 py-3 text-sm font-black text-emerald-800 transition hover:bg-emerald-50 dark:border-white/10 dark:bg-zinc-900 dark:text-emerald-300 dark:hover:bg-zinc-800">
                            Clear filters
                        </a>
                    @endif
                </div>
            </form>

            <div class="mt-12 space-y-5">
                @forelse ($requests as $request)
                    @php
                        $status = $request->Status;
                        $isApproved = $status === 'Approved';
                        $isRejected = $status === 'Rejected';
                        $isCancelled = $status === 'Cancelled';
                        $needsRevision = $status === 'Pending' && filled($request->Review_Requested_At);
                        $canCancel = in_array($status, ['Pending', 'Approved'], true);
                        $statusClass = match ($status) {
                            'Approved' => 'bg-emerald-600 text-white',
                            'Rejected' => 'bg-rose-600 text-white',
                            'Cancelled' => 'bg-zinc-600 text-white',
                            default => 'bg-yellow-400 text-emerald-950',
                        };
                        $statusLabel = $needsRevision ? 'Needs Revision' : $status;
                    @endphp

                    <details class="group overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-950">
                        <summary class="flex cursor-pointer list-none flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide {{ $statusClass }}">{{ $statusLabel }}</span>
                                    <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">Request #{{ $request->RID }}</span>
                                </div>
                                <h3 class="mt-3 text-2xl font-black text-emerald-950 dark:text-white">{{ $request->facility?->Facility_Name ?? 'Facility request' }}</h3>
                                <p class="mt-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">
                                    {{ $request->event?->Event_Title ?? $request->Purpose ?? 'Submitted facility request' }}
                                </p>
                            </div>

                            <div class="lg:min-w-[520px]">
                                <p class="mb-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300 lg:text-right">
                                    Requested {{ $request->Created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
                                </p>
                                <div class="grid gap-2 text-sm font-semibold text-emerald-900/75 dark:text-zinc-300 sm:grid-cols-3 lg:text-right">
                                    <span>{{ $request->Proposed_Date?->format('M j, Y') ?? 'No date' }}</span>
                                    <span>{{ $request->Proposed_Start_Time?->format('H:i') ?? '--:--' }} - {{ $request->Proposed_End_Time?->format('H:i') ?? '--:--' }}</span>
                                    <span>{{ $request->Capacity ?? 'N/A' }} attendees</span>
                                </div>
                            </div>
                        </summary>

                        <div class="border-t border-emerald-900/10 p-6 dark:border-white/10">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="rounded-xl bg-emerald-50 p-4 dark:bg-zinc-900">
                                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Progress</p>
                                    <div class="mt-4 flex items-center gap-2 text-sm font-bold">
                                        <span class="rounded-full bg-emerald-600 px-3 py-1 text-white">Submitted</span>
                                        <span class="h-0.5 flex-1 bg-emerald-300"></span>
                                        <span class="rounded-full {{ $isApproved || $isRejected ? 'bg-emerald-600 text-white' : 'bg-yellow-400 text-emerald-950' }} px-3 py-1">Review</span>
                                        <span class="h-0.5 flex-1 {{ $isApproved ? 'bg-emerald-300' : ($isRejected ? 'bg-rose-300' : 'bg-zinc-200') }}"></span>
                                        <span class="rounded-full {{ $isApproved ? 'bg-emerald-600 text-white' : ($isRejected ? 'bg-rose-600 text-white' : ($isCancelled ? 'bg-zinc-600 text-white' : 'bg-zinc-200 text-zinc-600')) }} px-3 py-1">
                                            {{ $isRejected ? 'Rejected' : ($isCancelled ? 'Cancelled' : 'Decision') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900/80 dark:bg-zinc-900 dark:text-zinc-300 lg:col-span-2">
                                    <p><span class="font-black">Location:</span> {{ $request->facility?->Location ?? 'N/A' }}</p>
                                    <p class="mt-2"><span class="font-black">Purpose:</span> {{ $request->Purpose ?? 'N/A' }}</p>
                                    <p class="mt-2"><span class="font-black">Event type:</span> {{ $request->event?->Type_Event ?? 'N/A' }}</p>
                                    @if ($isCancelled && $request->Cancellation_Reason)
                                        <p class="mt-2"><span class="font-black">Cancellation reason:</span> {{ $request->Cancellation_Reason }}</p>
                                    @endif
                                    @if ($isRejected && $request->Rejection_Reason)
                                        <p class="mt-2"><span class="font-black">Reason for rejection:</span> {{ $request->Rejection_Reason }}</p>
                                    @endif
                                    @if ($needsRevision && $request->Review_Notes)
                                        <div class="mt-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-100">
                                            <p class="text-xs font-black uppercase tracking-wide">Information requested by the reviewer</p>
                                            <p class="mt-2 whitespace-pre-wrap font-semibold">{{ $request->Review_Notes }}</p>
                                            <p class="mt-2 text-xs">Update the fields below and save this same request. You do not need to create a new one.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <form action="{{ route('waiting.list.update', $request) }}" method="POST" enctype="multipart/form-data" class="mt-6 grid gap-4 lg:grid-cols-2">
                                @csrf
                                <input name="Event_Title" value="{{ old('Event_Title', $request->event?->Event_Title) }}" placeholder="Event title" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <select name="Type_Event" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Select event type</option>
                                    @foreach (['Meeting', 'Seminar', 'Workshop', 'Conference', 'Other'] as $type)
                                        <option value="{{ $type }}" {{ old('Type_Event', $request->event?->Type_Event) === $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                <textarea name="Description" rows="3" placeholder="Description" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white lg:col-span-2">{{ old('Description', $request->event?->Description) }}</textarea>
                                <input name="Proposed_Date" type="date" value="{{ old('Proposed_Date', $request->Proposed_Date?->toDateString()) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Capacity" type="number" min="1" value="{{ old('Capacity', $request->Capacity) }}" placeholder="Expected attendees" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Proposed_Start_Time" type="time" value="{{ old('Proposed_Start_Time', $request->Proposed_Start_Time?->format('H:i')) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Proposed_End_Time" type="time" value="{{ old('Proposed_End_Time', $request->Proposed_End_Time?->format('H:i')) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Purpose" value="{{ old('Purpose', $request->Purpose) }}" placeholder="Purpose" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <select name="Event_Status" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                    @foreach (['Upcoming', 'Ongoing', 'Completed', 'Cancelled'] as $eventStatus)
                                        <option value="{{ $eventStatus }}" {{ old('Event_Status', $request->event?->Status) === $eventStatus ? 'selected' : '' }}>{{ $eventStatus }}</option>
                                    @endforeach
                                </select>
                                <div class="lg:col-span-2">
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300" for="attachment-{{ $request->RID }}">
                                        Request letter
                                    </label>
                                    <input
                                        id="attachment-{{ $request->RID }}"
                                        name="attachment"
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-3 file:py-2 file:font-semibold file:text-emerald-800 dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:file:bg-emerald-500/20 dark:file:text-emerald-200"
                                    >
                                    <p class="mt-1 text-xs text-emerald-900/60 dark:text-zinc-400">
                                        {{ $request->attachment_path ? 'Upload a PDF only if you need to replace the current request letter.' : 'PDF — max 5 MB.' }}
                                    </p>
                                    @error('attachment') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>
                                @if ($canCancel)
                                    <div class="lg:col-span-2">
                                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300" for="Cancellation_Reason_{{ $request->RID }}">
                                            Reason for cancellation
                                        </label>
                                        <textarea
                                            id="Cancellation_Reason_{{ $request->RID }}"
                                            name="Cancellation_Reason"
                                            form="cancel-request-{{ $request->RID }}"
                                            rows="3"
                                            required
                                            minlength="5"
                                            maxlength="1000"
                                            placeholder="Please explain why you are cancelling this {{ strtolower($request->Status) }} request."
                                            class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:border-rose-500/30 dark:bg-zinc-900 dark:text-white"
                                        >{{ old('Cancellation_Reason') }}</textarea>
                                        @error('Cancellation_Reason')
                                            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                                <div class="flex flex-wrap justify-end gap-3 lg:col-span-2">
                                    @if ($canCancel)
                                        <button
                                            type="submit"
                                            form="cancel-request-{{ $request->RID }}"
                                            class="rounded-xl border border-rose-200 bg-white px-5 py-3 text-sm font-black text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/30 dark:bg-zinc-950 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                            onclick="return confirm('Cancel this facility request?')"
                                        >
                                            Cancel request
                                        </button>
                                    @endif

                                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800">
                                        Save changes
                                    </button>
                                </div>
                            </form>

                            @if ($request->Facility_ID)
                                <form action="{{ route('facility-feedback.store', $request) }}" method="POST" class="mt-6 rounded-xl border border-emerald-900/10 bg-emerald-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                                    @csrf
                                    <label class="block" for="feedback-{{ $request->RID }}">
                                        <span class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Facility feedback</span>
                                        <textarea
                                            id="feedback-{{ $request->RID }}"
                                            name="Comment"
                                            rows="3"
                                            required
                                            minlength="5"
                                            maxlength="1000"
                                            placeholder="Share your experience or comments about this facility..."
                                            class="mt-2 w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white"
                                        >{{ old('Comment') }}</textarea>
                                    </label>
                                    @error('Comment')
                                        <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="mt-3 flex justify-end">
                                        <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800">
                                            Submit feedback
                                        </button>
                                    </div>
                                </form>
                            @endif

                            @if ($canCancel)
                                <form id="cancel-request-{{ $request->RID }}" action="{{ route('waiting.list.cancel', $request) }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-10 text-center text-emerald-900 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-300">
                        {{ $totalUserRequests > 0 ? 'No requests match the selected filters.' : 'You do not have any submitted requests yet.' }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

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

    <section id="map" class="scroll-mt-28 border-t border-emerald-900/10 bg-emerald-50/50 py-20 dark:border-white/10 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:items-stretch">
                <div class="rounded-2xl bg-emerald-800 p-8 text-white">
                    <h2 class="text-4xl font-black">Campus map</h2>
                    <p class="mt-4 text-lg leading-8 text-emerald-50">
                        Find every registered facility before sending your request. Pins are generated automatically from each facility's saved location.
                    </p>

                    <div class="mt-8 space-y-3 text-sm font-semibold text-emerald-50">
                        <p>Central Luzon State University</p>
                        <p>Science City of Munoz, Nueva Ecija</p>
                    </div>
                </div>

                <div class="relative z-0 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-950">
                    <div id="user-campus-map" class="relative z-0 flex h-[440px] w-full items-center justify-center bg-emerald-50 text-sm font-bold text-emerald-900 dark:bg-zinc-900 dark:text-emerald-200">
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
                <p class="mt-5 text-xl text-emerald-900/70 dark:text-zinc-300">Find answers to common questions and learn how to make the most of UNI Space.</p>
            </div>

            <div class="mt-14 space-y-5">
                @foreach ([
                    'How do I reserve a facility?' => 'Choose an available facility, click Reserve, then complete the request form.',
                    'Can I check existing reservations first?' => 'Yes. Use the booking calendar on this dashboard to review scheduled reservations.',
                    'How will I know if my request is approved?' => 'UNI Space will notify you when your request status changes.',
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
                const searchInput = document.getElementById('facility-search');
                const capacityFilter = document.getElementById('capacity-filter');
                const typeFilter = document.getElementById('type-filter');
                const cards = [...document.querySelectorAll('.facility-card')];
                const count = document.getElementById('facility-count');

                if (searchInput && capacityFilter && typeFilter && count && !searchInput.dataset.initialized) {
                    searchInput.dataset.initialized = 'true';

                    const filterFacilities = () => {
                        const search = searchInput.value.trim().toLowerCase();
                        const capacity = capacityFilter.value;
                        const type = typeFilter.value;
                        let visible = 0;

                        cards.forEach((card) => {
                            const matchesSearch = !search || card.dataset.name.includes(search);
                            const matchesCapacity = capacity === 'all' || card.dataset.capacity === capacity;
                            const matchesType = type === 'all' || card.dataset.type === type;
                            const shouldShow = matchesSearch && matchesCapacity && matchesType;

                            card.classList.toggle('hidden', !shouldShow);
                            if (shouldShow) visible += 1;
                        });

                        count.textContent = visible;
                    };

                    searchInput.addEventListener('input', filterFacilities);
                    capacityFilter.addEventListener('change', filterFacilities);
                    typeFilter.addEventListener('change', filterFacilities);
                }

                const mapElement = document.getElementById('user-campus-map');
                if (mapElement && window.L && !mapElement.dataset.initialized) {
                    mapElement.dataset.initialized = 'true';

                    const campusCenter = [15.7354, 120.9335];
                    const map = L.map(mapElement, {
                        scrollWheelZoom: false,
                    }).setView(campusCenter, 16);
                    mapElement.classList.remove('flex', 'items-center', 'justify-center');

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const facilities = @json($mapFacilities);
                    const bounds = L.latLngBounds();
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
                        L.marker(coordinates).addTo(map).bindPopup(
                            `<strong>${escapeHtml(facility.Facility_Name)}</strong><br>` +
                            `${escapeHtml(facility.Location || 'CLSU Main Campus')}<br>` +
                            `<small>${escapeHtml(facility.Status || '')}${approximate ? ' · Approximate campus pin' : ''}</small>`
                        );
                        bounds.extend(coordinates);
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
                            const cached = JSON.parse(localStorage.getItem(cacheKey) || 'null');

                            if (cached?.length === 2) {
                                addFacilityMarker(facility, cached);
                                continue;
                            }

                            const query = [facility.Facility_Name, facility.Location, 'Central Luzon State University', 'Science City of Muñoz', 'Nueva Ecija'].filter(Boolean).join(', ');

                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`, {
                                    headers: { 'Accept': 'application/json' },
                                });
                                const result = (await response.json())[0];
                                const coordinates = result ? [Number(result.lat), Number(result.lon)] : fallbackCoordinates(facility);
                                localStorage.setItem(cacheKey, JSON.stringify(coordinates));
                                addFacilityMarker(facility, coordinates, !result);
                            } catch {
                                addFacilityMarker(facility, fallbackCoordinates(facility), true);
                            }

                            await new Promise(resolve => setTimeout(resolve, 1050));
                        }

                        if (bounds.isValid()) map.fitBounds(bounds.pad(0.18), { maxZoom: 17 });
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
