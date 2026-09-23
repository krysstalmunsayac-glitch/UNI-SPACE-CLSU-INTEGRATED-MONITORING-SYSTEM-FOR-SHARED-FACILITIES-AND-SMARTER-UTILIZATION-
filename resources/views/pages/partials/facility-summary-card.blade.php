@php
    $capacity = (int) ($facility->Capacity ?? 0);
    $capacityGroup = $capacity > 300 ? 'large' : ($capacity > 150 ? 'medium' : ($capacity > 0 ? 'small' : 'unspecified'));
    $facilityType = strtolower($facility->facility_type ?? 'other');
    $visibleAmenities = $facility->amenities->take(3);
    $remainingAmenities = max(0, $facility->amenities->count() - $visibleAmenities->count());
    $rateSummary = filled($facility->rates)
        ? $facility->rates
        : ($facility->Price !== null
            ? ((float) $facility->Price > 0 ? '₱'.number_format((float) $facility->Price, 2) : 'No rental fee')
            : 'Rate upon inquiry');
    $facilityPhotos = $facility->images
        ->map(fn ($image) => asset('storage/'.ltrim($image->image_path, '/')))
        ->values();

    if ($facilityPhotos->isEmpty() && filled($facility->Image_URL)) {
        $facilityPhotos->push($facility->primaryImageUrl());
    }

    $isAvailable = $facility->Status === 'Available';
@endphp

<article
    class="facility-card group {{ ($hidden ?? false) ? 'hidden' : '' }} flex h-full flex-col overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-900"
    data-name="{{ strtolower($facility->Facility_Name.' '.$facility->Description.' '.$facility->Location.' '.$facility->amenities->pluck('name')->join(' ')) }}"
    data-capacity="{{ $capacityGroup }}"
    data-capacity-value="{{ $capacity }}"
    data-type="{{ $facilityType }}"
