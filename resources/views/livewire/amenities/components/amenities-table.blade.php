    <flux:card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Amenities</flux:heading>
            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="create" icon="plus" variant="primary">
                    Add Amenity
                </flux:button>
            </div>
        </div>

        <flux:table :paginate="$this->amenities">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </flux:table.column>

                <flux:table.column>Description</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'Status'"
                    :direction="$sortDirection"
                    wire:click="sort('Status')"
                >
                    Status
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->amenities as $amenity)
                    <flux:table.row :key="$amenity->AID">
                        <flux:table.cell>
                            <div class="font-medium">{{ $amenity->name }}</div>
                            <div class="text-xs text-zinc-500">#{{ $amenity->AID }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="truncate max-w-sm">{{ $amenity->Description ?? 'No description' }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <button
                                type="button"
                                wire:click="toggleStatus({{ $amenity->AID }})"
                                wire:loading.attr="disabled"
                                wire:target="toggleStatus({{ $amenity->AID }})"
                                class="group rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-900"
                                aria-label="Change {{ $amenity->name }} status from {{ $amenity->Status }} to {{ $amenity->Status === 'Available' ? 'Unavailable' : 'Available' }}"
                                title="Click to mark as {{ $amenity->Status === 'Available' ? 'unavailable' : 'available' }}"
                            >
                                <span
                                    @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-white transition-colors',
                                        'bg-emerald-700 group-hover:bg-red-600' => $amenity->Status === 'Available',
                                        'bg-red-600 group-hover:bg-emerald-700' => $amenity->Status !== 'Available',
                                    ])
                                >
                                    {{ $amenity->Status }}
                                </span>
                            </button>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="edit({{ $amenity->AID }})">Edit</flux:menu.item>
                                    <flux:menu.item icon="power" wire:click="toggleStatus({{ $amenity->AID }})">
                                        {{ $amenity->Status === 'Available' ? 'Mark unavailable' : 'Mark available' }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        class="text-red-600"
                                        wire:click="delete({{ $amenity->AID }})"
                                        wire:confirm="Are you sure you want to delete this amenity?"
                                    >
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8">
                            No amenities found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
