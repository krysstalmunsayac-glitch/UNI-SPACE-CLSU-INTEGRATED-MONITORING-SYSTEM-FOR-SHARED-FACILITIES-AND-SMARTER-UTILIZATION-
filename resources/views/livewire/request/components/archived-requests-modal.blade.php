    <div class="space-y-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <x-ui::heading size="lg">Archived Requests</x-ui::heading>
                    <x-ui::subheading>Restore archived requests or delete them permanently.</x-ui::subheading>
                </div>
                <div class="w-full lg:w-auto lg:min-w-[28rem]">
                    <x-ui::input wire:model.live.debounce.400ms="searchInput" aria-label="Search archived requests" placeholder="Search ID, user, facility, purpose, or status..." class="w-full" />
                </div>
            </div>

            <x-ui::select wire:model.live="archiveStatusFilter" label="Request status">
                <x-ui::select.option value="">All statuses</x-ui::select.option>
                <x-ui::select.option value="Cancelled">Cancelled</x-ui::select.option>
                <x-ui::select.option value="Approved">Approved</x-ui::select.option>
                <x-ui::select.option value="Ended">Event Ended</x-ui::select.option>
                <x-ui::select.option value="Rejected">Rejected</x-ui::select.option>
            </x-ui::select>

            <div class="min-h-56 overflow-x-auto">
                <x-ui::table :paginate="$this->archivedRequests" class="request-data-table">
                    <x-ui::table.columns>
                        <x-ui::table.column>Request ID</x-ui::table.column>
                        <x-ui::table.column>User</x-ui::table.column>
                        <x-ui::table.column>Event date</x-ui::table.column>
                        <x-ui::table.column>Facility</x-ui::table.column>
                        <x-ui::table.column>Time</x-ui::table.column>
                        <x-ui::table.column>Status</x-ui::table.column>
                        <x-ui::table.column>Archived</x-ui::table.column>
                        <x-ui::table.column>Actions</x-ui::table.column>
                    </x-ui::table.columns>

                    <x-ui::table.rows>
                        @forelse ($this->archivedRequests as $request)
                            <x-ui::table.row :key="'archived-request-'.$request->RID">
                                <x-ui::table.cell>
                                    <div class="font-medium">#{{ $request->RID }}</div>
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    <div class="flex min-w-44 items-center gap-3">
                                        <x-ui::avatar size="xs" :name="$request->user?->name ?? '—'" />
                                        <div class="min-w-0">
                                            <div class="font-medium">{{ $request->user?->name ?? '—' }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $request->user?->email ?? 'No email' }}</div>
                                        </div>
                                    </div>
                                </x-ui::table.cell>
                                <x-ui::table.cell variant="strong" class="min-w-36">
                                    <div>{{ $request->Proposed_Date->format('M d, Y') }}</div>
                                    @if ($request->Proposed_End_Date && ! $request->Proposed_End_Date->isSameDay($request->Proposed_Date))
                                        <div class="text-xs font-normal text-zinc-500 dark:text-zinc-400">to {{ $request->Proposed_End_Date->format('M d, Y') }}</div>
                                    @endif
                                </x-ui::table.cell>
                                <x-ui::table.cell class="min-w-44">{{ $request->facility?->Facility_Name ?? '—' }}</x-ui::table.cell>
                                <x-ui::table.cell class="whitespace-nowrap">
                                    @if (count($request->Daily_Schedules ?? []) > 1)
                                        <span class="font-medium">Daily times vary</span>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($request->Daily_Schedules) }} daily slots</div>
                                    @else
                                        {{ $request->Proposed_Start_Time->format('g:i A') }} – {{ $request->Proposed_End_Time->format('g:i A') }}
                                    @endif
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    <x-ui::badge
                                        size="sm"
                                        :color="match ($request->Status) {
                                            'Approved' => 'green',
                                            'Rejected' => 'red',
                                            'Cancelled' => 'amber',
                                            'Ended' => 'zinc',
                                            default => 'blue',
                                        }"
                                    >
                                        {{ $request->Status === 'Ended' ? 'Event Ended' : $request->Status }}
                                    </x-ui::badge>
                                </x-ui::table.cell>
                                <x-ui::table.cell class="whitespace-nowrap">
                                    <div class="font-medium">{{ $request->deleted_at?->format('M d, Y') ?? '—' }}</div>
                                    @if ($request->deleted_at)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $request->deleted_at->diffForHumans() }}</div>
                                    @endif
                                </x-ui::table.cell>
                                <x-ui::table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui::dropdown position="bottom" align="end">
                                            <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for request #{{ $request->RID }}" />
                                            <x-ui::menu>
                                                <x-ui::menu.item icon="eye" wire:click="showArchivedRequest({{ $request->RID }})">View details</x-ui::menu.item>
                                                <x-ui::menu.item icon="arrow-path" wire:click="restore({{ $request->RID }})">Restore</x-ui::menu.item>
                                                <x-ui::menu.separator />
                                                <x-ui::menu.item icon="trash" variant="danger" wire:click="forceDelete({{ $request->RID }})" data-ui-confirm="Delete archived request #{{ $request->RID }} permanently? This action cannot be undone." data-ui-confirm-title="Delete archived request" data-ui-confirm-label="Delete permanently" data-ui-confirm-variant="danger">Delete permanently</x-ui::menu.item>
                                            </x-ui::menu>
                                        </x-ui::dropdown>
                                    </div>
                                </x-ui::table.cell>
                            </x-ui::table.row>
                        @empty
                            <x-ui::table.row>
                                <x-ui::table.cell colspan="8" class="py-8 text-center">No archived requests match your current search or status filter.</x-ui::table.cell>
                            </x-ui::table.row>
                        @endforelse
                    </x-ui::table.rows>
                </x-ui::table>
            </div>

            @unless ($archiveOnly ?? false)
            <div class="flex justify-end">
                <x-ui::button wire:click="$set('showArchivedModal', false)" variant="ghost">Close</x-ui::button>
            </div>
            @endunless
    </div>
