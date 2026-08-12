<x-ui::card>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-ui::heading size="lg">Facilities</x-ui::heading>

        <div class="flex w-full flex-wrap gap-2 lg:w-auto lg:justify-end">
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
            <x-ui::table.column>Location</x-ui::table.column>
            <x-ui::table.column>Office</x-ui::table.column>
            <x-ui::table.column class="min-w-32 whitespace-nowrap" sortable :sorted="$sortBy === 'Status'" :direction="$sortDirection" wire:click="sort('Status')">
                Status
            </x-ui::table.column>
            <x-ui::table.column>Actions</x-ui::table.column>
        </x-ui::table.columns>

        <x-ui::table.rows>
            @forelse ($this->facilities as $facility)
                <x-ui::table.row :key="'facility-'.$facility->FID">
                    <x-ui::table.cell>
                        <div class="flex items-center gap-3">
                            @if ($facility->images->isNotEmpty())
                                <x-ui::avatar size="xs" src="{{ asset('storage/'.$facility->images->first()->image_path) }}" />
                            @else
                                <x-ui::avatar size="xs" :name="$facility->Facility_Name" />
                            @endif

                            <div>
                                <div class="font-medium">{{ $facility->Facility_Name }}</div>
                                <div class="text-xs text-zinc-500">#{{ $facility->FID }}</div>
                            </div>
                        </div>
                    </x-ui::table.cell>

                    <x-ui::table.cell>
                        <x-ui::badge size="sm" color="zinc">
                            {{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Not specified' }}
                        </x-ui::badge>
                    </x-ui::table.cell>
                    <x-ui::table.cell>{{ $facility->Capacity ?? '—' }}</x-ui::table.cell>
                    <x-ui::table.cell>{{ $facility->Location ?? '—' }}</x-ui::table.cell>
                    <x-ui::table.cell>{{ $facility->Office ?? '—' }}</x-ui::table.cell>

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
                                ])
                            >
                                {{ $facility->Status }}
                            </span>
                        </button>
                    </x-ui::table.cell>

                    <x-ui::table.cell>
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
                                    class="text-red-600 dark:text-red-400"
                                    wire:click="archiveFacility({{ $facility->FID }})"
                                    data-ui-confirm="Archive this facility?"
                                >
                                    Archive
                                </x-ui::menu.item>
                            </x-ui::menu>
                        </x-ui::dropdown>
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
