<?php

namespace App\Livewire\Reports;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ReportManagement extends Component
{
    use WithPagination;

    public string $searchInput = '';

    public string $search = '';

    public string $actionFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showViewModal = false;

    public ?int $viewingId = null;

    public function viewLog(int $logId): void
    {
        AuditLog::query()->findOrFail($logId);
        $this->viewingId = $logId;
        $this->showViewModal = true;
    }

    #[Computed]
    public function viewingLog(): ?AuditLog
    {
        if (! $this->viewingId) {
            return null;
        }

        return AuditLog::query()->with([
            'actor:id,name,user_type',
            'requestRecord:RID,User_ID,Facility_ID',
            'requestRecord.user:id,name,email',
            'requestRecord.facility:FID,Facility_Name',
        ])->find($this->viewingId);
    }

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
                        ->orWhereHas('actor', fn ($actorQuery) => $actorQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                        );
                });
            })
            ->when($this->actionFilter, fn ($query) => $query->where('action', $this->actionFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at')
            ->orderBy('id')
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

    public function render(): View
    {
        return view('livewire.reports.index');
    }
}
