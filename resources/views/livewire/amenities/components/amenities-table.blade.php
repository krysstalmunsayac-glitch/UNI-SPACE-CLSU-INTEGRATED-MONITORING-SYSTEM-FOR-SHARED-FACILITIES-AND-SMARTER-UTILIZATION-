    <x-ui::card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <x-ui::heading size="lg">Amenities</x-ui::heading>
            <div class="flex flex-wrap gap-2">
                <x-ui::button wire:click="create" icon="plus" variant="primary">
                    Add Amenity
                </x-ui::button>
            </div>
        </div>

        <x-ui::table :paginate="$this->amenities">
            <x-ui::table.columns>
                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </x-ui::table.column>

                <x-ui::table.column>Description</x-ui::table.column>

                <x-ui::table.column>Created by</x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'reservation_limit'"
                    :direction="$sortDirection"
                    wire:click="sort('reservation_limit')"
                >
                    Usage limit
                </x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'current_usage_count'"
                    :direction="$sortDirection"
                    wire:click="sort('current_usage_count')"
                >
                    Current usage
                </x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'Status'"
                    :direction="$sortDirection"
                    wire:click="sort('Status')"
                >
                    Status
                </x-ui::table.column>

                <x-ui::table.column>Actions</x-ui::table.column>
            </x-ui::table.columns>

            <x-ui::table.rows>
                @forelse ($this->amenities as $amenity)
                    <x-ui::table.row :key="$amenity->AID">
                        <x-ui::table.cell>
                            <div class="font-medium">{{ $amenity->name }}</div>
                            <div class="text-xs text-zinc-500">AMN-{{ str_pad((string) $amenity->AID, 5, '0', STR_PAD_LEFT) }}</div>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <div class="max-w-md whitespace-normal leading-5">{{ $amenity->Description ?? '—' }}</div>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <div class="font-medium text-zinc-900 dark:text-white">
                                {{ $amenity->creator?->name ?? 'Legacy/System' }}
                            </div>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <span class="group/tooltip relative inline-flex" tabindex="0">
                                <x-ui::badge :color="$amenity->reservation_limit ? 'blue' : 'zinc'">
                                    {{ $amenity->reservation_limit ? number_format($amenity->reservation_limit).' concurrent' : 'Unlimited' }}
                                </x-ui::badge>
                                <span
                                    role="tooltip"
                                    class="pointer-events-none absolute bottom-full left-1/2 z-40 mb-2 hidden w-64 -translate-x-1/2 rounded-lg bg-zinc-950 px-3 py-2 text-xs font-normal leading-5 text-white shadow-xl group-hover/tooltip:block group-focus/tooltip:block dark:bg-white dark:text-zinc-900"
                                >
                                    Maximum number of overlapping approved or pending reservations that may use this amenity. Unlimited means no concurrency cap.
                                </span>
                            </span>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            @php
                                $currentUsage = (int) $amenity->current_usage_count;
                                $atLimit = $amenity->reservation_limit !== null
                                    && $currentUsage >= $amenity->reservation_limit;
                            @endphp
                            <span class="group/tooltip relative inline-flex" tabindex="0">
                                <x-ui::badge :color="$atLimit ? 'red' : ($currentUsage > 0 ? 'amber' : 'green')">
                                    {{ number_format($currentUsage) }} / {{ $amenity->reservation_limit ? number_format($amenity->reservation_limit) : '∞' }}
                                </x-ui::badge>
                                <span
                                    role="tooltip"
                                    class="pointer-events-none absolute bottom-full left-1/2 z-40 mb-2 hidden w-64 -translate-x-1/2 rounded-lg bg-zinc-950 px-3 py-2 text-xs font-normal leading-5 text-white shadow-xl group-hover/tooltip:block group-focus/tooltip:block dark:bg-white dark:text-zinc-900"
                                >
                                    Total pending or approved reservations currently assigned to this amenity.
                                </span>
                            </span>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            @if ($this->canManageAmenity($amenity))
                            <button
                                type="button"
                                wire:click="requestToggleStatus({{ $amenity->AID }})"
                                wire:loading.attr="disabled"
                                wire:target="requestToggleStatus({{ $amenity->AID }})"
                                class="group rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-900"
                                aria-label="Change {{ $amenity->name }} status from {{ $amenity->Status }} to {{ $amenity->Status === 'Available' ? 'Unavailable' : 'Available' }}"
                                title="Click to {{ $amenity->Status === 'Available' ? 'deactivate' : 'activate' }}"
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
                            @else
                                <x-ui::badge :color="$amenity->Status === 'Available' ? 'green' : 'red'">
                                    {{ $amenity->Status }}
                                </x-ui::badge>
                            @endif
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            @if ($this->canManageAmenity($amenity))
                            <x-ui::dropdown :position="$loop->remaining < 2 ? 'top' : 'bottom'" align="end">
                                <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for {{ $amenity->name }}" />
                                <x-ui::menu>
                                    <x-ui::menu.item icon="eye" wire:click="showDetails({{ $amenity->AID }})">View</x-ui::menu.item>
                                    <x-ui::menu.item icon="pencil" wire:click="edit({{ $amenity->AID }})">Edit</x-ui::menu.item>
                                    <x-ui::menu.item icon="power" wire:click="requestToggleStatus({{ $amenity->AID }})">
                                        {{ $amenity->Status === 'Available' ? 'Deactivate' : 'Activate' }}
                                    </x-ui::menu.item>
                                    <x-ui::menu.separator />
                                    <x-ui::menu.item
                                        icon="archive-box"
                                        variant="danger"
                                        wire:click="delete({{ $amenity->AID }})"
                                        data-ui-confirm="Archive this amenity? It can be restored later."
                                        data-ui-confirm-title="Confirm archive"
                                        data-ui-confirm-label="Archive amenity"
                                        data-ui-confirm-variant="danger"
                                    >
                                        Archive
                                    </x-ui::menu.item>
                                    <x-ui::menu.item
                                        icon="trash"
                                        class="text-red-700 dark:text-red-300"
                                        wire:click="forceDelete({{ $amenity->AID }})"
                                        data-ui-confirm="Permanently delete this amenity? This action cannot be undone."
                                    >
                                        Delete permanently
                                    </x-ui::menu.item>
                                </x-ui::menu>
                            </x-ui::dropdown>
                            @else
                                <x-ui::button variant="ghost" size="sm" icon="eye" wire:click="showDetails({{ $amenity->AID }})">View</x-ui::button>
                            @endif
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @empty
                    <x-ui::table.row>
                        <x-ui::table.cell colspan="7" class="text-center py-8">
                            No amenities found.
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @endforelse
            </x-ui::table.rows>
        </x-ui::table>
    </x-ui::card>
