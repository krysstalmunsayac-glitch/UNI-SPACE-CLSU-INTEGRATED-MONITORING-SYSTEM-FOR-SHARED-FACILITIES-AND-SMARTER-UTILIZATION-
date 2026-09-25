<?php

namespace App\Livewire\Facilities;

use App\Actions\Facilities\DeleteFacility;
use App\Actions\Facilities\SaveFacility;
use App\Actions\Lifecycle\ArchiveRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Livewire\Facilities\Concerns\ManagesSuperAdminFacilityAvailability;
use App\Livewire\Facilities\Concerns\ManagesSuperAdminFacilityImages;
use App\Models\Facility;
use App\Services\FacilityAvailabilityService;
use App\Support\Ui;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SuperAdminFacility extends Component
{
    use ManagesSuperAdminFacilityAvailability;
    use ManagesSuperAdminFacilityImages;
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

    public string $sortDirection = 'asc';

    #[Validate('required|string|min:2|max:150')]
    public string $Facility_Name = '';

    #[Validate(['images' => 'nullable|array|max:5', 'images.*' => 'image|max:5120'])]
    public array $images = [];

    public array $existingImages = [];

    public array $removedImageIds = [];

    public ?string $legacyImageUrl = null;

    public bool $removeLegacyImage = false;

    #[Validate('required|in:sports,conference,auditorium,amphitheater,little_theater,classroom,laboratory,other')]
    public ?string $facility_type = null;

    #[Validate('nullable|string|max:10000')]
    public ?string $rates = null;

    #[Validate('required|string|min:2|max:150')]
    public ?string $Office = null;

    #[Validate('required|string|min:5|max:2000')]
    public ?string $Description = null;

    #[Validate('nullable|string|max:10000')]
    public ?string $protocols_and_guidelines = null;

    #[Validate('nullable|string|max:255')]
    public ?string $Location = null;

    #[Validate('nullable|numeric|between:-90,90')]
    public ?float $Latitude = null;

    #[Validate('nullable|numeric|between:-180,180')]
    public ?float $Longitude = null;

    #[Validate('required|integer|min:1|max:100000')]
    public ?int $Capacity = null;

    #[Validate('required|in:Available,Unavailable')]
    public ?string $Status = 'Available';

    #[Validate('nullable|date|after:now')]
    public ?string $Available_At = null;

    public ?string $Available_Date = null;

    public string $Available_Hour = '08';

    public string $Available_Minute = '00';

    public string $Available_Period = 'AM';

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
        $allowedColumns = ['Facility_Name', 'facility_type', 'Status', 'created_at'];

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
            'legacyImageUrl',
            'removeLegacyImage',
            'rates',
            'Office',
            'Description',
            'protocols_and_guidelines',
            'Location',
            'Latitude',
            'Longitude',
            'Capacity',
            'Available_At',
            'Available_Date',
        ]);

        $this->editingId = null;
        $this->viewMode = false;
        $this->showCreateConfirmation = false;
        $this->Status = 'Available';
        $this->Available_At = null;
        $this->Available_Hour = '08';
        $this->Available_Minute = '00';
        $this->Available_Period = 'AM';
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
        $facility = Facility::query()->findOrFail($facilityId);

        $this->viewMode = false;
        $this->editingId = $facility->FID;
        $this->Facility_Name = $facility->Facility_Name;
        $this->facility_type = $facility->facility_type;
        $this->rates = $facility->rates;
        $this->Office = $facility->Office;
        $this->Description = $facility->Description;
        $this->protocols_and_guidelines = $facility->protocols_and_guidelines;
        $this->Location = $facility->Location;
        $this->Latitude = $facility->Latitude;
        $this->Longitude = $facility->Longitude;
        $this->Capacity = $facility->Capacity;
        $this->Status = $facility->Status;
        $this->setAvailableAtFields($facility->Available_At);
        $this->images = [];
        $this->existingImages = $facility->images()
            ->get(['id', 'image_path'])
            ->map(fn ($image) => ['id' => $image->id, 'path' => $image->image_path])
            ->all();
        $this->removedImageIds = [];
        $this->legacyImageUrl = $facility->Image_URL
            ? (str_starts_with($facility->Image_URL, 'http://') || str_starts_with($facility->Image_URL, 'https://')
                ? $facility->Image_URL
                : asset(ltrim($facility->Image_URL, '/')))
            : null;
        $this->removeLegacyImage = false;
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

        $availableAt = $this->availableAtValue();
        $existingFacility = $this->editingId
            ? Facility::query()->findOrFail($this->editingId)
            : null;

        $data = [
            'Facility_Name' => $this->Facility_Name,
            'facility_type' => $this->facility_type,
            'rates' => $this->rates,
            'Rate_Details' => $this->rates,
            'Office' => $this->Office,
            'Description' => $this->Description,
            'protocols_and_guidelines' => $this->protocols_and_guidelines,
            'Protocols' => $this->protocols_and_guidelines,
            'Location' => $this->Location,
            'Latitude' => $this->Latitude,
            'Longitude' => $this->Longitude,
            'Capacity' => $this->Capacity,
            'Status' => $this->Status,
            'Available_At' => $this->Status === 'Unavailable' ? $availableAt : null,
            'Deactivated_At' => $this->Status === 'Unavailable'
                ? ($existingFacility?->Deactivated_At ?? now())
                : null,
            'Image_URL' => $this->removeLegacyImage ? null : $existingFacility?->Image_URL,
        ];

        $wasEditing = $this->editingId !== null;

        $facility = app(SaveFacility::class)->handle(
            $existingFacility,
            $data,
            $this->images,
            $wasEditing ? $this->removedImageIds : [],
        );

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

    public function archiveFacility(int $facilityId): void
    {
        $facility = Facility::query()->findOrFail($facilityId);
        app(ArchiveRecord::class)->handle($facility);

        Ui::toast(
            text: 'Facility moved to archived records.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Facility archived',
            'text' => 'The facility can be restored from Archived Facilities.',
            'icon' => 'success',
        ]);

        unset($this->facilities, $this->archivedFacilities);
    }

    public function openArchivedFacilities(): void
    {
        $this->resetPage('archivedFacilitiesPage');
        $this->showArchivedModal = true;
    }

    public function restoreFacility(int $facilityId): void
    {
        $facility = Facility::onlyTrashed()->findOrFail($facilityId);
        app(RestoreRecord::class)->handle($facility);

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
        $facility = Facility::onlyTrashed()
            ->with('images')
            ->findOrFail($facilityId);

        app(DeleteFacility::class)->permanently($facility);

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

    #[Computed]
    public function requestableFacilities()
    {
        return Facility::query()
            ->orderBy('Facility_Name')
            ->get(['FID', 'Facility_Name', 'Office', 'Status', 'Available_At']);
    }

    #[Computed]
    public function facilities()
    {
        app(FacilityAvailabilityService::class)->reactivateExpired();

        $query = Facility::query()
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->when(
                in_array($this->statusFilter, ['Available', 'Unavailable'], true),
                fn ($query) => $query->where('Status', $this->statusFilter)
            )
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery
                        ->where('Facility_Name', 'like', $term)
                        ->orWhere('Office', 'like', $term)
                        ->orWhere('facility_type', 'like', $term)
                        ->orWhere('rates', 'like', $term)
                        ->orWhere('protocols_and_guidelines', 'like', $term);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->orderBy('FID', $this->sortDirection);

        $facilities = $query->paginate(
            perPage: 8,
            pageName: 'facilitiesPage'
        );

        if ($facilities->currentPage() > $facilities->lastPage()) {
            $this->setPage($facilities->lastPage(), 'facilitiesPage');
            $facilities = $query->paginate(perPage: 8, pageName: 'facilitiesPage');
        }

        return $facilities;
    }

    #[Computed]
    public function archivedFacilities()
    {
        $query = Facility::onlyTrashed()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('Facility_Name', 'like', '%'.$this->search.'%')
                    ->orWhere('Office', 'like', '%'.$this->search.'%');
            }))
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->orderBy('deleted_at')
            ->orderBy('FID');

        $facilities = $query->paginate(
            perPage: 8,
            pageName: 'archivedFacilitiesPage'
        );

        if ($facilities->currentPage() > $facilities->lastPage()) {
            $this->setPage($facilities->lastPage(), 'archivedFacilitiesPage');
            $facilities = $query->paginate(perPage: 8, pageName: 'archivedFacilitiesPage');
        }

        return $facilities;
    }

    public function render(): View
    {
        return view('livewire.facilities.super-admin');
    }
}
