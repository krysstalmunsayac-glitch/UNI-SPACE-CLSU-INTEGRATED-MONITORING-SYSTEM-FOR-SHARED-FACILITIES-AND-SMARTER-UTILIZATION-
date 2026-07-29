<?php

use App\Models\AuditLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $searchInput = '';
    public string $search = '';
    public string $actionFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function applyFilters(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('auditPage');
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage('auditPage');
    }

    public function clearFilters(): void
    {
        $this->reset('searchInput', 'search', 'actionFilter', 'dateFrom', 'dateTo');
        $this->resetPage('auditPage');
    }

    #[Computed]
    public function logs()
    {
        return AuditLog::query()
            ->with(['actor', 'requestRecord.user', 'requestRecord.facility'])
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('description', 'like', "%{$this->search}%")
                        ->orWhere('auditable_id', 'like', "%{$this->search}%")
                        ->orWhereHas('actor', fn ($actorQuery) =>
                            $actorQuery->where('name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%")
                        );
                });
            })
            ->when($this->actionFilter, fn ($query) => $query->where('action', $this->actionFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(10, pageName: 'auditPage');
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => AuditLog::query()->count(),
            'today' => AuditLog::query()->whereDate('created_at', today())->count(),
            'approved' => AuditLog::query()->where('action', 'request_approved')->count(),
            'rejected' => AuditLog::query()->where('action', 'request_rejected')->count(),
        ];
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex items-center gap-3">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
            <flux:icon.clipboard-document-list class="size-6" />
        </span>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Report Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Review request activity and identify who performed each action.</p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'All audit records', 'value' => $this->stats['total'], 'tone' => 'slate'],
            ['label' => 'Actions today', 'value' => $this->stats['today'], 'tone' => 'yellow'],
            ['label' => 'Requests approved', 'value' => $this->stats['approved'], 'tone' => 'green'],
            ['label' => 'Requests rejected', 'value' => $this->stats['rejected'], 'tone' => 'red'],
        ] as $stat)
            <div @class([
                'rounded-xl border p-5 shadow-sm',
                'border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950' => $stat['tone'] === 'slate',
                'border-yellow-300 bg-yellow-50 dark:border-yellow-500/30 dark:bg-yellow-500/10' => $stat['tone'] === 'yellow',
                'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' => $stat['tone'] === 'green',
                'border-rose-200 bg-rose-50 dark:border-rose-500/30 dark:bg-rose-500/10' => $stat['tone'] === 'red',
            ])>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <flux:card>
        <div class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(200px,1.2fr)_minmax(170px,1fr)_minmax(140px,.8fr)_minmax(140px,.8fr)_auto] xl:items-end">
            <flux:input wire:model="searchInput" wire:keydown.enter="applyFilters" label="Search" placeholder="Actor, Request ID, or action..." icon="magnifying-glass" />

            <flux:select wire:model.live="actionFilter" label="Action">
                <flux:select.option value="">All actions</flux:select.option>
                <flux:select.option value="request_submitted">Submitted</flux:select.option>
                <flux:select.option value="request_approved">Approved</flux:select.option>
                <flux:select.option value="request_rejected">Rejected</flux:select.option>
                <flux:select.option value="revision_requested">Revision requested</flux:select.option>
                <flux:select.option value="request_updated">Updated</flux:select.option>
                <flux:select.option value="request_cancelled">Cancelled</flux:select.option>
                <flux:select.option value="event_ended">Event ended</flux:select.option>
                <flux:select.option value="request_archived">Archived</flux:select.option>
                <flux:select.option value="request_restored">Restored</flux:select.option>
                <flux:select.option value="request_deleted">Deleted</flux:select.option>
            </flux:select>

            <flux:input wire:model="dateFrom" type="date" label="From" />
            <flux:input wire:model="dateTo" type="date" label="To" />
            <div class="flex gap-2 md:col-span-2 xl:col-span-1">
                <flux:button wire:click="applyFilters" icon="funnel" class="flex-1">Apply</flux:button>
                <flux:button wire:click="clearFilters" variant="outline" icon="x-mark" class="flex-1">Clear</flux:button>
            </div>
        </div>

        <flux:table :paginate="$this->logs">
            <flux:table.columns>
                <flux:table.column>Date & Time</flux:table.column>
                <flux:table.column>Performed By</flux:table.column>
                <flux:table.column>Action</flux:table.column>
                <flux:table.column>Request</flux:table.column>
                <flux:table.column>User / Facility</flux:table.column>
                <flux:table.column>Details</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->logs as $log)
                    @php
                        $badgeColor = match ($log->action) {
                            'request_approved' => 'green',
                            'request_rejected', 'request_deleted' => 'red',
                            'revision_requested', 'request_cancelled' => 'amber',
                            'event_ended' => 'zinc',
                            default => 'blue',
                        };
                        $actionLabel = str($log->action)->replace('_', ' ')->title();
                    @endphp
                    <flux:table.row :key="$log->id">
                        <flux:table.cell class="whitespace-nowrap">
                            <div class="font-medium">{{ $log->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $log->created_at->format('h:i:s A') }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">{{ $log->actor?->name ?? 'System' }}</div>
                            <div class="text-xs text-zinc-500">{{ $log->actor?->roleLabel() ?? 'Automated action' }}</div>
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$badgeColor">{{ $actionLabel }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="font-medium">#{{ $log->auditable_id }}</flux:table.cell>
                        <flux:table.cell>
                            <div>{{ $log->requestRecord?->user?->name ?? 'Unavailable user' }}</div>
                            <div class="text-xs text-zinc-500">{{ $log->requestRecord?->facility?->Facility_Name ?? 'No facility' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <p class="max-w-sm">{{ $log->description }}</p>
                            @if ($log->new_values)
                                <p class="mt-1 max-w-sm truncate text-xs text-zinc-500">
                                    {{ collect($log->new_values)->map(fn ($value, $key) => str($key)->replace('_', ' ')->title().': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') }}
                                </p>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="flex flex-col items-center justify-center gap-2 py-12 text-center text-zinc-500">
                                <flux:icon.clipboard-document-list class="size-9 text-zinc-300" />
                                <p>No audit records match the selected filters.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
