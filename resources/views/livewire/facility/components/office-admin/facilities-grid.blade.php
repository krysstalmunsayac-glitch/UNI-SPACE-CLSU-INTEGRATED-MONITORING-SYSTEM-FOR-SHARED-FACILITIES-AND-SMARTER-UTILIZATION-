<x-ui::card>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-ui::heading size="lg">Assigned Facilities</x-ui::heading>

        @if ($this->requestableFacilities->isNotEmpty())
            <x-ui::dropdown position="bottom" align="end">
                <x-ui::button variant="primary" icon="calendar-days">
                    Request Facility
                </x-ui::button>

                <x-ui::menu
                    class="overflow-hidden! rounded-2xl! p-0! shadow-xl!"
                    style="width: 24rem; max-width: calc(100vw - 2rem); max-height: min(22rem, calc(100vh - 2rem));"
                >
                    <div x-data="{ facilitySearch: '' }" class="flex max-h-[22rem] flex-col overflow-hidden">
                        <div class="border-b border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <label class="sr-only" for="office-request-facility-search">Search facilities</label>
                            <input
                                id="office-request-facility-search"
                                x-model="facilitySearch"
                                x-on:click.stop
                                type="search"
                                placeholder="Search facilities..."
                                class="h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 dark:border-zinc-600 dark:bg-zinc-900"
                            >
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto p-2">
                            @foreach ($this->requestableFacilities as $requestableFacility)
                                @if ($requestableFacility->Status === 'Available')
                                    <x-ui::menu.item
                                        icon="calendar-days"
                                        href="{{ route('admin.requests.create', $requestableFacility) }}"
                                        class="min-w-0! rounded-xl! py-2.5!"
                                        x-show="facilitySearch === '' || @js(strtolower($requestableFacility->Facility_Name.' '.$requestableFacility->Office)).includes(facilitySearch.toLowerCase())"
                                    >
                                        <span class="block min-w-0 whitespace-normal">
                                            <span class="block break-words font-semibold leading-5">{{ $requestableFacility->Facility_Name }}</span>
                                            @if ($requestableFacility->Office)
                                                <span class="mt-0.5 block break-words text-xs leading-4 text-zinc-500 dark:text-zinc-400">{{ $requestableFacility->Office }}</span>
                                            @endif
                                        </span>
                                    </x-ui::menu.item>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        class="flex min-h-9 w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm opacity-60"
                                        x-show="facilitySearch === '' || @js(strtolower($requestableFacility->Facility_Name.' '.$requestableFacility->Office)).includes(facilitySearch.toLowerCase())"
                                    >
                                        <x-ui::icon.calendar-days class="size-5 shrink-0" />
                                        <span class="block min-w-0 whitespace-normal">
                                            <span class="block break-words font-semibold leading-5">{{ $requestableFacility->Facility_Name }}</span>
                                            <span class="mt-0.5 block break-words text-xs leading-4 text-red-600 dark:text-red-300">
                                                Unavailable{{ $requestableFacility->Available_At ? ' until '.$requestableFacility->Available_At->format('M j, Y g:i A') : '' }}
                                            </span>
                                        </span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </x-ui::menu>
            </x-ui::dropdown>
        @else
            <x-ui::button variant="primary" icon="calendar-days" disabled title="No facilities are currently available">
                Request Facility
            </x-ui::button>
        @endif
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->facilities as $facility)
            <x-ui::card wire:key="assigned-facility-{{ $facility->FID }}" class="flex flex-col gap-0 overflow-hidden p-0">
                <div class="relative h-36 w-full bg-slate-100 dark:bg-slate-800">
                    @if ($facility->images->isNotEmpty() || $facility->Image_URL)
                        <img
                            src="{{ $facility->primaryImageUrl() }}"
                            class="h-full w-full object-cover"
                            alt="{{ $facility->Facility_Name }}"
                            loading="lazy"
                            decoding="async"
                        />
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <x-ui::icon.building-office class="size-10 text-slate-300 dark:text-slate-600" />
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:click="requestToggleStatus({{ $facility->FID }})"
                        wire:loading.attr="disabled"
                        wire:target="requestToggleStatus({{ $facility->FID }})"
                        class="group absolute right-3 top-3 rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-slate-800"
                        aria-label="Change {{ $facility->Facility_Name }} status from {{ $facility->Status }} to {{ $facility->Status === 'Unavailable' ? 'Available' : 'Unavailable' }}"
                        title="Click to mark as {{ $facility->Status === 'Unavailable' ? 'available' : 'unavailable' }}"
                    >
                        <span
                            @class([
                                'inline-flex min-w-24 items-center justify-center whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none text-white shadow-sm transition-colors',
                                'bg-emerald-700 group-hover:bg-red-600' => $facility->Status === 'Available',
                                'bg-red-600 group-hover:bg-emerald-700' => $facility->Status === 'Unavailable',
                                'bg-zinc-600' => ! in_array($facility->Status, ['Available', 'Unavailable'], true),
                            ])
                        >
                            {{ $facility->Status ?: 'Not specified' }}
                        </span>
                        @if ($facility->Status === 'Unavailable' && $facility->Deactivated_At)
                            <span class="mt-1 block text-xs font-semibold text-red-700 dark:text-red-300">
                                Deactivated {{ $facility->Deactivated_At->format('M j, Y g:i A') }}
                            </span>
                        @endif
                        @if ($facility->Status === 'Unavailable' && $facility->Available_At)
                            <span class="mt-0.5 block text-xs font-semibold text-red-600 dark:text-red-300">
                                Until {{ $facility->Available_At->format('M j, Y g:i A') }}
                            </span>
                        @endif
                    </button>
                </div>

                <div class="flex flex-col gap-3 px-5 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <x-ui::heading size="lg">{{ $facility->Facility_Name }}</x-ui::heading>
                            <x-ui::text size="sm" variant="subtle">#{{ $facility->FID }}</x-ui::text>
                        </div>

                    </div>

                    <x-ui::separator />

                    <div class="grid grid-cols-1 gap-2 text-sm">
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.tag class="size-4 text-slate-400" />
                            <span>{{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Type not specified' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.users class="size-4 text-slate-400" />
                            <span>{{ $facility->Capacity !== null ? $facility->Capacity.' capacity' : '—' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.map-pin class="size-4 text-slate-400" />
                            <span>{{ $facility->Location ?? '—' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.briefcase class="size-4 text-slate-400" />
                            <span>{{ $facility->Office ?? '—' }}</span>
                        </div>
                    </div>

                    @if ($facility->Status === 'Unavailable' && ($facility->Deactivated_At || $facility->Available_At))
                        <div class="flex items-center gap-2 text-sm font-semibold text-red-600 dark:text-red-300">
                            <x-ui::icon.calendar-days class="size-4" />
                            <span>
                                @if ($facility->Deactivated_At)
                                    Deactivated {{ $facility->Deactivated_At->format('M j, Y g:i A') }}
                                @endif
                                @if ($facility->Deactivated_At && $facility->Available_At)
                                    ·
                                @endif
                                @if ($facility->Available_At)
                                    Available again {{ $facility->Available_At->format('M j, Y g:i A') }}
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="grid gap-3 border-t border-slate-200 pt-3 text-xs dark:border-slate-700">
                        <div>
                            <p class="font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Rates</p>
                            <p class="mt-1 line-clamp-2 whitespace-pre-line leading-5 text-slate-600 dark:text-slate-300">{{ $facility->rates ?: 'No rate information provided.' }}</p>
                        </div>
                        <div>
                            <p class="font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Protocols</p>
                            <p class="mt-1 line-clamp-2 whitespace-pre-line leading-5 text-slate-600 dark:text-slate-300">{{ $facility->protocols_and_guidelines ?: 'No protocols provided.' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <x-ui::button
                            size="sm"
                            variant="primary"
                            wire:click="edit({{ $facility->FID }})"
                            class="w-full"
                        >
                            Edit facility
                        </x-ui::button>
                        <x-ui::button
                            size="sm"
                            :variant="$facility->Status === 'Unavailable' ? 'primary' : 'danger'"
                            wire:click="requestToggleStatus({{ $facility->FID }})"
                            class="w-full"
                        >
                            {{ $facility->Status === 'Unavailable' ? 'Activate Facility' : 'Deactivate Facility' }}
                        </x-ui::button>
                    </div>
                </div>
            </x-ui::card>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                {{ $search !== '' || $statusFilter !== '' ? 'No assigned facilities match your search or availability filter.' : 'No facilities assigned to your account.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->facilities->links() }}
    </div>
</x-ui::card>
