<?php

namespace App\Livewire\Feedback;

use App\Actions\Lifecycle\ArchiveRecord;
use App\Models\Feedback;
use App\Support\Ui;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class FeedbackManagement extends Component
{
    use WithPagination;

    public string $searchInput = '';

    public string $search = '';

    public string $sortBy = 'Created_at';

    public string $sortDirection = 'desc';

    public bool $showViewModal = false;

    public ?int $viewingId = null;

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('feedbackPage');
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['User_ID', 'Comment', 'Created_at'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('feedbackPage');
    }

    public function delete(int $feedbackId): void
    {
        $feedback = $this->feedbackQuery()->findOrFail($feedbackId);
        app(ArchiveRecord::class)->handle($feedback);

        if ($this->viewingId === $feedbackId) {
            $this->closeView();
        }

        Ui::toast(text: 'Feedback archived successfully!', variant: 'success');
        $this->dispatch(
            'swal',
            [
                'title' => 'Feedback archived',
                'text' => 'Feedback archived successfully!',
                'icon' => 'success',
            ]
        );
    }

    public function showDetails(int $feedbackId): void
    {
        $feedback = $this->feedbackQuery()->findOrFail($feedbackId);

        $this->viewingId = $feedback->getKey();
        $this->showViewModal = true;
    }

    public function closeView(): void
    {
        $this->showViewModal = false;
        $this->viewingId = null;
    }

    private function feedbackQuery(): Builder
    {
        $query = Feedback::query();

        if (auth()->user()->isAdmin()) {
            $query->where(function (Builder $query): void {
                $query
                    ->whereHas('facility', fn (Builder $facilityQuery) => $facilityQuery->whereHas('assignedAdmins', fn (Builder $adminQuery) => $adminQuery->where('users.id', auth()->id())
                    )
                    )
                    ->orWhereHas('request.facility', fn (Builder $facilityQuery) => $facilityQuery->whereHas('assignedAdmins', fn (Builder $adminQuery) => $adminQuery->where('users.id', auth()->id())
                    )
                    );
            });
        }

        return $query;
    }

    #[Computed]
    public function viewingFeedback(): ?Feedback
    {
        if (! $this->showViewModal || ! $this->viewingId) {
            return null;
        }

        return $this->feedbackQuery()
            ->with([
                'user:id,name,email',
                'facility:FID,Facility_Name,Office',
                'request:RID,Facility_ID',
                'request.facility:FID,Facility_Name,Office',
            ])
            ->findOrFail($this->viewingId);
    }

    #[Computed]
    public function feedbacks()
    {
        $query = $this->feedbackQuery()->with([
            'user:id,name',
            'facility:FID,Facility_Name',
            'request:RID,Facility_ID',
            'request.facility:FID,Facility_Name',
        ]);

        if ($this->search) {
            $query->where(function ($query) {
                $query->where('Comment', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($sub) {
                        $sub->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(8, pageName: 'feedbackPage');
    }

    public function render(): View
    {
        return view('livewire.feedback.index');
    }
}
