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
    @endphp

    <main class="min-h-screen bg-zinc-100 pb-20 pt-10 text-zinc-950 sm:pt-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}#facilities" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:text-emerald-900">
                <span aria-hidden="true">←</span> Back to facilities
            </a>

            <div class="mt-6 grid gap-8 lg:grid-cols-[1.15fr_.85fr] lg:items-start">
                <section
                    class="overflow-hidden rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6"
                    x-data="{ activePhoto: 0 }"
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
                        <div class="mt-5 aspect-[16/10] overflow-hidden rounded-2xl bg-zinc-200">
                            @foreach ($photos as $index => $photo)
                                <img
                                    x-show="activePhoto === {{ $index }}"
                                    src="{{ $photo }}"
                                    alt="{{ $facility->Facility_Name }} photo {{ $index + 1 }}"
                                    class="h-full w-full object-cover"
                                    @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                >
                            @endforeach
                        </div>

                        @if ($photos->count() > 1)
                            <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                                @foreach ($photos as $index => $photo)
                                    <button type="button" x-on:click="activePhoto = {{ $index }}" class="aspect-[4/3] overflow-hidden rounded-xl border-2 transition" x-bind:class="activePhoto === {{ $index }} ? 'border-emerald-700' : 'border-transparent opacity-70 hover:opacity-100'" aria-label="Show photo {{ $index + 1 }}">
                                        <img src="{{ $photo }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="mt-5 flex aspect-[16/10] items-center justify-center rounded-2xl bg-slate-200 text-center font-bold text-slate-500">
                            No facility images available
                        </div>
                    @endif
                </section>

                <article class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full bg-zinc-200 px-3 py-1 text-xs font-black uppercase tracking-wide text-zinc-700">{{ ucfirst($facility->facility_type ?: 'Facility') }}</span>
                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide text-white',
                            'bg-emerald-700' => $isAvailable,
                            'bg-red-600' => ! $isAvailable,
                        ])>{{ $isAvailable ? 'Available' : 'Unavailable' }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-black leading-tight sm:text-4xl">{{ $facility->Facility_Name }}</h1>
                    <p class="mt-4 leading-7 text-zinc-600">{{ $facility->Description ?: 'Campus facility available for reservation.' }}</p>

                    @if (! $isAvailable)
                        <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 p-4 font-semibold text-red-700">
                            <p>This facility is unavailable.</p>
                            @if ($facility->Deactivated_At)
                                <p class="mt-2 text-sm">
                                    Deactivated on {{ $facility->Deactivated_At->format('M j, Y g:i A') }}.
                                </p>
                            @endif
                            @if ($facility->Available_At)
                                <p class="mt-1 text-sm">
                                    Available again on {{ $facility->Available_At->format('M j, Y g:i A') }}.
                                </p>
                            @endif
                        </div>
                    @endif

                    <dl class="mt-7 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-emerald-50 p-4"><dt class="text-xs font-black uppercase tracking-wide text-emerald-700">Capacity</dt><dd class="mt-2 font-bold">{{ $facility->Capacity ? number_format($facility->Capacity).' people' : 'Not specified' }}</dd></div>
                        <div class="rounded-2xl bg-emerald-50 p-4"><dt class="text-xs font-black uppercase tracking-wide text-emerald-700">Location</dt><dd class="mt-2 font-bold">{{ $facility->Location ?: 'Not specified' }}</dd></div>
                        <div class="rounded-2xl bg-emerald-50 p-4 sm:col-span-2"><dt class="text-xs font-black uppercase tracking-wide text-emerald-700">Managing office</dt><dd class="mt-2 font-bold">{{ $facility->Office ?: 'Not specified' }}</dd></div>
                    </dl>

                    <div class="mt-7 border-t border-zinc-200 pt-6">
                        <h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Amenities</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($facility->amenities as $amenity)
                                <span class="rounded-full border border-emerald-200 px-3 py-1.5 text-sm font-bold text-emerald-800">{{ $amenity->name }} — {{ number_format($amenity->inventory_quantity) }} units</span>
                            @empty
                                <p class="text-sm text-zinc-500">No amenities listed.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-7 grid gap-4 border-t border-zinc-200 pt-6">
                        <div><h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Rates</h2><p class="mt-2 whitespace-pre-line leading-7 text-zinc-600">{{ $rate }}</p></div>
                        <div><h2 class="text-sm font-black uppercase tracking-wide text-emerald-700">Protocols and guidelines</h2><p class="mt-2 whitespace-pre-line leading-7 text-zinc-600">{{ $facility->protocols_and_guidelines ?: ($facility->Protocols ?: 'No protocols listed.') }}</p></div>
                    </div>

                    @if ($isAvailable)
                        <a href="{{ route('requests.create', $facility) }}" class="mt-8 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-6 py-3 font-black text-white transition hover:bg-emerald-800">
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
