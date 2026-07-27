    <flux:modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-5xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archived Amenities</flux:heading>
                <flux:subheading>Restore archived amenities or delete them permanently.</flux:subheading>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->archivedAmenities">
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Description</flux:table.column>
                        <flux:table.column>Archived</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->archivedAmenities as $amenity)
                            <flux:table.row :key="'archived-amenity-'.$amenity->AID">
                                <flux:table.cell>
                                    <div class="font-medium">{{ $amenity->name }}</div>
                                    <div class="text-xs text-zinc-500">#{{ $amenity->AID }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $amenity->Description ?? 'No description' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $amenity->deleted_at?->format('M d, Y') ?? 'N/A' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="restore({{ $amenity->AID }})">
                                            Restore
                                        </flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="forceDelete({{ $amenity->AID }})" wire:confirm="Delete this archived amenity permanently?">
                                            Delete
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="py-8 text-center">
                                    No archived amenities found.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="$set('showArchivedModal', false)" variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>
