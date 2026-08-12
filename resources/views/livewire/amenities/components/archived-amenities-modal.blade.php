    <div class="space-y-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <x-ui::heading size="lg">Archived amenities</x-ui::heading>
                    <x-ui::subheading>Restore archived amenities or delete them permanently.</x-ui::subheading>
                </div>
                <div class="w-full lg:w-auto lg:min-w-[28rem]">
                    <x-ui::input wire:model.live.debounce.400ms="searchInput" placeholder="Search name or description..." class="w-full" />
                </div>
            </div>

            <div class="min-h-56 overflow-x-auto">
                <x-ui::table :paginate="$this->archivedAmenities">
                    <x-ui::table.columns>
                        <x-ui::table.column>Name</x-ui::table.column>
                        <x-ui::table.column>Description</x-ui::table.column>
                        <x-ui::table.column>Facilities</x-ui::table.column>
                        <x-ui::table.column>Usage limit</x-ui::table.column>
                        <x-ui::table.column>Archived</x-ui::table.column>
                        <x-ui::table.column>Actions</x-ui::table.column>
                    </x-ui::table.columns>

                    <x-ui::table.rows>
                        @forelse ($this->archivedAmenities as $amenity)
                            <x-ui::table.row :key="'archived-amenity-'.$amenity->AID">
                                <x-ui::table.cell>
                                    <div class="font-medium">{{ $amenity->name }}</div>
                                    <div class="text-xs text-zinc-500">#{{ $amenity->AID }}</div>
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    {{ $amenity->Description ?? 'No description' }}
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    {{ $amenity->facilities->pluck('Facility_Name')->join(', ') ?: 'Unassigned' }}
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    {{ $amenity->reservation_limit ? number_format($amenity->reservation_limit).' concurrent' : 'Unlimited' }}
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    {{ $amenity->deleted_at?->format('M d, Y') ?? '—' }}
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    @if ($this->canManageAmenity($amenity))
                                    <x-ui::dropdown position="bottom" align="end">
                                        <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for {{ $amenity->name }}" />
                                        <x-ui::menu>
                                            <x-ui::menu.item icon="arrow-path" wire:click="restore({{ $amenity->AID }})">Restore</x-ui::menu.item>
                                            <x-ui::menu.separator />
                                            <x-ui::menu.item icon="trash" variant="danger" wire:click="forceDelete({{ $amenity->AID }})" data-ui-confirm="Delete this archived amenity permanently?">Delete permanently</x-ui::menu.item>
                                        </x-ui::menu>
                                    </x-ui::dropdown>
                                    @else
                                        <span class="text-xs text-zinc-500">Read only</span>
                                    @endif
                                </x-ui::table.cell>
                            </x-ui::table.row>
                        @empty
                            <x-ui::table.row>
                                <x-ui::table.cell colspan="6" class="py-8 text-center">
                                    No archived amenities found.
                                </x-ui::table.cell>
                            </x-ui::table.row>
                        @endforelse
                    </x-ui::table.rows>
                </x-ui::table>
            </div>

            @unless ($archiveOnly ?? false)
            <div class="flex justify-end">
                <x-ui::button wire:click="$set('showArchivedModal', false)" variant="ghost">
                    Close
                </x-ui::button>
            </div>
            @endunless
    </div>
