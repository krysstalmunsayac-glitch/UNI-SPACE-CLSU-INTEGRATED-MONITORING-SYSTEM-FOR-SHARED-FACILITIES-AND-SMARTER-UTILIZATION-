<?php

use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $archiveStatusFilter = '';

    public function updatedArchiveStatusFilter(): void
    {
        $this->resetPage('archivedRequestsPage');
    }

    #[Computed]
    public function archivedRequests()
    {
        $query = Requests::query()->onlyTrashed();

        if (auth()->user()?->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($facilityQuery) =>
                $facilityQuery->where('users.id', auth()->id())
            );
        }

        if (in_array($this->archiveStatusFilter, ['Cancelled', 'Approved', 'Rejected'], true)) {
            $query->where('Status', $this->archiveStatusFilter);
        }

        return $query->with(['user', 'facility'])
            ->orderByDesc('deleted_at')
            ->paginate(10, pageName: 'archivedRequestsPage');
    }

    #[Computed]
    public function archivedSchedules()
    {
        $query = Schedule::query()->onlyTrashed();

        if (auth()->user()?->isAdmin()) {
            $query->whereHas('request', function ($requestQuery) {
                $requestQuery->withTrashed()->whereHas('facility.assignedAdmins', fn ($facilityQuery) =>
                    $facilityQuery->where('users.id', auth()->id())
                );
            });
        }

        return $query->with(['request' => fn ($requestQuery) => $requestQuery->withTrashed()->with('facility')])
            ->orderByDesc('deleted_at')
            ->paginate(10);
    }

    #[Computed]
    public function archivedEvents()
    {
        $query = Events::query()->onlyTrashed();

        return $query->orderByDesc('deleted_at')->paginate(10);
    }

    #[Computed]
    public function archivedUsers()
    {
        $query = User::query()->onlyTrashed();

        return $query->orderByDesc('deleted_at')->paginate(10);
    }

    public function restoreRequest(int $id): void
    {
        Requests::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('$refresh');
    }

    public function restoreSchedule(int $id): void
    {
        Schedule::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('$refresh');
    }

    public function restoreEvent(int $id): void
    {
        Events::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('$refresh');
    }

    public function restoreUser(int $id): void
    {
        User::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('$refresh');
    }

    public function forceDeleteRequest(int $id): void
    {
        Requests::withTrashed()->findOrFail($id)->forceDelete();
        $this->dispatch('$refresh');
    }

    public function forceDeleteSchedule(int $id): void
    {
        Schedule::withTrashed()->findOrFail($id)->forceDelete();
        $this->dispatch('$refresh');
    }

    public function forceDeleteEvent(int $id): void
    {
        Events::withTrashed()->findOrFail($id)->forceDelete();
        $this->dispatch('$refresh');
    }

    public function forceDeleteUser(int $id): void
    {
        User::withTrashed()->findOrFail($id)->forceDelete();
        $this->dispatch('$refresh');
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Archived Records</h1>
        <p class="text-gray-600 dark:text-gray-400">View deleted requests and schedules for your assigned facilities.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Requests</h2>
                <flux:select wire:model.live="archiveStatusFilter" label="Request status" class="sm:w-48">
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="Cancelled">Cancelled</flux:select.option>
                    <flux:select.option value="Approved">Approved</flux:select.option>
                    <flux:select.option value="Rejected">Rejected</flux:select.option>
                </flux:select>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedRequests as $request)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Request #{{ $request->RID }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->facility?->Facility_Name ?? 'Unassigned facility' }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $request->Status }}</p>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="restoreRequest({{ $request->RID }})">Restore</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="forceDeleteRequest({{ $request->RID }})" wire:confirm="Delete this archived request permanently?">Delete</flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived requests found.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Schedules</h2>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedSchedules as $schedule)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Schedule #{{ $schedule->SID }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $schedule->request?->facility?->Facility_Name ?? 'Unassigned facility' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="restoreSchedule({{ $schedule->SID }})">Restore</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="forceDeleteSchedule({{ $schedule->SID }})" wire:confirm="Delete this archived schedule permanently?">Delete</flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived schedules found.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Events</h2>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedEvents as $event)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Event #{{ $event->EID }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $event->Event_Title }}</p>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="restoreEvent({{ $event->EID }})">Restore</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="forceDeleteEvent({{ $event->EID }})" wire:confirm="Delete this archived event permanently?">Delete</flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived events found.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Archived Users</h2>
            <div class="mt-4 space-y-3">
                @forelse ($this->archivedUsers as $user)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">User #{{ $user->id }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->name }}</p>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="restoreUser({{ $user->id }})">Restore</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="forceDeleteUser({{ $user->id }})" wire:confirm="Delete this archived user permanently?">Delete</flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No archived users found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
