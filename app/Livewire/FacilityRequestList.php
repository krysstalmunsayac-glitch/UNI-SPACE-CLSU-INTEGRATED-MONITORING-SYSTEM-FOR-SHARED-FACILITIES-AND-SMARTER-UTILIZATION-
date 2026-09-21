<?php

namespace App\Livewire;

use App\Models\FacilityRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FacilityRequestList extends Component
{
    use WithPagination;

    #[Url(as: 'request_sort', except: 'latest')]
    public string $requestSort = 'latest';

    #[Url(as: 'request_status', except: '')]
    public string $requestStatus = '';

    public function mount(): void
    {
        $this->normalizeFilters();
    }

    public function updatedRequestSort(): void
    {
        $this->normalizeFilters();
        $this->resetPage('requests_page');
    }

    public function updatedRequestStatus(): void
    {
        $this->normalizeFilters();
        $this->resetPage('requests_page');
    }

    public function render(): View
    {
        return view('livewire.facility-request-list', [
            'requests' => $this->requestsQuery()->paginate(5, ['*'], 'requests_page'),
            'totalUserRequests' => $this->requestsQuery(false)->count(),
        ]);
    }

    private function requestsQuery(bool $applyFilters = true): Builder
    {
        return FacilityRequest::withTrashed()
            ->where('User_ID', Auth::id())
            ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhere('Status', 'Ended'))
            ->with(['facility', 'event', 'feedback'])
            ->when($applyFilters && $this->requestStatus === 'Needs Revision', fn (Builder $query) => $query->where('Status', 'Pending')->whereNotNull('Review_Requested_At'))
            ->when($applyFilters && $this->requestStatus === 'Pending', fn (Builder $query) => $query->where('Status', 'Pending')->whereNull('Review_Requested_At'))
            ->when(
                $applyFilters && $this->requestStatus && ! in_array($this->requestStatus, ['Pending', 'Needs Revision'], true),
                fn (Builder $query) => $query->where('Status', $this->requestStatus),
            )
            ->orderBy('Created_at', $this->requestSort === 'oldest' ? 'asc' : 'desc')
            ->orderBy('RID', $this->requestSort === 'oldest' ? 'asc' : 'desc');
    }

    private function normalizeFilters(): void
    {
        if (! in_array($this->requestSort, ['latest', 'oldest'], true)) {
            $this->requestSort = 'latest';
        }

        if (! in_array($this->requestStatus, ['', 'Pending', 'Needs Revision', 'Awaiting Payment', 'Approved', 'Rejected', 'Cancelled', 'Ended'], true)) {
            $this->requestStatus = '';
        }
    }
}
