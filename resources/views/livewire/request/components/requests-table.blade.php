    <flux:card>
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <flux:heading size="lg">Requests</flux:heading>
            <div class="flex w-full flex-wrap items-center gap-2 xl:w-auto xl:justify-end">
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="arrow-down-tray" icon:trailing="chevron-down">Download</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="document-text" href="{{ route('exports.requests.csv') }}">CSV</flux:menu.item>
                        <flux:menu.item icon="table-cells" href="{{ route('exports.requests.xlsx') }}">Excel (.xlsx)</flux:menu.item>
                        <flux:menu.item icon="document" href="{{ route('exports.requests.pdf') }}">PDF</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:select
                    wire:model.live="statusFilter"
                    class="min-w-[145px] flex-1 sm:flex-none sm:w-[160px]"
                >
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="Pending">Pending</flux:select.option>
                    <flux:select.option value="Needs Revision">Needs Revision</flux:select.option>
                    <flux:select.option value="Approved">Approved</flux:select.option>
                    <flux:select.option value="Ended">Event Ended</flux:select.option>
                    <flux:select.option value="Cancelled">Cancelled</flux:select.option>
                    <flux:select.option value="Rejected">Rejected</flux:select.option>
                </flux:select>

            </div>
        </div>

        <flux:table :paginate="$this->requests">
            <flux:table.columns>
                <flux:table.column>
                    <flux:dropdown position="bottom" align="start">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 font-semibold text-zinc-700 transition hover:text-emerald-700 dark:text-zinc-200 dark:hover:text-emerald-300"
                            aria-label="Sort requests by Request ID"
                        >
                            Request ID
                            <flux:icon.chevron-down class="size-3.5" />
                        </button>
                        <flux:menu>
                            <flux:menu.item
                                :icon="$sortBy === 'RID' && $receivedOrder === 'fifo' ? 'check' : null"
                                wire:click="setRequestIdOrder('fifo')"
                            >
                                Oldest first
                            </flux:menu.item>
                            <flux:menu.item
                                :icon="$sortBy === 'RID' && $receivedOrder === 'recent' ? 'check' : null"
                                wire:click="setRequestIdOrder('recent')"
                            >
                                Newest first
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'User_ID'"
                    :direction="$sortDirection"
                    wire:click="sort('User_ID')"
                >
                    User
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'Proposed_Date'"
                    :direction="$sortDirection"
                    wire:click="sort('Proposed_Date')"
                >
                    Date
                </flux:table.column>

                <flux:table.column>Facility</flux:table.column>

                <flux:table.column>Time</flux:table.column>
                <flux:table.column>Capacity</flux:table.column>
                <flux:table.column>Purpose</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'Status'"
                    :direction="$sortDirection"
                    wire:click="sort('Status')"
                >
                    Status
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->requests as $request)
                    <flux:table.row :key="$request->RID">

                        <flux:table.cell>
                            <div class="font-medium">#{{ $request->RID }}</div>
                        </flux:table.cell>

                        <flux:table.cell class="flex items-center gap-3">
                            <flux:avatar size="xs" :name="$request->user?->name ?? 'N/A'" />
                            <div>
                                <div class="font-medium">{{ $request->user?->name ?? 'N/A' }}</div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell variant="strong">
                            {{ $request->Proposed_Date->format('M d, Y') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $request->facility?->Facility_Name ?? 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">
                            {{ $request->Proposed_Start_Time->format('H:i') }} – {{ $request->Proposed_End_Time->format('H:i') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $request->Capacity ?? 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="truncate max-w-xs">{{ $request->Purpose }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
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
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-2">
                                <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="eye"
                                        wire:click="showRequest({{ $request->RID }})"
                                    >
                                        View
                                    </flux:menu.item>
                                    @if (! in_array($request->Status, ['Approved', 'Cancelled', 'Ended'], true))
                                        <flux:menu.item
                                            icon="document-magnifying-glass"
                                            class="text-amber-700 dark:text-amber-300"
                                            wire:click="openReviewModal({{ $request->RID }})"
                                        >
                                            Review
                                        </flux:menu.item>
                                    @endif
                                    @if (! in_array($request->Status, ['Approved', 'Cancelled', 'Ended'], true))
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="check"
                                            class="text-green-600 dark:text-green-400"
                                            wire:click="approve({{ $request->RID }})"
                                            wire:confirm="Approve this request?"
                                        >
                                            Approve
                                        </flux:menu.item>
                                    @endif
                                    @if (! in_array($request->Status, ['Cancelled', 'Ended'], true))
                                        @if (in_array($request->Status, ['Approved'], true))
                                            <flux:menu.separator />
                                        @endif
                                        <flux:menu.item
                                            icon="x-mark"
                                            class="text-red-600 dark:text-red-400"
                                            wire:click="openRejectModal({{ $request->RID }})"
                                        >
                                            Reject
                                        </flux:menu.item>
                                    @endif
                                    @if ($request->attachment_path)
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="arrow-down-tray"
                                            wire:click="downloadAttachment({{ $request->RID }})"
                                        >
                                            Download attachment
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="archive-box"
                                        class="text-amber-600"
                                        wire:click="delete({{ $request->RID }})"
                                        wire:confirm="Archive this request?"
                                    >
                                        Archive
                                    </flux:menu.item>
                                </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center py-8">
                            No requests found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
