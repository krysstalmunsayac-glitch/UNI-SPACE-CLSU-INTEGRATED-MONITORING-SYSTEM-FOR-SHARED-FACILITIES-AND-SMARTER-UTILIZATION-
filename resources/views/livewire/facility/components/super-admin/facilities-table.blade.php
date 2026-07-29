<flux:card>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <flux:heading size="lg">Facilities</flux:heading>

        <div class="flex w-full flex-wrap gap-2 lg:w-auto lg:justify-end">
            <flux:dropdown position="bottom" align="end">
                <flux:button icon="arrow-down-tray" icon:trailing="chevron-down">Download</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-text" href="{{ route('exports.facilities.csv') }}">CSV</flux:menu.item>
                    <flux:menu.item icon="table-cells" href="{{ route('exports.facilities.xlsx') }}">Excel (.xlsx)</flux:menu.item>
                    <flux:menu.item icon="document" href="{{ route('exports.facilities.pdf') }}">PDF</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <flux:button
                wire:click="create"
                icon="plus"
                variant="primary"
            >
                Add Facility
            </flux:button>
        </div>
    </div>

    <flux:table :paginate="$this->facilities">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'Facility_Name'" :direction="$sortDirection" wire:click="sort('Facility_Name')">
                Facility
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'facility_type'" :direction="$sortDirection" wire:click="sort('facility_type')">
                Facility Type
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Price'" :direction="$sortDirection" wire:click="sort('Price')">
                Price
            </flux:table.column>
            <flux:table.column>Capacity</flux:table.column>
            <flux:table.column>Location</flux:table.column>
            <flux:table.column>Office</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'Status'" :direction="$sortDirection" wire:click="sort('Status')">
                Status
            </flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->facilities as $facility)
                <flux:table.row :key="'facility-'.$facility->FID">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if ($facility->images->isNotEmpty())
                                <flux:avatar size="xs" src="{{ asset('storage/'.$facility->images->first()->image_path) }}" />
                            @else
                                <flux:avatar size="xs" :name="$facility->Facility_Name" />
                            @endif

                            <div>
                                <div class="font-medium">{{ $facility->Facility_Name }}</div>
                                <div class="text-xs text-zinc-500">#{{ $facility->FID }}</div>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">
                            {{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Not specified' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">₱{{ number_format($facility->Price, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ $facility->Capacity ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $facility->Location ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $facility->Office ?? 'N/A' }}</flux:table.cell>

                    <flux:table.cell>
                        <button
                            type="button"
                            wire:click="toggleStatus({{ $facility->FID }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $facility->FID }})"
                            class="group rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-900"
                            aria-label="Change {{ $facility->Facility_Name }} status from {{ $facility->Status }} to {{ $facility->Status === 'Unavailable' ? 'Available' : 'Unavailable' }}"
                            title="Click to mark as {{ $facility->Status === 'Unavailable' ? 'available' : 'unavailable' }}"
                        >
                            <span
                                @class([
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-white transition-colors',
                                    'bg-emerald-700 group-hover:bg-red-600' => $facility->Status === 'Available',
                                    'bg-amber-600 group-hover:bg-red-600' => $facility->Status === 'Under Maintenance',
                                    'bg-red-600 group-hover:bg-emerald-700' => $facility->Status === 'Unavailable',
                                ])
                            >
                                {{ $facility->Status }}
                            </span>
                        </button>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit({{ $facility->FID }})">
                                    Edit
                                </flux:menu.item>

                                <flux:menu.item icon="power" wire:click="toggleStatus({{ $facility->FID }})">
                                    {{ $facility->Status === 'Unavailable' ? 'Activate' : 'Deactivate' }}
                                </flux:menu.item>

                                <flux:menu.separator />

                                <flux:menu.item
                                    icon="archive-box"
                                    class="text-red-600 dark:text-red-400"
                                    wire:click="archiveFacility({{ $facility->FID }})"
                                    wire:confirm="Archive this facility?"
                                >
                                    Archive
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="py-8 text-center">
                        No facilities found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>
