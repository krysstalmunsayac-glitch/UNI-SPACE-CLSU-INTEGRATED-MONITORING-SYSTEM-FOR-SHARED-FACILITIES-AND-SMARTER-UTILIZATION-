<flux:modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Archived Facilities</flux:heading>
            <flux:subheading>
                Restore archived facilities or delete them permanently.
            </flux:subheading>
        </div>

        <div class="overflow-x-auto">
            <flux:table :paginate="$this->archivedFacilities">
                <flux:table.columns>
                    <flux:table.column>Facility</flux:table.column>
                    <flux:table.column>Location</flux:table.column>
                    <flux:table.column>Office</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Archived</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->archivedFacilities as $facility)
                        <flux:table.row :key="'archived-facility-'.$facility->FID">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($facility->images->isNotEmpty())
                                        <flux:avatar size="sm" src="{{ asset('storage/'.$facility->images->first()->image_path) }}" />
                                    @else
                                        <flux:avatar size="sm" :name="$facility->Facility_Name" />
                                    @endif

                                    <div>
                                        <div class="font-medium">{{ $facility->Facility_Name }}</div>
                                        <div class="text-xs text-zinc-500">#{{ $facility->FID }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>{{ $facility->Location ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell>{{ $facility->Office ?? 'N/A' }}</flux:table.cell>

                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($facility->Status) {
                                        'Available' => 'green',
                                        'Under Maintenance' => 'amber',
                                        default => 'red',
                                    }"
                                >
                                    {{ $facility->Status }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div>{{ $facility->deleted_at?->format('M d, Y') ?? 'N/A' }}</div>
                                @if ($facility->deleted_at)
                                    <div class="text-xs text-zinc-500">
                                        {{ $facility->deleted_at->diffForHumans() }}
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="arrow-uturn-left"
                                        wire:click="restoreFacility({{ $facility->FID }})"
                                        wire:confirm="Restore this facility?"
                                    >
                                        Restore
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="forceDeleteFacility({{ $facility->FID }})"
                                        wire:confirm="This action cannot be undone. Permanently delete this facility?"
                                    >
                                        Delete
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-10 text-center">
                                No archived facilities found.
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
