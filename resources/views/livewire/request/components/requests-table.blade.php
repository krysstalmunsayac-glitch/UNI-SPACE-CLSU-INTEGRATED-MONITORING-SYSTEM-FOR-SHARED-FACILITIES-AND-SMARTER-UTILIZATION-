    <x-ui::card>
        <div class="mb-4">
            <div>
                <x-ui::heading size="lg">Facility Requests</x-ui::heading>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Open a request to see its full booking details before taking action.</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-x-5 gap-y-2 rounded-xl bg-zinc-50 px-4 py-3 text-xs text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
            <span><strong class="text-blue-700 dark:text-blue-300">Pending:</strong> waiting for review</span>
            <span><strong class="text-amber-700 dark:text-amber-300">Needs Revision:</strong> user must update details</span>
            <span><strong class="text-emerald-700 dark:text-emerald-300">Approved:</strong> ready and added to the schedule</span>
            <span><strong class="text-red-700 dark:text-red-300">Rejected:</strong> request was not accepted</span>
        </div>

        <x-ui::table :paginate="$this->requests" class="request-data-table">
            <x-ui::table.columns>
                <x-ui::table.column>Request ID</x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'User_ID'"
                    :direction="$sortDirection"
                    wire:click="sort('User_ID')"
                >
                    User
                </x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'Proposed_Date'"
                    :direction="$sortDirection"
                    wire:click="sort('Proposed_Date')"
                >
                    Event date
                </x-ui::table.column>

                <x-ui::table.column>Facility</x-ui::table.column>

                <x-ui::table.column>Time</x-ui::table.column>

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
                @forelse ($this->requests as $request)
                    <x-ui::table.row :key="$request->RID">

                        <x-ui::table.cell>
                            <div class="font-medium">REQ-{{ str_pad((string) $request->RID, 5, '0', STR_PAD_LEFT) }}</div>
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

                        <x-ui::table.cell class="min-w-44">
                            {{ $request->facility?->Facility_Name ?? '—' }}
                        </x-ui::table.cell>

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
                                :color="match($request->Status) {
                                    'Approved'  => 'green',
                                    'Rejected'  => 'red',
                                    'Cancelled' => 'amber',
                                    'Ended'     => 'zinc',
                                    'Pending' => $request->Review_Requested_At ? 'amber' : 'blue',
                                    default     => 'blue',
                                }"
                                inset="top bottom"
                            >
                                {{ $request->Review_Requested_At && $request->Status === 'Pending'
                                    ? 'Needs Revision'
                                    : ($request->Status === 'Ended' ? 'Event Ended' : $request->Status) }}
                            </x-ui::badge>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <div class="flex items-center justify-end gap-2">
                                <x-ui::dropdown :position="$loop->remaining < 2 ? 'top' : 'bottom'" align="end">
                                <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <x-ui::menu>
                                    <x-ui::menu.item icon="eye" wire:click="showRequest({{ $request->RID }})">
                                        View details
                                    </x-ui::menu.item>
                                    @if ($request->canBeReviewed())
                                        <x-ui::menu.item
                                            icon="document-magnifying-glass"
                                            class="text-amber-700 dark:text-amber-300"
                                            wire:click="openReviewModal({{ $request->RID }})"
                                        >
                                            Request changes
                                        </x-ui::menu.item>
                                    @endif
                                    @if ($request->canTransitionTo('Approved'))
                                        <x-ui::menu.separator />
                                        <x-ui::menu.item
                                            icon="check"
                                            class="text-green-600 dark:text-green-400"
                                            wire:click="approve({{ $request->RID }})"
                                            data-ui-confirm="Approve request REQ-{{ str_pad((string) $request->RID, 5, '0', STR_PAD_LEFT) }}? It will be added to the facility schedule."
                                            data-ui-confirm-title="Confirm approval"
                                            data-ui-confirm-label="Approve request"
                                        >
                                            Approve request
                                        </x-ui::menu.item>
                                    @endif
                                    @if ($request->Status === 'Approved')
                                        <x-ui::menu.separator />
                                        <x-ui::menu.item
                                            icon="x-mark"
                                            class="text-red-600 dark:text-red-400"
                                            wire:click="cancel({{ $request->RID }})"
                                            data-ui-confirm="Cancel approved request REQ-{{ str_pad((string) $request->RID, 5, '0', STR_PAD_LEFT) }}? Its facility schedule will be removed and the requester will be notified."
                                            data-ui-confirm-title="Confirm cancellation"
                                            data-ui-confirm-label="Cancel request"
                                            data-ui-confirm-variant="danger"
                                        >
                                            Cancel request
                                        </x-ui::menu.item>
                                    @elseif ($request->canTransitionTo('Rejected'))
                                        <x-ui::menu.item
                                            icon="x-mark"
                                            class="text-red-600 dark:text-red-400"
                                            wire:click="openRejectModal({{ $request->RID }})"
                                        >
                                            Reject request
                                        </x-ui::menu.item>
                                    @endif
                                    @if ($request->attachment_path)
                                        <x-ui::menu.separator />
                                        <x-ui::menu.item
                                            icon="arrow-down-tray"
                                            href="{{ route('requests.attachment.download', $request) }}"
                                        >
                                            Download attachment
                                        </x-ui::menu.item>
                                    @endif
                                    <x-ui::menu.separator />
                                    <x-ui::menu.item
                                        icon="archive-box"
                                        variant="danger"
                                        wire:click="delete({{ $request->RID }})"
                                        data-ui-confirm="Archive request REQ-{{ str_pad((string) $request->RID, 5, '0', STR_PAD_LEFT) }}? It will be removed from this list and can be restored from Archives."
                                        data-ui-confirm-title="Confirm archive"
                                        data-ui-confirm-label="Archive request"
                                        data-ui-confirm-variant="danger"
                                    >
                                        Archive request
                                    </x-ui::menu.item>
                                </x-ui::menu>
                                </x-ui::dropdown>
                            </div>
                        </x-ui::table.cell>

                    </x-ui::table.row>
                @empty
                    <x-ui::table.row>
                        <x-ui::table.cell colspan="7" class="text-center py-8">
                            No requests match your current search or status filter.
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @endforelse
            </x-ui::table.rows>
        </x-ui::table>
    </x-ui::card>