>
    <div
        class="relative aspect-[16/9] w-full shrink-0 overflow-hidden bg-slate-200 dark:bg-zinc-700"
        style="aspect-ratio: 16 / 9;"
        x-data="{
            photos: @js($facilityPhotos),
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
                this.activePhoto = (this.activePhoto - 1 + this.photos.length) % this.photos.length;
            },
            nextPhoto() {
                this.activePhoto = (this.activePhoto + 1) % this.photos.length;
            },
        }"
        x-on:keydown.escape.window="if (expanded) closePhoto()"
        x-on:keydown.left.window="if (expanded) previousPhoto()"
        x-on:keydown.right.window="if (expanded) nextPhoto()"
    >
        @if (! $isAvailable)
            <div class="absolute inset-0 z-10 bg-zinc-950/35" aria-hidden="true"></div>
        @endif

        @if ($facilityPhotos->isNotEmpty())
            <button type="button" class="block h-full w-full cursor-zoom-in" x-on:click="openPhoto()" aria-label="Expand {{ $facility->Facility_Name }} photo">
                <img
                    src="{{ $facilityPhotos->first() }}"
                    x-bind:src="photos[activePhoto]"
                    alt="{{ $facility->Facility_Name }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                    loading="lazy"
                    decoding="async"
                >
            </button>
        @else
            <div class="flex h-full w-full flex-col items-center justify-center gap-3 bg-slate-200 text-slate-500 dark:bg-zinc-700 dark:text-zinc-300" role="img" aria-label="No image available for {{ $facility->Facility_Name }}">
                <svg aria-hidden="true" class="h-11 w-11" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                    <path d="M7 21V9h10v12M9 12h2M13 12h2M9 16h2M13 16h2M8 5V3h8v2"></path>
                </svg>
                <span class="text-sm font-bold">No image available</span>
            </div>
        @endif

        <span class="absolute left-4 top-4 z-20 rounded-full bg-zinc-200 px-3 py-1 text-xs font-black uppercase tracking-wide text-zinc-700 shadow-sm ring-1 ring-black/10">
            {{ $facility->facility_type ? str($facility->facility_type)->headline() : 'Facility' }}
        </span>

        @if ($facilityPhotos->count() > 1)
            <button
                type="button"
                x-on:click.stop="previousPhoto()"
                class="absolute left-3 top-1/2 z-10 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-white bg-white text-lg font-bold text-emerald-800 shadow-md transition hover:bg-emerald-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400"
                aria-label="Show previous photo of {{ $facility->Facility_Name }}"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </button>
            <button
                type="button"
                x-on:click.stop="nextPhoto()"
                class="absolute right-3 top-1/2 z-10 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-white bg-white text-lg font-bold text-emerald-800 shadow-md transition hover:bg-emerald-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400"
                aria-label="Show next photo of {{ $facility->Facility_Name }}"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </button>

            <div class="absolute inset-x-0 bottom-3 z-10 flex items-center justify-center gap-1.5" aria-label="Choose facility photo">
                @foreach ($facilityPhotos as $photoIndex => $photo)
                    <button
                        type="button"
                        x-on:click.stop="activePhoto = {{ $photoIndex }}"
                        x-bind:class="activePhoto === {{ $photoIndex }} ? 'w-5 bg-white' : 'w-2 bg-zinc-400'"
                        class="h-2 rounded-full shadow transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400"
                        aria-label="Show photo {{ $photoIndex + 1 }} of {{ $facilityPhotos->count() }}"
                    ></button>
                @endforeach
            </div>

            <span class="absolute bottom-3 right-3 z-10 rounded-full bg-zinc-900 px-2.5 py-1 text-xs font-bold text-white shadow">
                <span x-text="activePhoto + 1"></span> / {{ $facilityPhotos->count() }}
            </span>
        @endif

        @if ($facilityPhotos->isNotEmpty())
            <template x-teleport="body">
                <div x-cloak x-show="expanded" x-transition.opacity class="fixed inset-x-0 bottom-0 top-16 z-40 flex items-center justify-center bg-emerald-950/95 px-16 py-8 sm:top-20 sm:px-24 lg:px-32" role="dialog" aria-modal="true" aria-label="Expanded {{ $facility->Facility_Name }} gallery" x-on:click.self="closePhoto()">
                    <button type="button" class="absolute right-4 top-4 z-10 inline-flex size-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-6 sm:top-6" x-on:click="closePhoto()" aria-label="Close expanded photo">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>

                    @if ($facilityPhotos->count() > 1)
                        <button type="button" class="fixed left-4 top-1/2 z-10 inline-flex size-12 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white/70 bg-black/25 text-white transition hover:border-amber-300 hover:bg-black/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 sm:left-6" x-on:click="previousPhoto()" aria-label="Show previous photo">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                        </button>
                        <button type="button" class="fixed right-4 top-1/2 z-10 inline-flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white transition hover:bg-black/55 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-6" x-on:click="nextPhoto()" aria-label="Show next photo">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        </button>
                    @endif

                    <img x-bind:src="photos[activePhoto]" alt="{{ $facility->Facility_Name }} enlarged" class="max-h-[calc(100vh-10rem)] max-w-full rounded-2xl object-contain shadow-2xl">

                    @if ($facilityPhotos->count() > 1)
                        <div class="absolute bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-black/40 px-5 py-2 text-sm font-black text-white" aria-live="polite"><span x-text="activePhoto + 1"></span> / {{ $facilityPhotos->count() }}</div>
                    @endif
                </div>
            </template>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-xl font-black leading-tight text-emerald-950 dark:text-white">{{ $facility->Facility_Name }}</h3>
        <p class="mt-2 line-clamp-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">
            {{ $facility->Description ?? 'Campus facility available for reservation.' }}
        </p>

        @if ($isAvailable)
            <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm font-black uppercase tracking-wide text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300">
                Available
            </div>
        @else
            <div class="mt-4 rounded-xl border border-red-100 bg-red-50 p-3 text-sm font-semibold text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                <span class="block font-black uppercase tracking-wide">Unavailable</span>
                @if ($facility->Available_At)
                    <span class="mt-1 block">Available again {{ $facility->Available_At->format('M j, Y g:i A') }}</span>
                @endif
            </div>
        @endif

        <dl class="mt-5">
            <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/30">
                <dt class="text-[11px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Capacity</dt>
                <dd class="mt-1 text-sm font-bold text-emerald-950 dark:text-white">
                    {{ $capacity > 0 ? number_format($capacity).' people' : 'Not specified' }}
                </dd>
            </div>
        </dl>

        <div class="mt-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Amenity</p>
            <div class="mt-2 flex min-h-7 flex-wrap gap-2">
                @forelse ($visibleAmenities as $amenity)
                    <span class="rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-zinc-950 dark:text-emerald-300">
                        {{ $amenity->name }} · {{ number_format($amenity->inventory_quantity) }}
                    </span>
                @empty
                    <span class="text-sm text-emerald-900/60 dark:text-zinc-400">No amenities listed</span>
                @endforelse

                @if ($remainingAmenities > 0)
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">
                        +{{ $remainingAmenities }} more
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 border-t border-emerald-900/10 pt-4 dark:border-white/10">
            <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Rate</p>
            <p class="mt-1 line-clamp-2 text-sm font-semibold leading-5 text-emerald-950 dark:text-white">{{ $rateSummary }}</p>
        </div>

        <div class="mt-auto grid gap-3 pt-5 sm:grid-cols-2">
            <a href="{{ route('facilities.show', $facility) }}" class="inline-flex items-center justify-center rounded-xl border-2 border-emerald-700 bg-white px-4 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                Review Details
            </a>
            @if ($isAvailable)
                <a href="{{ route('requests.create', $facility) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
                    Book
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center justify-center rounded-xl bg-zinc-200 px-4 py-3 text-sm font-bold text-zinc-500">
                    Unavailable
                </span>
            @endif
        </div>
    </div>
</article>
