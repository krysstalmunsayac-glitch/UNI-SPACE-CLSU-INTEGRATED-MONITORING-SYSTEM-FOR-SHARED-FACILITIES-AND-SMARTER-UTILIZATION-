    <flux:modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archived Requests</flux:heading>
                <flux:subheading>Restore archived requests or delete them permanently.</flux:subheading>
            </div>

            <flux:select wire:model.live="archiveStatusFilter" label="Request status" class="max-w-xs">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="Cancelled">Cancelled</flux:select.option>
                <flux:select.option value="Approved">Approved</flux:select.option>
                <flux:select.option value="Rejected">Rejected</flux:select.option>
            </flux:select>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->archivedRequests">
                    <flux:table.columns>
                        <flux:table.column>Request</flux:table.column>
                        <flux:table.column>User</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Archived</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->archivedRequests as $request)
                            <flux:table.row :key="'archived-request-'.$request->RID">
                                <flux:table.cell>
                                    <div class="font-medium">#{{ $request->RID }}</div>
                                    <div class="text-xs text-zinc-500">{{ $request->Purpose }}</div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $request->user?->name ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>{{ $request->Status }}</flux:table.cell>
                                <flux:table.cell>{{ $request->deleted_at?->format('M d, Y') ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="restore({{ $request->RID }})">Restore</flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="forceDelete({{ $request->RID }})" wire:confirm="Delete this archived request permanently?">Delete</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="py-8 text-center">No archived requests found.</flux:table.cell>
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
