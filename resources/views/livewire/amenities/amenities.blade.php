<?php

use App\Models\Amenities;
use App\Models\Facilities;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $editingId = null;
    public bool $showModal = false;
    public bool $showArchivedModal = false;
    public string $searchInput = '';
    public string $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public ?string $Description = null;

    #[Validate('nullable|array')]
    public array $facilityIds = [];

    #[Validate('required|in:Available,Unavailable')]
    public string $Status = 'Available';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function sort($column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'Description']);
        $this->Status = 'Available';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $amenity = $this->getScopedAmenity($this->editingId);
            $amenity->update([
                'name' => $this->name,
                'Description' => $this->Description,
                'Status' => $this->Status,
            ]);
            $amenity->facilities()->sync($this->facilityIds);
        } else {
            $amenity = Amenities::create([
                'name' => $this->name,
                'Description' => $this->Description,
                'Status' => $this->Status,
            ]);
            $amenity->facilities()->sync($this->facilityIds);
        }

        Flux::toast(
            text: $this->editingId ? 'Amenity updated successfully!' : 'Amenity created successfully!',
            variant: 'success'
        );

        if ($this->editingId) {
            $this->dispatch(
                'swal',
                [
                    'title' => 'Amenity updated',
                    'text' => 'Amenity updated successfully!',
                    'icon' => 'success',
                ]
            );
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function edit(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId);

        $this->editingId = $amenity->AID;
        $this->name = $amenity->name;
        $this->Description = $amenity->Description;
        $this->Status = $amenity->Status;
        $this->facilityIds = $amenity->facilities->pluck('FID')->toArray();
        $this->showModal = true;
    }

    public function toggleStatus(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId);

        $amenity->update([
            'Status' => $amenity->Status === 'Available'
                ? 'Unavailable'
                : 'Available',
        ]);

        Flux::toast(
            text: $amenity->Status === 'Available'
                ? 'Amenity is now available.'
                : 'Amenity is now unavailable and cannot be selected in new requests.',
            variant: 'success'
        );
    }

    public function delete(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId);
        $amenity->delete();

        Flux::toast(text: 'Amenity archived successfully!', variant: 'success');
        $this->dispatch(
            'swal',
            [
                'title' => 'Amenity archived',
                'text' => 'Amenity archived successfully!',
                'icon' => 'success',
            ]
        );
    }

    public function openArchivedRecords(): void
    {
        $this->showArchivedModal = true;
    }

    public function restore(int $amenityId): void
    {
        $amenity = Amenities::withTrashed()->findOrFail($amenityId);
        $amenity->restore();

        Flux::toast(text: 'Amenity restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $amenityId): void
    {
        $amenity = Amenities::withTrashed()->findOrFail($amenityId);
        $amenity->forceDelete();

        Flux::toast(text: 'Amenity permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function amenities()
    {
        $query = Amenities::query()
            ->when($this->search, fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('Description', 'like', "%{$this->search}%"));

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facilities', function ($query) {
                $query->where('Office', auth()->user()->office);
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    #[Computed]
    public function archivedAmenities()
    {
        $query = Amenities::query()->onlyTrashed();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facilities', function ($query) {
                $query->where('Office', auth()->user()->office);
            });
        }

        return $query->orderByDesc('deleted_at')->paginate(10);
    }

    #[Computed]
    public function facilityOptions()
    {
        $query = Facilities::query();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('assignedAdmins', fn ($adminQuery) =>
                $adminQuery->where('users.id', auth()->id())
            );
        }

        return $query->orderBy('Facility_Name')->get();
    }

    private function getScopedAmenity(int $amenityId): Amenities
    {
        $query = Amenities::query();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facilities', function ($query) {
                $query->whereHas('assignedAdmins', fn ($adminQuery) =>
                    $adminQuery->where('users.id', auth()->id())
                );
            });
        }

        return $query->findOrFail($amenityId);
    }
}; ?>

<div class="w-full">
    @include('amenities.components.page-header')
    @include('amenities.components.amenities-table')
    @include('amenities.components.archived-amenities-modal')
    @include('amenities.components.amenity-form-modal')
</div>
