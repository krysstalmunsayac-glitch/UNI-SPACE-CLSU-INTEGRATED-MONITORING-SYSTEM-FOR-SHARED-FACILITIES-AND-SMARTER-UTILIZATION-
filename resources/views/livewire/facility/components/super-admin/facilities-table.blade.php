<x-ui::card>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-ui::heading size="lg">Facilities</x-ui::heading>

        <div class="flex w-full flex-wrap gap-2 lg:w-auto lg:justify-end">
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
                                <label class="sr-only" for="request-facility-search">Search facilities</label>
                                <input
                                    id="request-facility-search"
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

            <x-ui::button
                wire:click="create"
                icon="plus"
                variant="primary"
            >
                Add Facility
            </x-ui::button>
        </div>
    </div>

    <x-ui::table :paginate="$this->facilities">
        <x-ui::table.columns>
            <x-ui::table.column sortable :sorted="$sortBy === 'Facility_Name'" :direction="$sortDirection" wire:click="sort('Facility_Name')">
                Facility
            </x-ui::table.column>
            <x-ui::table.column sortable :sorted="$sortBy === 'facility_type'" :direction="$sortDirection" wire:click="sort('facility_type')">
                Facility type
            </x-ui::table.column>
            <x-ui::table.column>Capacity</x-ui::table.column>
            <x-ui::table.column>Office</x-ui::table.column>
            <x-ui::table.column>Rates</x-ui::table.column>
            <x-ui::table.column class="min-w-32 whitespace-nowrap" sortable :sorted="$sortBy === 'Status'" :direction="$sortDirection" wire:click="sort('Status')">
                Status
            </x-ui::table.column>
            <x-ui::table.column>Actions</x-ui::table.column>
        </x-ui::table.columns>

        <x-ui::table.rows>
            @forelse ($this->facilities as $facility)
                <x-ui::table.row wire:key="facility-{{ $facility->FID }}">
                    <x-ui::table.cell>
                        <div class="flex items-center gap-3">
                            @if ($facility->images->isNotEmpty() || $facility->Image_URL)
                                <x-ui::avatar size="xs" :src="$facility->primaryImageUrl()" />
                            @else
                                <x-ui::avatar size="xs" :name="$facility->Facility_Name" />
                            @endif

                            <div>
                                <div class="font-medium">{{ $facility->Facility_Name }}</div>
                                <div class="text-xs text-zinc-500">FAC-{{ str_pad((string) $facility->FID, 5, '0', STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                    </x-ui::table.cell>

                    <x-ui::table.cell>
                        <x-ui::badge size="sm" color="zinc">
                            {{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Not specified' }}
                        </x-ui::badge>
                    </x-ui::table.cell>
                    <x-ui::table.cell>{{ $facility->Capacity ?? '—' }}</x-ui::table.cell>
                    <x-ui::table.cell>{{ $facility->Office ?? '—' }}</x-ui::table.cell>
                    <x-ui::table.cell>
                        <p class="max-w-56 line-clamp-3 whitespace-pre-line text-xs leading-5 text-zinc-600 dark:text-zinc-300">{{ $facility->rates ?: '—' }}</p>
                    </x-ui::table.cell>

                    <x-ui::table.cell class="min-w-32 whitespace-nowrap">
                        <button
                            type="button"
                            wire:click="requestToggleStatus({{ $facility->FID }})"
                            wire:loading.attr="disabled"
                            wire:target="requestToggleStatus({{ $facility->FID }})"
                            class="group rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-900"
                            aria-label="Change {{ $facility->Facility_Name }} status from {{ $facility->Status }} to {{ $facility->Status === 'Unavailable' ? 'Available' : 'Unavailable' }}"
                            title="Click to mark as {{ $facility->Status === 'Unavailable' ? 'available' : 'unavailable' }}"
                        >
                            <span
                                @class([
                                    'inline-flex min-w-24 items-center justify-center whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none text-white transition-colors',
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
                    </x-ui::table.cell>

                    <x-ui::table.cell>
                        <div class="flex items-center justify-end gap-2">
                            <x-ui::dropdown :position="$loop->remaining < 2 ? 'top' : 'bottom'" align="end">
                                <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <x-ui::menu>
                                    <x-ui::menu.item icon="eye" wire:click="viewFacility({{ $facility->FID }})">
                                        View
                                    </x-ui::menu.item>

                                    <x-ui::menu.item icon="pencil" wire:click="edit({{ $facility->FID }})">
                                        Edit
                                    </x-ui::menu.item>

                                    <x-ui::menu.item icon="power" wire:click="requestToggleStatus({{ $facility->FID }})">
                                        {{ $facility->Status === 'Unavailable' ? 'Activate' : 'Deactivate' }}
                                    </x-ui::menu.item>

                                    <x-ui::menu.separator />

                                    <x-ui::menu.item
                                        icon="archive-box"
                                        variant="danger"
                                        wire:click="archiveFacility({{ $facility->FID }})"
                                        data-ui-confirm="Archive this facility?"
                                        data-ui-confirm-title="Confirm archive"
                                        data-ui-confirm-label="Archive facility"
                                        data-ui-confirm-variant="danger"
                                    >
                                        Archive
                                    </x-ui::menu.item>
                                </x-ui::menu>
                            </x-ui::dropdown>
                        </div>
                    </x-ui::table.cell>
                </x-ui::table.row>
            @empty
                <x-ui::table.row>
                    <x-ui::table.cell colspan="7" class="py-8 text-center">
                        No facilities found.
                    </x-ui::table.cell>
                </x-ui::table.row>
            @endforelse
        </x-ui::table.rows>
    </x-ui::table>
</x-ui::card>
