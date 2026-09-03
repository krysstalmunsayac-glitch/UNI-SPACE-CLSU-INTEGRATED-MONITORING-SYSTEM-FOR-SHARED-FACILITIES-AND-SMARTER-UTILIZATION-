<?php

use App\Models\Facilities;
use App\Services\FacilityAvailabilityService;
use App\Support\Ui;
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
    public bool $showStatusConfirmation = false;
    public ?int $pendingStatusId = null;
    public string $pendingStatusName = '';
    public bool $pendingStatusWillActivate = false;
    public string $deactivationConfirmation = '';
    public bool $viewMode = false;
    public bool $showCreateConfirmation = false;
    public bool $showArchivedModal = false;
    public bool $archiveOnly = false;

    public function mount(): void
    {
        $this->archiveOnly = request()->boolean('archive');
        $this->showArchivedModal = $this->archiveOnly;
    }

    public string $searchInput = '';
    public string $search = '';
    public string $statusFilter = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    #[Validate('required|string|min:2|max:150')]
    public string $Facility_Name = '';

    #[Validate(['images' => 'nullable|array|max:5', 'images.*' => 'image|max:5120'])]
    public array $images = [];

    public array $existingImages = [];
    public array $removedImageIds = [];

    #[Validate('required|in:sports,conference,auditorium,classroom,laboratory,other')]
    public ?string $facility_type = null;

    #[Validate('nullable|numeric|min:0|max:9999999.99')]
    public ?float $Price = null;

    #[Validate('required|string|min:2|max:150')]
    public ?string $Office = null;

    #[Validate('required|string|min:5|max:2000')]
    public ?string $Description = null;

    #[Validate('required|string|min:2|max:255')]
    public ?string $Location = null;

    #[Validate('nullable|numeric|between:-90,90')]
    public ?float $Latitude = null;

    #[Validate('nullable|numeric|between:-180,180')]
    public ?float $Longitude = null;

    #[Validate('required|integer|min:70|max:100000')]
    public ?int $Capacity = null;

    #[Validate('required|in:Available,Unavailable')]
    public string $Status = 'Available';

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('facilitiesPage');
        $this->resetPage('archivedFacilitiesPage');
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
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
            'removedImageIds',
            'Price',
            'Office',
            'Description',
            'Location',
            'Latitude',
            'Longitude',
            'Capacity',
        ]);

        $this->editingId = null;
        $this->viewMode = false;
        $this->showCreateConfirmation = false;
        $this->Status = 'Available';
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function updatedShowModal(bool $showModal): void
    {
        if (! $showModal) {
            $this->resetForm();
        }
    }

    public function edit(int $facilityId): void
    {
        $facility = Facilities::query()->findOrFail($facilityId);

        $this->viewMode = false;
        $this->editingId = $facility->FID;
        $this->Facility_Name = $facility->Facility_Name;
        $this->facility_type = $facility->facility_type;
        $this->Price = $facility->Price === null ? null : (float) $facility->Price;
        $this->Office = $facility->Office;
        $this->Description = $facility->Description;
        $this->Location = $facility->Location;
        $this->Latitude = $facility->Latitude;
        $this->Longitude = $facility->Longitude;
        $this->Capacity = $facility->Capacity;
        $this->Status = $facility->Status;
        $this->images = [];
        $this->existingImages = $facility->images()
            ->get(['id', 'image_path'])
            ->map(fn ($image) => ['id' => $image->id, 'path' => $image->image_path])
            ->all();
        $this->removedImageIds = [];
        $this->resetValidation();
        $this->showModal = true;
    }

    public function viewFacility(int $facilityId): void
    {
        $this->edit($facilityId);
        $this->viewMode = true;
    }

    public function save(bool $createConfirmed = false): void
    {
        $this->validate();

        if (count($this->existingImages) + count($this->images) > 5) {
            $this->addError('images', 'A facility can have a maximum of 5 images.');

            return;
        }

        if (! $this->editingId && ! $createConfirmed) {
            $this->showCreateConfirmation = true;

            return;
        }

        $this->showCreateConfirmation = false;

        $data = [
            'Facility_Name' => $this->Facility_Name,
            'facility_type' => $this->facility_type,
            'Price' => $this->Price,
            'Office' => $this->Office,
            'Description' => $this->Description,
            'Location' => $this->Location,
            'Latitude' => $this->Latitude,
            'Longitude' => $this->Longitude,
            'Capacity' => $this->Capacity,
            'Status' => $this->Status,
        ];

        $wasEditing = $this->editingId !== null;

        $facility = $wasEditing
            ? tap(Facilities::query()->findOrFail($this->editingId))->update($data)
            : Facilities::query()->create($data);

        if ($wasEditing && $this->removedImageIds !== []) {
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

        Ui::toast(
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

    public function archiveFacility(int $facilityId): void
    {
        $facility = Facilities::query()->findOrFail($facilityId);
        $facility->delete();

        Ui::toast(
            text: 'Facility moved to archived records.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Facility archived',
            'text' => 'The facility can be restored from Archived Facilities.',
            'icon' => 'success',
        ]);

        // Keep the administrator on the current paginated table after archiving.
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

        Ui::toast(
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
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->findOrFail($facilityId);

        foreach ($facility->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $facility->assignedAdmins()->detach();
        $facility->images()->delete();
        $facility->forceDelete();

        Ui::toast(
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

    public function requestToggleStatus(int $facilityId): void
    {
        $facility = Facilities::query()->findOrFail($facilityId);
        $this->pendingStatusId = $facility->FID;
        $this->pendingStatusName = $facility->Facility_Name;
        $this->pendingStatusWillActivate = $facility->Status === 'Unavailable';
        $this->deactivationConfirmation = '';
        $this->resetValidation('deactivationConfirmation');
        $this->showStatusConfirmation = true;
    }

    public function confirmToggleStatus(): void
    {
        $facility = Facilities::query()->findOrFail($this->pendingStatusId);

        if ($facility->Status !== 'Unavailable') {
            $this->validate([
                'deactivationConfirmation' => ['required', 'in:DEACTIVATE'],
            ], [
                'deactivationConfirmation.required' => 'Type DEACTIVATE to confirm.',
                'deactivationConfirmation.in' => 'Type DEACTIVATE exactly to confirm.',
            ]);
        }

        $cancelledCount = app(FacilityAvailabilityService::class)->toggle($facility);
        $facility->refresh();

        Ui::toast(
            text: $facility->Status === 'Available'
                ? 'Facility reactivated successfully!'
                : "Facility deactivated. {$cancelledCount} active request(s) cancelled.",
            variant: 'success'
        );

        $this->showStatusConfirmation = false;
        $this->pendingStatusId = null;
        $this->deactivationConfirmation = '';
    }

    #[Computed]
    public function facilities()
    {
        return Facilities::query()
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->when(
                in_array($this->statusFilter, ['Available', 'Unavailable'], true),
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
                perPage: 8,
                pageName: 'facilitiesPage'
            );
    }

    #[Computed]
    public function archivedFacilities()
    {
        return Facilities::onlyTrashed()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('Facility_Name', 'like', '%'.$this->search.'%')
                    ->orWhere('Location', 'like', '%'.$this->search.'%')
                    ->orWhere('Office', 'like', '%'.$this->search.'%');
            }))
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->orderByDesc('deleted_at')
            ->paginate(
                perPage: 8,
                pageName: 'archivedFacilitiesPage'
            );
    }
}; ?>

<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('facility.components.super-admin.archived-facilities-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
    @else
    @include('facility.components.super-admin.page-header')
    @include('facility.components.super-admin.facilities-table')
    @if ($showArchivedModal)
        <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
            @include('facility.components.super-admin.archived-facilities-modal')
        </x-ui::modal>
    @endif
    @if ($showModal)
        @include('facility.components.super-admin.facility-form-modal')
    @endif
    @if ($showCreateConfirmation)
        <x-ui::modal wire:model.self="showCreateConfirmation" class="md:w-[28rem]">
            <div class="space-y-6">
                <div>
                    <x-ui::heading size="lg">Confirm new facility</x-ui::heading>
                    <x-ui::subheading>
                        Are you sure you want to add <span class="font-semibold">{{ $Facility_Name }}</span> as a new facility?
                    </x-ui::subheading>
                </div>
                <div class="flex gap-2">
                    <x-ui::button wire:click="save(true)" variant="primary" class="flex-1">Add facility</x-ui::button>
                    <x-ui::button wire:click="$set('showCreateConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
                </div>
            </div>
        </x-ui::modal>
    @endif
    @include('facility.components.status-confirmation-modal')
    @endif
</div>
