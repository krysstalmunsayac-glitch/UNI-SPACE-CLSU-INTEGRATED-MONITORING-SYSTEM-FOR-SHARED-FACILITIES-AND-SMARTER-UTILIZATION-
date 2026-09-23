<?php

namespace App\Livewire\Amenities;

use App\Actions\Amenities\SaveAmenity;
use App\Actions\Amenities\ToggleAmenityStatus;
use App\Livewire\Amenities\Concerns\ManagesAmenityLifecycle;
use App\Livewire\Queries\AmenityListQuery;
use App\Models\Amenity;
use App\Support\Ui;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class AmenityManagement extends Component
{
    use ManagesAmenityLifecycle;
    use WithPagination;

    public $editingId = null;

    public bool $showModal = false;

    public bool $showArchivedModal = false;

    public bool $showViewModal = false;

    public bool $showCreateConfirmation = false;

    public bool $showStatusConfirmation = false;

    public ?int $pendingStatusId = null;

    public string $pendingStatusName = '';

    public bool $pendingStatusWillActivate = false;

    public string $deactivationConfirmation = '';

    public ?int $viewingId = null;

    public bool $archiveOnly = false;

    public function mount(): void
    {
        $this->archiveOnly = request()->boolean('archive');
        $this->showArchivedModal = $this->archiveOnly;
    }

    public string $searchInput = '';

    public string $search = '';

    public $sortBy = 'name';

    public $sortDirection = 'asc';

    #[Validate('required|string|min:2|max:100')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $Description = null;

    #[Validate('required|array|min:1')]
    public array $facilityIds = [];

    #[Validate('required|in:Available,Unavailable')]
    public string $Status = 'Available';

    #[Validate('required|integer|min:1|max:100000')]
    public int $inventory_quantity = 1;

    #[Validate('required|in:permanent,countable')]
    public string $inventory_type = 'countable';

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('amenitiesPage');
        $this->resetPage('archivedAmenitiesPage');
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function sort($column): void
    {
        if (! in_array($column, ['name', 'Status', 'inventory_quantity'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('amenitiesPage');
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'Description', 'facilityIds']);
        $this->inventory_quantity = 1;
        $this->inventory_type = 'countable';
        $this->Status = 'Available';
        $this->editingId = null;
        $this->showCreateConfirmation = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function save(bool $createConfirmed = false): void
    {
        abort_unless(auth()->user()->isSuperAdminOrAdmin(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'Description' => ['nullable', 'string', 'max:1000'],
            'facilityIds' => ['required', 'array', 'min:1'],
            'facilityIds.*' => ['integer', 'distinct', Rule::exists('facilities', 'FID')->whereNull('deleted_at')],
            'Status' => ['required', Rule::in(['Available', 'Unavailable'])],
            'inventory_type' => ['required', Rule::in(['permanent', 'countable'])],
            'inventory_quantity' => [Rule::requiredIf($this->inventory_type === 'countable'), 'integer', 'min:1', 'max:100000'],
        ]);

        $this->authorizeFacilityIds($validated['facilityIds']);

        if (! $this->editingId && ! $createConfirmed) {
            $this->showCreateConfirmation = true;

            return;
        }

        $this->showCreateConfirmation = false;

        $amenity = $this->editingId ? $this->getScopedAmenity($this->editingId) : null;
        app(SaveAmenity::class)->handle($amenity, [
            'name' => $this->name,
            'Description' => $this->Description,
            'Status' => $this->Status,
            'inventory_type' => $validated['inventory_type'],
            'inventory_quantity' => $validated['inventory_type'] === 'permanent' ? 1 : $validated['inventory_quantity'],
        ], $validated['facilityIds'], (int) auth()->id());

        Ui::toast(
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
        $this->inventory_quantity = $amenity->inventory_quantity;
        $this->inventory_type = $amenity->inventory_type;
        $this->facilityIds = $amenity->facilities->pluck('FID')->toArray();
        $this->showModal = true;
    }

    public function showDetails(int $amenityId): void
    {
        $amenity = $this->getVisibleAmenity($amenityId);
        $this->viewingId = $amenity->AID;
        $this->showViewModal = true;
    }

    public function closeView(): void
    {
        $this->showViewModal = false;
        $this->viewingId = null;
    }

    public function requestToggleStatus(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId);
        $this->pendingStatusId = $amenity->AID;
        $this->pendingStatusName = $amenity->name;
        $this->pendingStatusWillActivate = $amenity->Status !== 'Available';
        $this->deactivationConfirmation = '';
        $this->resetValidation('deactivationConfirmation');
        $this->showStatusConfirmation = true;
    }

    public function confirmToggleStatus(): void
    {
        $amenity = $this->getScopedAmenity($this->pendingStatusId);

        if ($amenity->Status === 'Available') {
            $this->validate([
                'deactivationConfirmation' => ['required', Rule::in(['DEACTIVATE'])],
            ], [
                'deactivationConfirmation.required' => 'Type DEACTIVATE to confirm.',
                'deactivationConfirmation.in' => 'Type DEACTIVATE exactly to confirm.',
            ]);
        }

        $amenity = app(ToggleAmenityStatus::class)->handle($amenity);

        Ui::toast(
            text: $amenity->Status === 'Available'
                ? 'Amenity is now available.'
                : 'Amenity is now unavailable and cannot be selected in new requests.',
            variant: 'success'
        );

        $this->showStatusConfirmation = false;
        $this->pendingStatusId = null;
        $this->deactivationConfirmation = '';
    }

    #[Computed]
    public function amenities()
    {
        return app(AmenityListQuery::class)->active(auth()->user(), $this->search, $this->sortBy, $this->sortDirection);
    }

    #[Computed]
    public function archivedAmenities()
    {
        return app(AmenityListQuery::class)->archived(auth()->user(), $this->search);
    }

    #[Computed]
    public function facilityOptions()
    {
        if (! $this->showModal) {
            return collect();
        }

        return app(AmenityListQuery::class)->facilities(auth()->user());
    }

    #[Computed]
    public function viewingAmenity(): ?Amenity
    {
        if (! $this->showViewModal || ! $this->viewingId) {
            return null;
        }

        return $this->getVisibleAmenity($this->viewingId)
            ->load(['facilities:FID,Facility_Name,Office', 'creator:id,name,email,user_type']);
    }

    public function canManageAmenity(Amenity $amenity): bool
    {
        if (! auth()->user()->isAdmin()) {
            return true;
        }

        $assignedIds = $this->assignedFacilityIds();
        $amenityFacilityIds = $amenity->facilities->pluck('FID')->map(fn ($id) => (int) $id);

        return $amenityFacilityIds->isNotEmpty()
            && $amenityFacilityIds->every(fn (int $id) => in_array($id, $assignedIds, true));
    }

    private function getScopedAmenity(int $amenityId, bool $withTrashed = false): Amenity
    {
        return app(AmenityListQuery::class)->scoped(auth()->user(), $amenityId, $withTrashed);
    }

    private function getVisibleAmenity(int $amenityId): Amenity
    {
        return app(AmenityListQuery::class)->visible(auth()->user(), $amenityId);
    }

    /** @param array<int, int|string> $facilityIds */
    private function authorizeFacilityIds(array $facilityIds): void
    {
        if (! auth()->user()->isAdmin()) {
            return;
        }

        $assignedIds = $this->assignedFacilityIds();
        abort_unless(
            collect($facilityIds)->map(fn ($id) => (int) $id)
                ->every(fn (int $id) => in_array($id, $assignedIds, true)),
            403,
            'You can only manage amenities for facilities assigned to you.',
        );
    }

    /** @return array<int, int> */
    private function assignedFacilityIds(): array
    {
        return auth()->user()->assignedFacilityIds();
    }

    public function render(): View
    {
        return view('livewire.amenities.index');
    }
}
