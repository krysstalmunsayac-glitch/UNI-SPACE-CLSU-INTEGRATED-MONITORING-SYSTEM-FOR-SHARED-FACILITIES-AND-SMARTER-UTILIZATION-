<div class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <x-ui::heading size="lg">Archived Facilities</x-ui::heading>
                <x-ui::subheading>Restore archived facilities or delete them permanently.</x-ui::subheading>
            </div>
            <div class="w-full lg:w-auto lg:min-w-[28rem]">
                <x-ui::input wire:model.live.debounce.400ms="searchInput" placeholder="Search facility, location, or office..." class="w-full" />
            </div>
        </div>

        <div class="min-h-56 overflow-x-auto">
            <x-ui::table :paginate="$this->archivedFacilities">
                <x-ui::table.columns>
                    <x-ui::table.column>Facility</x-ui::table.column>
                    <x-ui::table.column>Location</x-ui::table.column>
                    <x-ui::table.column>Office</x-ui::table.column>
                    <x-ui::table.column>Status</x-ui::table.column>
                    <x-ui::table.column>Archived</x-ui::table.column>
                    <x-ui::table.column>Actions</x-ui::table.column>
                </x-ui::table.columns>

                <x-ui::table.rows>
                    @forelse ($this->archivedFacilities as $facility)
                        <x-ui::table.row :key="'archived-facility-'.$facility->FID">
                            <x-ui::table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($facility->images->isNotEmpty())
                                        <x-ui::avatar size="sm" src="{{ asset('storage/'.$facility->images->first()->image_path) }}" />
                                    @else
                                        <x-ui::avatar size="sm" :name="$facility->Facility_Name" />
                                    @endif

                                    <div>
                                        <div class="font-medium">{{ $facility->Facility_Name }}</div>
                                        <div class="text-xs text-zinc-500">FAC-{{ str_pad((string) $facility->FID, 5, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </x-ui::table.cell>

                            <x-ui::table.cell>{{ $facility->Location ?? '—' }}</x-ui::table.cell>
                            <x-ui::table.cell>{{ $facility->Office ?? '—' }}</x-ui::table.cell>

                            <x-ui::table.cell>
                                <x-ui::badge
                                    size="sm"
                                    :color="match ($facility->Status) {
                                        'Available' => 'green',
                                        default => 'red',
                                    }"
                                >
                                    {{ $facility->Status }}
                                </x-ui::badge>
                            </x-ui::table.cell>

                            <x-ui::table.cell>
                                <div>{{ $facility->deleted_at?->format('M d, Y') ?? '—' }}</div>
                                @if ($facility->deleted_at)
                                    <div class="text-xs text-zinc-500">
                                        {{ $facility->deleted_at->diffForHumans() }}
                                    </div>
                                @endif
                            </x-ui::table.cell>

                            <x-ui::table.cell>
                                <div class="flex justify-end">
                                    <x-ui::dropdown position="bottom" align="end">
                                        <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for {{ $facility->Facility_Name }}" />
                                        <x-ui::menu>
                                            <x-ui::menu.item icon="arrow-path" wire:click="restoreFacility({{ $facility->FID }})" data-ui-confirm="Restore this facility?">Restore</x-ui::menu.item>
                                            <x-ui::menu.separator />
                                            <x-ui::menu.item icon="trash" variant="danger" wire:click="forceDeleteFacility({{ $facility->FID }})" data-ui-confirm="This action cannot be undone. Permanently delete this facility?">Delete permanently</x-ui::menu.item>
                                        </x-ui::menu>
                                    </x-ui::dropdown>
                                </div>
                            </x-ui::table.cell>
                        </x-ui::table.row>
                    @empty
                        <x-ui::table.row>
                            <x-ui::table.cell colspan="6" class="py-10 text-center">
                                No archived facilities found.
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
