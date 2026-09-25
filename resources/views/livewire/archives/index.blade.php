<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Archived Records</h1>
        <p class="text-gray-600 dark:text-gray-400">View deleted requests and schedules for your assigned facilities.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Requests</h2>
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-ui::select wire:model.live="archiveStatusFilter" label="Request status" class="sm:w-44">
                        <x-ui::select.option value="">All statuses</x-ui::select.option>
                        <x-ui::select.option value="Cancelled">Cancelled</x-ui::select.option>
                        <x-ui::select.option value="Approved">Approved</x-ui::select.option>
                        <x-ui::select.option value="Expired">Expired</x-ui::select.option>
                        <x-ui::select.option value="Ended">Completed</x-ui::select.option>
                        <x-ui::select.option value="Rejected">Rejected</x-ui::select.option>
                    </x-ui::select>
                    <x-ui::select wire:model.live="archiveMonthFilter" label="Archived month" class="sm:w-44">
                        <x-ui::select.option value="">All months</x-ui::select.option>
                        @foreach (range(1, 12) as $month)
                            <x-ui::select.option value="{{ $month }}">{{ now()->month($month)->format('F') }}</x-ui::select.option>
                        @endforeach
                    </x-ui::select>
                    <x-ui::select wire:model.live="archiveYearFilter" label="Archived year" class="sm:w-36">
                        <x-ui::select.option value="">All years</x-ui::select.option>
                        @forelse ($this->archiveYears as $year)
                            <x-ui::select.option value="{{ $year }}">{{ $year }}</x-ui::select.option>
                        @empty
                            <x-ui::select.option value="{{ now()->year }}">{{ now()->year }}</x-ui::select.option>
                        @endforelse
                    </x-ui::select>
                </div>
            </div>
            <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
                Showing archived requests for {{ $this->archivePeriodLabel() }}.
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedRequests as $request)
                    <div wire:key="archive-request-{{ $request->RID }}" class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Request #{{ $request->RID }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->facility?->Facility_Name ?? 'Unassigned facility' }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $request->Status === 'Ended' ? 'Completed' : $request->Status }}</p>
                            </div>
                            <div class="flex gap-2">
                                <x-ui::button size="sm" variant="ghost" wire:click="restoreRequest({{ $request->RID }})">Restore</x-ui::button>
                                <x-ui::button size="sm" variant="danger" wire:click="forceDeleteRequest({{ $request->RID }})" data-ui-confirm="Delete this archived request permanently?">Delete</x-ui::button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived requests found.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $this->archivedRequests->links() }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Schedules</h2>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedSchedules as $schedule)
                    <div wire:key="archive-schedule-{{ $schedule->SID }}" class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Schedule #{{ $schedule->SID }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $schedule->request?->facility?->Facility_Name ?? 'Unassigned facility' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <x-ui::button size="sm" variant="ghost" wire:click="restoreSchedule({{ $schedule->SID }})">Restore</x-ui::button>
                                <x-ui::button size="sm" variant="danger" wire:click="forceDeleteSchedule({{ $schedule->SID }})" data-ui-confirm="Delete this archived schedule permanently?">Delete</x-ui::button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived schedules found.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $this->archivedSchedules->links() }}</div>
        </div>
        @if (auth()->user()->isSuperAdmin())
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Event</h2>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedEvents as $event)
                    <div wire:key="archive-event-{{ $event->EID }}" class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Event #{{ $event->EID }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $event->Event_Title }}</p>
                            </div>
                            <div class="flex gap-2">
                                <x-ui::button size="sm" variant="ghost" wire:click="restoreEvent({{ $event->EID }})">Restore</x-ui::button>
                                <x-ui::button size="sm" variant="danger" wire:click="forceDeleteEvent({{ $event->EID }})" data-ui-confirm="Delete this archived event permanently?">Delete</x-ui::button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived events found.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $this->archivedEvents->links() }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Users</h2>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedUsers as $user)
                    <div wire:key="archive-user-{{ $user->id }}" class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">User #{{ $user->id }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Permanently removed automatically {{ $user->deleted_at->addDays(90)->format('M j, Y') }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <x-ui::button size="sm" variant="ghost" wire:click="restoreUser({{ $user->id }})">Restore</x-ui::button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived users found.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $this->archivedUsers->links() }}</div>
        </div>
        @endif
    </div>
</div>
