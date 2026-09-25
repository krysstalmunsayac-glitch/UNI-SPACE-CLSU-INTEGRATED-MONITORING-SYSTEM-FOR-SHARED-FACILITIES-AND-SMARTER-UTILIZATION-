<?php

namespace App\Livewire\Archives;

use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Models\Event;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ArchiveManagement extends Component
{
    use WithPagination;

    public string $archiveStatusFilter = '';

    public string $archiveMonthFilter = '';

    public string $archiveYearFilter = '';

    public function updatedArchiveStatusFilter(): void
    {
        $this->resetPage('archivedRequestsPage');
    }

    public function updatedArchiveMonthFilter(): void
    {
        $this->resetPage('archivedRequestsPage');
    }

    public function updatedArchiveYearFilter(): void
    {
        $this->resetPage('archivedRequestsPage');
    }

    #[Computed]
    public function archivedRequests()
    {
        $query = FacilityRequest::query()->onlyTrashed();

        if (auth()->user()?->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($facilityQuery) => $facilityQuery->where('users.id', auth()->id())
            );
        }

        if (in_array($this->archiveStatusFilter, ['Cancelled', 'Approved', 'Rejected', 'Expired', 'Ended'], true)) {
            $query->where('Status', $this->archiveStatusFilter);
        }

        if ($this->archiveMonthFilter !== '') {
            $query->whereMonth('deleted_at', (int) $this->archiveMonthFilter);
        }

        if ($this->archiveYearFilter !== '') {
            $query->whereYear('deleted_at', (int) $this->archiveYearFilter);
        }

        return $query->with([
            'user:id,name',
            'facility:FID,Facility_Name',
        ])
            ->orderBy('deleted_at')
            ->orderBy('RID')
            ->paginate(8, pageName: 'archivedRequestsPage');
    }

    #[Computed]
    public function archiveYears()
    {
        $query = FacilityRequest::query()->onlyTrashed()
            ->whereNotNull('deleted_at')
            ->orderByDesc('deleted_at');

        if (auth()->user()?->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($facilityQuery) => $facilityQuery->where('users.id', auth()->id())
            );
        }

        return $query->get(['deleted_at'])
            ->map(fn (FacilityRequest $request) => $request->deleted_at?->year)
            ->filter()
            ->unique()
            ->values();
    }

    public function archivePeriodLabel(): string
    {
        $month = $this->archiveMonthFilter !== ''
            ? now()->month((int) $this->archiveMonthFilter)->format('F')
            : null;

        return match (true) {
            $month && $this->archiveYearFilter !== '' => "{$month} {$this->archiveYearFilter}",
            $month => $month.' across all years',
            $this->archiveYearFilter !== '' => 'all months in '.$this->archiveYearFilter,
            default => 'all archived months and years',
        };
    }

    #[Computed]
    public function archivedSchedules()
    {
        $query = Schedule::query()->onlyTrashed();

        if (auth()->user()?->isAdmin()) {
            $query->whereHas('request', function ($requestQuery) {
                $requestQuery->withTrashed()->whereHas('facility.assignedAdmins', fn ($facilityQuery) => $facilityQuery->where('users.id', auth()->id())
                );
            });
        }

        return $query->with([
            'request' => fn ($requestQuery) => $requestQuery
                ->withTrashed()
                ->select(['RID', 'Facility_ID', 'Purpose'])
                ->with('facility:FID,Facility_Name'),
        ])
            ->orderBy('deleted_at')
            ->orderBy('SID')
            ->paginate(8, pageName: 'archivedSchedulesPage');
    }

    #[Computed]
    public function archivedEvents()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $query = Event::query()->onlyTrashed();

        return $query->orderBy('deleted_at')
            ->orderBy('EID')
            ->paginate(8, pageName: 'archivedEventsPage');
    }

    #[Computed]
    public function archivedUsers()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $query = User::query()->onlyTrashed();

        return $query->orderBy('deleted_at')
            ->orderBy('id')
            ->paginate(8, pageName: 'archivedUsersPage');
    }

    public function restoreRequest(int $id): void
    {
        app(RestoreRecord::class)->handle($this->archivedRequest($id));
        $this->dispatch('$refresh');
    }

    public function restoreSchedule(int $id): void
    {
        app(RestoreRecord::class)->handle($this->archivedSchedule($id));
        $this->dispatch('$refresh');
    }

    public function restoreEvent(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        app(RestoreRecord::class)->handle(Event::onlyTrashed()->findOrFail($id));
        $this->dispatch('$refresh');
    }

    public function restoreUser(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        app(RestoreRecord::class)->handle(User::onlyTrashed()->findOrFail($id));
        $this->dispatch('$refresh');
    }

    public function forceDeleteRequest(int $id): void
    {
        app(PermanentlyDeleteRecord::class)->handle($this->archivedRequest($id));
        $this->dispatch('$refresh');
    }

    public function forceDeleteSchedule(int $id): void
    {
        app(PermanentlyDeleteRecord::class)->handle($this->archivedSchedule($id));
        $this->dispatch('$refresh');
    }

    public function forceDeleteEvent(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        app(PermanentlyDeleteRecord::class)->handle(Event::onlyTrashed()->findOrFail($id));
        $this->dispatch('$refresh');
    }

    private function archivedRequest(int $id): FacilityRequest
    {
        return FacilityRequest::onlyTrashed()
            ->when(auth()->user()?->isAdmin(), fn ($query) => $query->whereHas(
                'facility.assignedAdmins',
                fn ($facilityQuery) => $facilityQuery->where('users.id', auth()->id()),
            ))
            ->findOrFail($id);
    }

    private function archivedSchedule(int $id): Schedule
    {
        return Schedule::onlyTrashed()
            ->when(auth()->user()?->isAdmin(), fn ($query) => $query->whereHas(
                'request.facility.assignedAdmins',
                fn ($adminQuery) => $adminQuery->where('users.id', auth()->id()),
            ))
            ->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.archives.index');
    }
}
