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

    public function updatedSearchInput(): void
    {
        $this->applyFilters();
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
            ->with([
                'actor:id,name,user_type',
                'requestRecord:RID,User_ID,Facility_ID',
                'requestRecord.user:id,name',
                'requestRecord.facility:FID,Facility_Name',
            ])
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
            ->paginate(8, pageName: 'auditPage');
    }

    #[Computed]
    public function stats(): array
    {
        $startOfToday = today()->startOfDay();
        $startOfTomorrow = today()->addDay()->startOfDay();

        $stats = AuditLog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today_count',
                [$startOfToday, $startOfTomorrow],
            )
            ->selectRaw("SUM(CASE WHEN action = 'request_approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN action = 'request_rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        return [
            'total' => (int) $stats->total,
            'today' => (int) $stats->today_count,
            'approved' => (int) $stats->approved,
            'rejected' => (int) $stats->rejected,
        ];
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                <x-ui::icon.clipboard-document-list class="size-6" />
            </span>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Report Management</h1>
                <p class="text-gray-600 dark:text-gray-400">Review request activity and download administrative reports.</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 sm:justify-end">
            <x-ui::dropdown position="bottom" align="end">
                <x-ui::button variant="outline" class="w-32 justify-start gap-2">
                    <x-ui::icon.building-office class="size-4 shrink-0" />
                    Facilities
                </x-ui::button>
                <x-ui::menu>
                    <x-ui::menu.item icon="document-text" href="{{ route('exports.facilities.csv') }}">CSV</x-ui::menu.item>
                    <x-ui::menu.item icon="table-cells" href="{{ route('exports.facilities.xlsx') }}">Excel (.xlsx)</x-ui::menu.item>
                    <x-ui::menu.item icon="document" href="{{ route('exports.facilities.pdf') }}">PDF</x-ui::menu.item>
                </x-ui::menu>
            </x-ui::dropdown>

            <x-ui::dropdown position="bottom" align="end">
                <x-ui::button variant="outline" class="w-32 justify-start gap-2">
                    <x-ui::icon.document-text class="size-4 shrink-0" />
                    Requests
                </x-ui::button>
                <x-ui::menu>
                    <x-ui::menu.item icon="document-text" href="{{ route('exports.requests.csv') }}">CSV</x-ui::menu.item>
                    <x-ui::menu.item icon="table-cells" href="{{ route('exports.requests.xlsx') }}">Excel (.xlsx)</x-ui::menu.item>
                    <x-ui::menu.item icon="document" href="{{ route('exports.requests.pdf') }}">PDF</x-ui::menu.item>
                </x-ui::menu>
            </x-ui::dropdown>

            <x-ui::dropdown position="bottom" align="end">
                <x-ui::button variant="outline" class="w-32 justify-start gap-2">
                    <x-ui::icon.users class="size-4 shrink-0" />
                    Users
                </x-ui::button>
                <x-ui::menu>
                    <x-ui::menu.item icon="document-text" href="{{ route('exports.users.csv') }}">CSV</x-ui::menu.item>
                    <x-ui::menu.item icon="table-cells" href="{{ route('exports.users.xlsx') }}">Excel (.xlsx)</x-ui::menu.item>
                    <x-ui::menu.item icon="document" href="{{ route('exports.users.pdf') }}">PDF</x-ui::menu.item>
                </x-ui::menu>
            </x-ui::dropdown>

            <x-ui::dropdown position="bottom" align="end">
                <x-ui::button variant="outline" class="w-32 justify-start gap-2">
                    <x-ui::icon.rectangle-stack class="size-4 shrink-0" />
                    Amenities
                </x-ui::button>
                <x-ui::menu>
                    <x-ui::menu.item icon="document-text" href="{{ route('exports.amenities.csv') }}">CSV</x-ui::menu.item>
                    <x-ui::menu.item icon="table-cells" href="{{ route('exports.amenities.xlsx') }}">Excel (.xlsx)</x-ui::menu.item>
                    <x-ui::menu.item icon="document" href="{{ route('exports.amenities.pdf') }}">PDF</x-ui::menu.item>
                </x-ui::menu>
            </x-ui::dropdown>
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
                <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-zinc-950 dark:text-white">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <x-ui::card>
        <div class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(200px,1.2fr)_minmax(170px,1fr)_minmax(140px,.8fr)_minmax(140px,.8fr)_auto] xl:items-end">
            <x-ui::input wire:model.live.debounce.400ms="searchInput" label="Search" placeholder="Actor, Request ID, or action..." icon="magnifying-glass" />

            <x-ui::select wire:model.live="actionFilter" label="Action">
                <x-ui::select.option value="">All actions</x-ui::select.option>
                <x-ui::select.option value="request_submitted">Submitted</x-ui::select.option>
                <x-ui::select.option value="request_approved">Approved</x-ui::select.option>
                <x-ui::select.option value="request_rejected">Rejected</x-ui::select.option>
                <x-ui::select.option value="revision_requested">Revision requested</x-ui::select.option>
                <x-ui::select.option value="request_updated">Updated</x-ui::select.option>
                <x-ui::select.option value="request_cancelled">Cancelled</x-ui::select.option>
                <x-ui::select.option value="event_ended">Event ended</x-ui::select.option>
                <x-ui::select.option value="request_archived">Archived</x-ui::select.option>
                <x-ui::select.option value="request_restored">Restored</x-ui::select.option>
                <x-ui::select.option value="request_deleted">Deleted</x-ui::select.option>
            </x-ui::select>

            <x-ui::input wire:model="dateFrom" type="date" label="From" />
            <x-ui::input wire:model="dateTo" type="date" label="To" />
            <div class="flex gap-2 md:col-span-2 xl:col-span-1">
                <x-ui::button wire:click="applyFilters" icon="funnel" class="flex-1">Apply</x-ui::button>
                <x-ui::button wire:click="clearFilters" variant="outline" icon="x-mark" class="flex-1">Clear</x-ui::button>
            </div>
        </div>

        <x-ui::table :paginate="$this->logs">
            <x-ui::table.columns>
                <x-ui::table.column>Date & time</x-ui::table.column>
                <x-ui::table.column>Performed by</x-ui::table.column>
                <x-ui::table.column>Action</x-ui::table.column>
                <x-ui::table.column>Request</x-ui::table.column>
                <x-ui::table.column>User / facility</x-ui::table.column>
                <x-ui::table.column>Details</x-ui::table.column>
            </x-ui::table.columns>

            <x-ui::table.rows>
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
                    <x-ui::table.row :key="$log->id">
                        <x-ui::table.cell class="whitespace-nowrap">
                            <div class="font-medium">{{ $log->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $log->created_at->format('h:i:s A') }}</div>
                        </x-ui::table.cell>
                        <x-ui::table.cell>
                            <div class="font-medium">{{ $log->actor?->name ?? 'System' }}</div>
                            <div class="text-xs text-zinc-500">{{ $log->actor?->roleLabel() ?? 'Automated action' }}</div>
                        </x-ui::table.cell>
                        <x-ui::table.cell><x-ui::badge size="sm" :color="$badgeColor">{{ $actionLabel }}</x-ui::badge></x-ui::table.cell>
                        <x-ui::table.cell class="font-medium">#{{ $log->auditable_id }}</x-ui::table.cell>
                        <x-ui::table.cell>
                            <div>{{ $log->requestRecord?->user?->name ?? '—' }}</div>
                            <div class="text-xs text-zinc-500">{{ $log->requestRecord?->facility?->Facility_Name ?? '—' }}</div>
                        </x-ui::table.cell>
                        <x-ui::table.cell>
                            <p class="max-w-sm">{{ $log->description }}</p>
                            @if ($log->new_values)
                                <p class="mt-1 max-w-md whitespace-normal break-words text-xs text-zinc-500">
                                    {{ collect($log->new_values)->map(fn ($value, $key) => str($key)->replace('_', ' ')->title().': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') }}
                                </p>
                            @endif
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @empty
                    <x-ui::table.row>
                        <x-ui::table.cell colspan="6">
                            <div class="flex flex-col items-center justify-center gap-2 py-12 text-center text-zinc-500">
                                <x-ui::icon.clipboard-document-list class="size-9 text-zinc-300" />
                                <p>No audit records match the selected filters.</p>
                            </div>
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @endforelse
            </x-ui::table.rows>
        </x-ui::table>
    </x-ui::card>
</div>
