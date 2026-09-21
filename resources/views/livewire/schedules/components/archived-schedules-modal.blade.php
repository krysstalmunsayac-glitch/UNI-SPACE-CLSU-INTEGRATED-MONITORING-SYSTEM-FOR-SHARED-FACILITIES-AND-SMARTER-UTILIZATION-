    <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
        <div class="space-y-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <x-ui::heading size="lg">Archived Schedules</x-ui::heading>
                    <x-ui::subheading>Restore archived schedules or delete them permanently.</x-ui::subheading>
                </div>
                <div class="w-full lg:w-auto lg:min-w-[28rem]">
                    <x-ui::input wire:model.live.debounce.400ms="searchInput" placeholder="Search facility or purpose..." class="w-full" />
                </div>
            </div>

            <div class="min-h-56 overflow-x-auto">
                <x-ui::table :paginate="$this->archivedSchedules">
                    <x-ui::table.columns>
                        <x-ui::table.column>Schedule</x-ui::table.column>
                        <x-ui::table.column>Facility</x-ui::table.column>
                        <x-ui::table.column>Archived</x-ui::table.column>
                        <x-ui::table.column>Actions</x-ui::table.column>
                    </x-ui::table.columns>

                    <x-ui::table.rows>
                        @forelse ($this->archivedSchedules as $schedule)
                            <x-ui::table.row :key="'archived-schedule-'.$schedule->SID">
                                <x-ui::table.cell>
                                    <div class="font-medium">SCH-{{ str_pad((string) $schedule->SID, 5, '0', STR_PAD_LEFT) }}</div>
                                    <div class="text-xs text-zinc-500">
                                        {{ \Carbon\Carbon::parse($schedule->Date)->format('M d, Y') }} ·
                                        {{ \Carbon\Carbon::parse($schedule->Start_Time)->format('h:i A') }}–{{ \Carbon\Carbon::parse($schedule->End_Time)->format('h:i A') }}
                                    </div>
                                </x-ui::table.cell>
                                <x-ui::table.cell>{{ $schedule->request?->facility?->Facility_Name ?? '—' }}</x-ui::table.cell>
                                <x-ui::table.cell>{{ $schedule->deleted_at?->format('M d, Y') ?? '—' }}</x-ui::table.cell>
                                <x-ui::table.cell>
                                    <x-ui::dropdown position="bottom" align="end">
                                        <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for schedule SCH-{{ str_pad((string) $schedule->SID, 5, '0', STR_PAD_LEFT) }}" />
                                        <x-ui::menu>
                                            <x-ui::menu.item icon="arrow-path" wire:click="restore({{ $schedule->SID }})">Restore</x-ui::menu.item>
                                            <x-ui::menu.separator />
                                            <x-ui::menu.item icon="trash" variant="danger" wire:click="forceDelete({{ $schedule->SID }})" data-ui-confirm="Delete this archived schedule permanently?">Delete permanently</x-ui::menu.item>
                                        </x-ui::menu>
                                    </x-ui::dropdown>
                                </x-ui::table.cell>
                            </x-ui::table.row>
                        @empty
                            <x-ui::table.row>
                                <x-ui::table.cell colspan="4" class="py-8 text-center">No archived schedules found.</x-ui::table.cell>
                            </x-ui::table.row>
                        @endforelse
                    </x-ui::table.rows>
                </x-ui::table>
            </div>

            <div class="flex justify-end">
                <x-ui::button wire:click="$set('showArchivedModal', false)" variant="ghost">Close</x-ui::button>
            </div>
        </div>
    </x-ui::modal>
