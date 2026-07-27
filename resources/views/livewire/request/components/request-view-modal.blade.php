    {{-- View Modal (read-only) --}}
    <flux:modal wire:model.self="showViewModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Request #{{ $viewingId }}</flux:heading>
                <flux:subheading>Request details (read-only).</flux:subheading>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">User</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $this->users->firstWhere('id', $User_ID)?->name ?? 'N/A' }}
                    </span>
                </div>

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Proposed Date</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $Proposed_Date ? \Carbon\Carbon::parse($Proposed_Date)->format('M d, Y') : '—' }}
                    </span>
                </div>

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Time</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $Proposed_Start_Time }} – {{ $Proposed_End_Time }}
                    </span>
                </div>

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Facility</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $Facility_Name ?? '—' }}
                    </span>
                </div>

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Event</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $Event_Title ?? '—' }}
                    </span>
                </div>

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Status</span>
                    <flux:badge
                        size="sm"
                        :color="match($Status) {
                            'Approved'  => 'green',
                            'Rejected'  => 'red',
                            'Cancelled' => 'amber',
                            default     => 'blue',
                        }"
                        inset="top bottom"
                    >
                        {{ $Status }}
                    </flux:badge>
                </div>

                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Attachment</span>
                    @if ($attachmentPath)
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="arrow-down-tray"
                            wire:click="downloadAttachment({{ $viewingId }})"
                        >
                            Download
                        </flux:button>
                    @else
                        <span class="text-sm font-medium text-gray-900 dark:text-white">—</span>
                    @endif
                </div>

                <div class="px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Purpose</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $Purpose ?: '—' }}</p>
                </div>

                <div class="flex justify-between px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Expected Attendees</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $Capacity ?? '—' }}
                    </span>
                </div>

                <div class="px-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Facility Amenities</span>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white space-y-1">
                        @if (! empty($Requested_Amenities))
                            @foreach ($Requested_Amenities as $amenityName)
                                <p>{{ $amenityName }}</p>
                            @endforeach
                        @else
                            <p class="text-gray-500 dark:text-gray-400">—</p>
                        @endif
                    </div>
                </div>

            </div>

            <div class="flex gap-2">
                @if (! in_array($Status, ['Approved', 'Cancelled'], true) && $viewingId)
                    <flux:button wire:click="approve({{ $viewingId }})" wire:confirm="Approve this request?" variant="primary" icon="check" class="flex-1">
                        Approve
                    </flux:button>
                @endif
                @if ($Status !== 'Cancelled' && $viewingId)
                    <flux:button wire:click="openRejectModal({{ $viewingId }}); $set('showViewModal', false)" variant="danger" icon="x-mark" class="flex-1">
                        Reject
                    </flux:button>
                @endif
                <flux:button
                    wire:click="$set('showViewModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>
