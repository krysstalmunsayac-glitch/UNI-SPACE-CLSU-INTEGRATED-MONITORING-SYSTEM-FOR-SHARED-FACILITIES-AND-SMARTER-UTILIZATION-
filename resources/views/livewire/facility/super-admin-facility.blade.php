<?php

use App\Models\Facilities;
use App\Services\FacilityAvailabilityService;
use App\Support\Ui;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
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

    #[Validate('nullable|string|max:10000')]
    public ?string $rates = null;

    #[Validate('required|string|min:2|max:150')]
    public ?string $Office = null;

    #[Validate('required|string|min:5|max:2000')]
    public ?string $Description = null;

    #[Validate('nullable|string|max:10000')]
    public ?string $protocols_and_guidelines = null;

    #[Validate('required|string|min:2|max:255')]
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
        $facility = Facilities::query()->findOrFail($facilityId);

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
            ? Facilities::query()->findOrFail($this->editingId)
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
        ];

        $wasEditing = $this->editingId !== null;

        $facility = $wasEditing
            ? tap($existingFacility)->update($data)
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

        unset($this->facilities, $this->archivedFacilities);
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
        $this->setAvailableAtFields($facility->Available_At);
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
                'Available_Date' => ['nullable', 'date', 'after_or_equal:today'],
                'Available_Hour' => ['required_with:Available_Date', 'in:01,02,03,04,05,06,07,08,09,10,11,12'],
                'Available_Minute' => ['required_with:Available_Date', 'in:00,15,30,45'],
                'Available_Period' => ['required_with:Available_Date', 'in:AM,PM'],
            ], [
                'deactivationConfirmation.required' => 'Type DEACTIVATE to confirm.',
                'deactivationConfirmation.in' => 'Type DEACTIVATE exactly to confirm.',
                'Available_Date.after_or_equal' => 'Choose today or a future date.',
            ]);
        }

        $cancelledCount = app(FacilityAvailabilityService::class)->toggle($facility, $this->availableAtValue());
        $facility->refresh();

        Ui::toast(
            text: $facility->Status === 'Available'
                ? 'Facility reactivated successfully!'
                : "Facility deactivated. {$cancelledCount} active request(s) cancelled.",
            variant: 'success'
        );

        $this->showStatusConfirmation = false;
        $this->pendingStatusId = null;
        $this->Available_At = null;
        $this->Available_Date = null;
        $this->deactivationConfirmation = '';
    }

    #[Computed]
    public function requestableFacilities()
    {
        return Facilities::query()
            ->orderBy('Facility_Name')
            ->get(['FID', 'Facility_Name', 'Office', 'Status', 'Available_At']);
    }

    private function setAvailableAtFields($availableAt): void
    {
        $this->Available_At = $availableAt?->format('Y-m-d H:i:s');
        $this->Available_Date = $availableAt?->format('Y-m-d');
        $this->Available_Hour = $availableAt?->format('h') ?? '08';
        $this->Available_Minute = $availableAt?->format('i') ?? '00';
        $this->Available_Period = $availableAt?->format('A') ?? 'AM';
    }

    private function availableAtValue(): ?string
    {
        if ($this->Status !== 'Unavailable' || ! $this->Available_Date) {
            return null;
        }

        $hour = (int) $this->Available_Hour;
        $hour = $this->Available_Period === 'PM' && $hour !== 12 ? $hour + 12 : $hour;
        $hour = $this->Available_Period === 'AM' && $hour === 12 ? 0 : $hour;

        $availableAt = \Carbon\Carbon::parse(sprintf('%s %02d:%s:00', $this->Available_Date, $hour, $this->Available_Minute));

        if ($availableAt->isPast()) {
            throw ValidationException::withMessages([
                'Available_Date' => 'Choose a future date and time.',
            ]);
        }

        return $availableAt->format('Y-m-d H:i:s');
    }

    #[Computed]
    public function facilities()
    {
        $query = Facilities::query()
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
                        ->orWhere('Location', 'like', $term)
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
        $query = Facilities::onlyTrashed()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('Facility_Name', 'like', '%'.$this->search.'%')
                    ->orWhere('Location', 'like', '%'.$this->search.'%')
                    ->orWhere('Office', 'like', '%'.$this->search.'%');
            }))
            ->with(['images' => fn ($query) => $query->oldest('id')->limit(1)])
            ->orderByDesc('deleted_at')
            ->orderByDesc('FID');

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
}; ?>

<div class="w-full" @if (! $showModal && ! $showStatusConfirmation && ! $showCreateConfirmation) wire:poll.15s @endif>
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
