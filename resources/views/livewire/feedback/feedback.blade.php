<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Support\Ui;
use App\Models\Feedbacks;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $searchInput = '';
    public string $search = '';
    public string $sortBy = 'Created_at';
    public string $sortDirection = 'desc';

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
        $feedback = Feedbacks::findOrFail($feedbackId);
        $feedback->delete();

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

    #[Computed]
    public function feedbacks()
    {
        $query = Feedbacks::query()->with(['user:id,name', 'facility:FID,Facility_Name']);

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facility', fn ($facilityQuery) =>
                $facilityQuery->whereHas('assignedAdmins', fn ($adminQuery) =>
                    $adminQuery->where('users.id', auth()->id())
                )
            );
        }

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
}; ?>

<div class="w-full">
    @include('feedback.components.page-header')
    @include('feedback.components.feedback-table')
</div>
