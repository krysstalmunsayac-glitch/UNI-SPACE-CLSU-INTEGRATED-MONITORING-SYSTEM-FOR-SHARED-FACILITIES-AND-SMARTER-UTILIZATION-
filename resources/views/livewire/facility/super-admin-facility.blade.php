<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Services\FacilityAvailabilityService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;

    public bool $showModal = false;
    public bool $showArchivedModal = false;

    public string $searchInput = '';
    public string $search = '';
    public string $statusFilter = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    #[Validate('required|string|max:255')]
    public string $Facility_Name = '';

    #[Validate(['images' => 'nullable|array|max:5', 'images.*' => 'image|max:5120'])]
    public array $images = [];

    public array $existingImages = [];

    #[Validate('nullable|in:sports,conference,auditorium,classroom,laboratory,other')]
    public ?string $facility_type = null;

    #[Validate('required|numeric|min:0')]
    public float $Price = 0;

    #[Validate('nullable|string|max:255')]
    public ?string $Office = null;

    #[Validate('nullable|string')]
    public ?string $Description = null;

    #[Validate('nullable|string|max:255')]
    public ?string $Location = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $Capacity = null;

    #[Validate('required|in:Available,Under Maintenance,Unavailable')]
    public string $Status = 'Available';

    #[Validate('nullable|array')]
    public array $selectedAmenityIds = [];

    #[Computed]
    public function amenities()
    {
        return Amenities::query()
            ->where('Status', 'Available')
            ->orderBy('name')
            ->get();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('facilitiesPage');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage('facilitiesPage');
    }

    public function sort(string $column): void
    {
        $allowedColumns = ['Facility_Name', 'facility_type', 'Price', 'Status', 'created_at'];

        if (! in_array($column, $allowedColumns, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('facilitiesPage');
    }

    public function resetForm(): void
    {
        $this->reset([
            'Facility_Name',
            'facility_type',
            'images',
            'existingImages',
            'Price',
            'Office',
            'Description',
            'Location',
            'Capacity',
            'selectedAmenityIds',
        ]);

        $this->editingId = null;
        $this->Status = 'Available';
        $this->selectedAmenityIds = [];
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $facilityId): void
    {
        $facility = Facilities::query()->findOrFail($facilityId);

        $this->editingId = $facility->FID;
        $this->Facility_Name = $facility->Facility_Name;
        $this->facility_type = $facility->facility_type;
        $this->Price = (float) $facility->Price;
        $this->Office = $facility->Office;
        $this->Description = $facility->Description;
        $this->Location = $facility->Location;
        $this->Capacity = $facility->Capacity;
        $this->Status = $facility->Status;
        $this->images = [];
        $this->existingImages = $facility->images()->pluck('image_path')->all();
        $this->selectedAmenityIds = $facility->amenities()->pluck('amenities.AID')->map(fn ($id) => (int) $id)->all();

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'Facility_Name' => $this->Facility_Name,
            'facility_type' => $this->facility_type,
            'Price' => $this->Price,
            'Office' => $this->Office,
            'Description' => $this->Description,
            'Location' => $this->Location,
            'Capacity' => $this->Capacity,
            'Status' => $this->Status,
        ];

        $wasEditing = $this->editingId !== null;

        $facility = $wasEditing
            ? tap(Facilities::query()->findOrFail($this->editingId))->update($data)
            : Facilities::query()->create($data);

        if ($wasEditing && $this->images !== []) {
            Storage::disk('public')->delete($facility->images()->pluck('image_path')->all());
            $facility->images()->delete();
        }

        foreach ($this->images as $image) {
            $path = $image->store('facilities', 'public');

            $facility->images()->create([
                'image_path' => $path,
            ]);
        }

        $facility->amenities()->sync($this->selectedAmenityIds);

        Flux::toast(
            text: $wasEditing
                ? 'Facility updated successfully!'
                : 'Facility created successfully!',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => $wasEditing ? 'Facility updated' : 'Facility created',
            'text' => $wasEditing
                ? 'Facility updated successfully!'
                : 'Facility created successfully!',
            'icon' => 'success',
        ]);

        $this->showModal = false;
        $this->resetForm();
        $this->resetPage('facilitiesPage');
    }

    public function archiveFacility(int $facilityId): void
    {
        $facility = Facilities::query()->findOrFail($facilityId);
        $facility->delete();

        Flux::toast(
            text: 'Facility moved to archived records.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Facility archived',
            'text' => 'The facility can be restored from Archived Facilities.',
            'icon' => 'success',
        ]);

        $this->resetPage('facilitiesPage');
        $this->resetPage('archivedFacilitiesPage');
    }

    public function openArchivedFacilities(): void
    {
        $this->resetPage('archivedFacilitiesPage');
        $this->showArchivedModal = true;
    }

    public function restoreFacility(int $facilityId): void
    {
        $facility = Facilities::onlyTrashed()->findOrFail($facilityId);
        $facility->restore();

        Flux::toast(
            text: 'Facility restored successfully!',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Facility restored',
            'text' => 'The facility is available in Facility Management again.',
            'icon' => 'success',
        ]);

        $this->resetPage('facilitiesPage');
        $this->resetPage('archivedFacilitiesPage');
    }

    public function forceDeleteFacility(int $facilityId): void
    {
        $facility = Facilities::onlyTrashed()
            ->with('images')
            ->findOrFail($facilityId);

        foreach ($facility->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $facility->assignedAdmins()->detach();
        $facility->images()->delete();
        $facility->forceDelete();

        Flux::toast(
            text: 'Facility permanently deleted.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Facility permanently deleted',
            'text' => 'This facility can no longer be restored.',
            'icon' => 'success',
        ]);

        $this->resetPage('archivedFacilitiesPage');
    }

    public function toggleStatus(int $facilityId): void
    {
        $facility = Facilities::query()->findOrFail($facilityId);
        $cancelledCount = app(FacilityAvailabilityService::class)->toggle($facility);
        $facility->refresh();

        Flux::toast(
            text: $facility->Status === 'Available'
                ? 'Facility reactivated successfully!'
                : "Facility deactivated. {$cancelledCount} active request(s) cancelled.",
            variant: 'success'
        );
    }

    #[Computed]
    public function facilities()
    {
        return Facilities::query()
            ->with('images')
            ->when(
                in_array($this->statusFilter, ['Available', 'Under Maintenance', 'Unavailable'], true),
                fn ($query) => $query->where('Status', $this->statusFilter)
            )
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery
                        ->where('Facility_Name', 'like', $term)
                        ->orWhere('Location', 'like', $term)
                        ->orWhere('Office', 'like', $term);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(
                perPage: 10,
                pageName: 'facilitiesPage'
            );
    }

    #[Computed]
    public function archivedFacilities()
    {
        return Facilities::onlyTrashed()
            ->with('images')
            ->orderByDesc('deleted_at')
            ->paginate(
                perPage: 10,
                pageName: 'archivedFacilitiesPage'
            );
    }
}; ?>

<div class="w-full">
    @include('facility.components.super-admin.page-header')
    @include('facility.components.super-admin.facilities-table')
    @include('facility.components.super-admin.archived-facilities-modal')
    @include('facility.components.super-admin.facility-form-modal')
</div>
