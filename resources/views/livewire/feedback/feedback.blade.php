<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Flux\Flux;
use App\Models\Feedbacks;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $searchInput = '';
    public string $search = '';
    public string $sortBy = 'Created_at';
    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $feedbackId): void
    {
        $feedback = Feedbacks::findOrFail($feedbackId);
        $feedback->delete();

        Flux::toast(text: 'Feedback archived successfully!', variant: 'success');
        $this->dispatch(
            'swal',
            [
                'title' => 'Feedback archived',
                'text' => 'Feedback archived successfully!',
                'icon' => 'success',
            ]
        );
    }

    public function feedbacks()
    {
        $query = Feedbacks::query()->with('user');

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
            ->paginate(10);
    }
}; ?>

<div class="w-full">
    @include('feedback.components.page-header')
    @include('feedback.components.feedback-table')
</div>
