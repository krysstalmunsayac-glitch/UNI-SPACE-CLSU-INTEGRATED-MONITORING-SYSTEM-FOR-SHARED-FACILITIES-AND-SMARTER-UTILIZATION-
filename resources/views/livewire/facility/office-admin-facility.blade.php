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

    public string $searchInput = '';
    public string $search = '';
    public string $statusFilter = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public ?int $editingId = null;
    public bool $showModal = false;

    #[Validate('required|string|min:2|max:150')]
    public string $Facility_Name = '';

    #[Validate(['images' => 'nullable|array|max:5', 'images.*' => 'image|max:5120'])]
    public array $images = [];

    public array $existingImages = [];
    public array $removedImageIds = [];

    #[Validate('required|numeric|min:0|max:9999999.99')]
    public float $Price = 0;

    #[Validate('nullable|in:sports,conference,auditorium,classroom,laboratory,other')]
    public ?string $facility_type = null;

    #[Validate('nullable|string|min:2|max:150')]
    public ?string $Office = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $Description = null;

    #[Validate('nullable|string|max:255')]
    public ?string $Location = null;

    #[Validate('required|integer|min:70|max:100000')]
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
        $this->resetPage('assignedFacilitiesPage');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage('assignedFacilitiesPage');
    }

    public function resetForm(): void
    {
        $this->reset([
            'Facility_Name',
            'facility_type',
            'images',
            'existingImages',
            'removedImageIds',
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

    public function edit(int $facilityId): void
    {
        $facility = $this->getScopedFacility($facilityId);

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
        $this->existingImages = $facility->images()
            ->get(['id', 'image_path'])
            ->map(fn ($image) => ['id' => $image->id, 'path' => $image->image_path])
            ->all();
        $this->removedImageIds = [];
        $this->selectedAmenityIds = $facility->amenities()->pluck('amenities.AID')->map(fn ($id) => (int) $id)->all();

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if (count($this->existingImages) + count($this->images) > 5) {
            $this->addError('images', 'A facility can have a maximum of 5 images.');

            return;
        }

        $facility = $this->getScopedFacility($this->editingId);

        $facility->update([
            'Facility_Name' => $this->Facility_Name,
            'facility_type' => $this->facility_type,
            'Price' => $this->Price,
            'Office' => $this->Office,
            'Description' => $this->Description,
            'Location' => $this->Location,
            'Capacity' => $this->Capacity,
            'Status' => $this->Status,
        ]);

        if ($this->removedImageIds !== []) {
            $imagesToRemove = $facility->images()
                ->whereIn('id', $this->removedImageIds)
                ->get();

            Storage::disk('public')->delete($imagesToRemove->pluck('image_path')->all());
            $facility->images()->whereIn('id', $this->removedImageIds)->delete();
        }

        foreach ($this->images as $image) {
            $path = $image->store('facilities', 'public');

            $facility->images()->create([
                'image_path' => $path,
            ]);
        }

        $facility->amenities()->sync($this->selectedAmenityIds);

        Flux::toast(
            text: 'Facility updated successfully!',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Facility updated',
            'text' => 'Facility updated successfully!',
            'icon' => 'success',
        ]);

        $this->showModal = false;
        $this->resetForm();
        $this->resetPage('assignedFacilitiesPage');
    }

    public function removeExistingImage(int $imageId): void
    {
        $image = collect($this->existingImages)->firstWhere('id', $imageId);

        if (! $image) {
            return;
        }

        $this->removedImageIds[] = $imageId;
        $this->removedImageIds = array_values(array_unique($this->removedImageIds));
        $this->existingImages = array_values(array_filter(
            $this->existingImages,
            fn (array $existingImage) => $existingImage['id'] !== $imageId,
        ));
        $this->resetErrorBag('images');
    }

    public function removeNewImage(int $index): void
    {
        if (! array_key_exists($index, $this->images)) {
            return;
        }

        unset($this->images[$index]);
        $this->images = array_values($this->images);
        $this->resetErrorBag('images');
    }

    public function toggleStatus(int $facilityId): void
    {
        $facility = $this->getScopedFacility($facilityId);
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
            ->whereHas('assignedAdmins', function ($adminQuery) {
                $adminQuery->where('users.id', auth()->id());
            })
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
                        ->orWhere('Office', 'like', $term)
                        ->orWhere('facility_type', 'like', $term);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(
                perPage: 10,
                pageName: 'assignedFacilitiesPage'
            );
    }

    private function getScopedFacility(int $facilityId): Facilities
    {
        return Facilities::query()
            ->whereHas('assignedAdmins', function ($adminQuery) {
                $adminQuery->where('users.id', auth()->id());
            })
            ->findOrFail($facilityId);
    }
}; ?>

<div class="w-full">
    @include('facility.components.office-admin.page-header')
    @include('facility.components.office-admin.facilities-grid')
    @include('facility.components.super-admin.facility-form-modal')
</div>
