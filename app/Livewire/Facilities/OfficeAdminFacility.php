<?php

namespace App\Livewire\Facilities;

use App\Actions\Facilities\SaveFacility;
use App\Livewire\Facilities\Concerns\ManagesOfficeFacilityAvailability;
use App\Livewire\Facilities\Concerns\ManagesOfficeFacilityImages;
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
class OfficeAdminFacility extends Component
{
    use ManagesOfficeFacilityAvailability;
    use ManagesOfficeFacilityImages;
    use WithFileUploads;
    use WithPagination;

    public string $searchInput = '';

    public string $search = '';

    public string $statusFilter = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $editingId = null;

    public bool $showModal = false;

    public bool $showStatusConfirmation = false;

    public ?int $pendingStatusId = null;

    public string $pendingStatusName = '';

    public bool $pendingStatusWillActivate = false;

    public string $deactivationConfirmation = '';

    public bool $viewMode = false;

    #[Validate('required|string|min:2|max:150')]
    public string $Facility_Name = '';

    #[Validate(['images' => 'nullable|array|max:5', 'images.*' => 'image|max:5120'])]
    public array $images = [];

    public array $existingImages = [];

    public array $removedImageIds = [];

    public ?string $legacyImageUrl = null;

    public bool $removeLegacyImage = false;

    #[Validate('nullable|string|max:10000')]
    public ?string $rates = null;

    #[Validate('required|in:sports,conference,auditorium,amphitheater,little_theater,classroom,laboratory,other')]
    public ?string $facility_type = null;

    #[Validate('required|string|min:2|max:150')]
    public ?string $Office = null;

    #[Validate('required|string|min:5|max:2000')]
    public ?string $Description = null;

    #[Validate('nullable|string|max:10000')]
    public ?string $protocols_and_guidelines = null;

    #[Validate('nullable|string|max:255')]
    public ?string $Location = null;

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
        $this->resetPage('assignedFacilitiesPage');
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
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
            'legacyImageUrl',
            'removeLegacyImage',
            'rates',
            'Office',
            'Description',
            'protocols_and_guidelines',
            'Location',
            'Capacity',
            'Available_At',
            'Available_Date',
        ]);

        $this->editingId = null;
        $this->viewMode = false;
        $this->Status = 'Available';
        $this->Available_At = null;
        $this->Available_Hour = '08';
        $this->Available_Minute = '00';
        $this->Available_Period = 'AM';
        $this->resetValidation();
    }

    public function edit(int $facilityId): void
    {
        $facility = $this->getScopedFacility($facilityId);

        $this->viewMode = false;
        $this->editingId = $facility->FID;
        $this->Facility_Name = $facility->Facility_Name;
        $this->facility_type = $facility->facility_type;
        $this->rates = $facility->rates;
        $this->Office = $facility->Office;
        $this->Description = $facility->Description;
        $this->protocols_and_guidelines = $facility->protocols_and_guidelines;
        $this->Location = $facility->Location;
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

    public function save(): void
    {
        $this->validate();

        if (count($this->existingImages) + count($this->images) > 5) {
            $this->addError('images', 'A facility can have a maximum of 5 images.');

            return;
        }

        $facility = $this->getScopedFacility($this->editingId);

        $availableAt = $this->availableAtValue();

        app(SaveFacility::class)->handle($facility, [
            'Facility_Name' => $this->Facility_Name,
            'facility_type' => $this->facility_type,
            'rates' => $this->rates,
            'Rate_Details' => $this->rates,
            'Office' => $this->Office,
            'Description' => $this->Description,
            'protocols_and_guidelines' => $this->protocols_and_guidelines,
            'Protocols' => $this->protocols_and_guidelines,
            'Location' => $this->Location,
            'Capacity' => $this->Capacity,
            'Status' => $this->Status,
            'Available_At' => $this->Status === 'Unavailable' ? $availableAt : null,
            'Deactivated_At' => $this->Status === 'Unavailable'
                ? ($facility->Deactivated_At ?? now())
                : null,
            'Image_URL' => $this->removeLegacyImage ? null : $facility->Image_URL,
        ], $this->images, $this->removedImageIds);

        Ui::toast(
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

    #[Computed]
    public function requestableFacilities()
    {
        return Facility::query()
            ->whereHas('assignedAdmins', function ($adminQuery) {
                $adminQuery->where('users.id', auth()->id());
            })
            ->orderBy('Facility_Name')
            ->get(['FID', 'Facility_Name', 'Office', 'Status', 'Available_At']);
    }

    #[Computed]
    public function facilities()
    {
        app(FacilityAvailabilityService::class)->reactivateExpired();

        $query = Facility::query()
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->whereHas('assignedAdmins', function ($adminQuery) {
                $adminQuery->where('users.id', auth()->id());
            })
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
            pageName: 'assignedFacilitiesPage'
        );

        if ($facilities->currentPage() > $facilities->lastPage()) {
            $this->setPage($facilities->lastPage(), 'assignedFacilitiesPage');
            $facilities = $query->paginate(perPage: 8, pageName: 'assignedFacilitiesPage');
        }

        return $facilities;
    }

    private function getScopedFacility(int $facilityId): Facility
    {
        return Facility::query()
            ->whereHas('assignedAdmins', function ($adminQuery) {
                $adminQuery->where('users.id', auth()->id());
            })
            ->findOrFail($facilityId);
    }

    public function render(): View
    {
        return view('livewire.facilities.office-admin');
    }
}
