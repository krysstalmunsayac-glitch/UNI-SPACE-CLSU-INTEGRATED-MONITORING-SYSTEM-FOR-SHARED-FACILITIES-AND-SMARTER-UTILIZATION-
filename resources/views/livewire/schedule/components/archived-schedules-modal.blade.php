    <flux:modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archived Schedules</flux:heading>
                <flux:subheading>Restore archived schedules or delete them permanently.</flux:subheading>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->archivedSchedules">
                    <flux:table.columns>
                        <flux:table.column>Schedule</flux:table.column>
                        <flux:table.column>Facility</flux:table.column>
                        <flux:table.column>Archived</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->archivedSchedules as $schedule)
                            <flux:table.row :key="'archived-schedule-'.$schedule->SID">
                                <flux:table.cell>
                                    <div class="font-medium">#{{ $schedule->SID }}</div>
                                    <div class="text-xs text-zinc-500">{{ $schedule->Date }} {{ $schedule->Start_Time }}-{{ $schedule->End_Time }}</div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $schedule->request?->facility?->Facility_Name ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>{{ $schedule->deleted_at?->format('M d, Y') ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="restore({{ $schedule->SID }})">Restore</flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="forceDelete({{ $schedule->SID }})" wire:confirm="Delete this archived schedule permanently?">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="py-8 text-center">No archived schedules found.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="$set('showArchivedModal', false)" variant="ghost">Close</flux:button>
            </div>
        </div>
    </flux:modal>
