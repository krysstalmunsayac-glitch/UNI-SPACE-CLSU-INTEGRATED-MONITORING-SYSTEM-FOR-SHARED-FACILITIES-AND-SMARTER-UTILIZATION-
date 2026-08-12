<?php

use App\Models\Amenities;
use App\Models\Facilities;
use App\Support\Ui;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component
{
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

    #[Validate('nullable|integer|min:1|max:100000')]
    public ?int $reservation_limit = null;

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
        if (! in_array($column, ['name', 'Status', 'reservation_limit', 'current_usage_count'], true)) {
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
        $this->reset(['name', 'Description', 'facilityIds', 'reservation_limit']);
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
            'reservation_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $this->authorizeFacilityIds($validated['facilityIds']);

        if (! $this->editingId && ! $createConfirmed) {
            $this->showCreateConfirmation = true;

            return;
        }

        $this->showCreateConfirmation = false;

        if ($this->editingId) {
            $amenity = $this->getScopedAmenity($this->editingId);
            $amenity->update([
                'name' => $this->name,
                'Description' => $this->Description,
                'Status' => $this->Status,
                'reservation_limit' => $validated['reservation_limit'] ?? null,
            ]);
            $amenity->facilities()->sync($validated['facilityIds']);
        } else {
            $amenity = Amenities::create([
                'created_by' => auth()->id(),
                'name' => $this->name,
                'Description' => $this->Description,
                'Status' => $this->Status,
                'reservation_limit' => $validated['reservation_limit'] ?? null,
            ]);
            $amenity->facilities()->sync($validated['facilityIds']);
        }

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
        $this->reservation_limit = $amenity->reservation_limit;
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

        $amenity->update([
            'Status' => $amenity->Status === 'Available'
                ? 'Unavailable'
                : 'Available',
        ]);

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

    public function delete(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId);
        $amenity->delete();

        Ui::toast(text: 'Amenity archived successfully!', variant: 'success');
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
        $this->resetPage('archivedAmenitiesPage');
        $this->showArchivedModal = true;
    }

    public function restore(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId, withTrashed: true);
        $amenity->restore();

        Ui::toast(text: 'Amenity restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId, withTrashed: true);
        $amenity->forceDelete();

        Ui::toast(text: 'Amenity permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function amenities()
    {
        $query = Amenities::query()
            ->with([
                'facilities:FID,Facility_Name',
                'creator:id,name',
            ])
            ->withCount([
                'requests as current_usage_count' => fn ($requestQuery) => $requestQuery
                    ->whereIn('Status', ['Pending', 'Approved']),
            ])
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('Description', 'like', "%{$this->search}%");
            }));

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facilities.assignedAdmins', fn ($query) => $query->where('users.id', auth()->id()));
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(8, pageName: 'amenitiesPage');
    }

    #[Computed]
    public function archivedAmenities()
    {
        $query = Amenities::query()->onlyTrashed()->with('facilities:FID,Facility_Name');

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facilities.assignedAdmins', fn ($query) => $query->where('users.id', auth()->id()));
        }

        if ($this->search !== '') {
            $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('Description', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderByDesc('deleted_at')
            ->paginate(8, pageName: 'archivedAmenitiesPage');
    }

    #[Computed]
    public function facilityOptions()
    {
        if (! $this->showModal) {
            return collect();
        }

        $query = Facilities::query();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('assignedAdmins', fn ($adminQuery) => $adminQuery->where('users.id', auth()->id())
            );
        }

        return $query->orderBy('Facility_Name')->get(['FID', 'Facility_Name', 'Office']);
    }

    #[Computed]
    public function viewingAmenity(): ?Amenities
    {
        if (! $this->showViewModal || ! $this->viewingId) {
            return null;
        }

        return $this->getVisibleAmenity($this->viewingId)
            ->load(['facilities:FID,Facility_Name,Office', 'creator:id,name,email,user_type']);
    }

    public function canManageAmenity(Amenities $amenity): bool
    {
        if (! auth()->user()->isAdmin()) {
            return true;
        }

        $assignedIds = $this->assignedFacilityIds();
        $amenityFacilityIds = $amenity->facilities->pluck('FID')->map(fn ($id) => (int) $id);

        return $amenityFacilityIds->isNotEmpty()
            && $amenityFacilityIds->every(fn (int $id) => in_array($id, $assignedIds, true));
    }

    private function getScopedAmenity(int $amenityId, bool $withTrashed = false): Amenities
    {
        $query = $withTrashed ? Amenities::withTrashed() : Amenities::query();

        if (auth()->user()->isAdmin()) {
            $assignedIds = $this->assignedFacilityIds();
            $query
                ->whereHas('facilities', fn ($facilityQuery) => $facilityQuery->whereIn('facilities.FID', $assignedIds))
                ->whereDoesntHave('facilities', fn ($facilityQuery) => $facilityQuery->whereNotIn('facilities.FID', $assignedIds));
        }

        return $query->findOrFail($amenityId);
    }

    private function getVisibleAmenity(int $amenityId): Amenities
    {
        $query = Amenities::query();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facilities.assignedAdmins', fn ($adminQuery) => $adminQuery->where('users.id', auth()->id()));
        }

        return $query->findOrFail($amenityId);
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
}; ?>

<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-5xl">
            <x-ui::card>
                @include('amenities.components.archived-amenities-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
    @else
    @include('amenities.components.page-header')
    @include('amenities.components.amenities-table')
    @if ($showArchivedModal)
        <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-5xl">
            @include('amenities.components.archived-amenities-modal')
        </x-ui::modal>
    @endif
    @if ($showModal)
        @include('amenities.components.amenity-form-modal')
    @endif
    @if ($showViewModal)
        @include('amenities.components.amenity-view-modal')
    @endif
    @if ($showCreateConfirmation)
        <x-ui::modal wire:model.self="showCreateConfirmation" class="md:w-[28rem]">
            <div class="space-y-6">
                <div>
                    <x-ui::heading size="lg">Confirm new amenity</x-ui::heading>
                    <x-ui::subheading>
                        Are you sure you want to add <span class="font-semibold">{{ $name }}</span> as a new amenity?
                    </x-ui::subheading>
                </div>
                <div class="flex gap-2">
                    <x-ui::button wire:click="save(true)" variant="primary" class="flex-1">Add amenity</x-ui::button>
                    <x-ui::button wire:click="$set('showCreateConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
                </div>
            </div>
        </x-ui::modal>
    @endif
    @if ($showStatusConfirmation)
        <x-ui::modal wire:model.self="showStatusConfirmation" class="md:w-[28rem]">
            <div class="space-y-6">
                <div>
                    <x-ui::heading size="lg">
                        Confirm amenity {{ $pendingStatusWillActivate ? 'activation' : 'deactivation' }}
                    </x-ui::heading>
                    <x-ui::subheading>
                        Make
                        <span class="font-semibold">{{ $pendingStatusName }}</span>
                        {{ $pendingStatusWillActivate ? 'available again?' : 'unavailable for new requests?' }}
                    </x-ui::subheading>
                </div>

                @if (! $pendingStatusWillActivate)
                    <x-ui::input wire:model="deactivationConfirmation" label="Type DEACTIVATE to confirm" placeholder="DEACTIVATE" autocomplete="off" />
                @endif

                <div class="flex gap-2">
                    <x-ui::button wire:click="confirmToggleStatus" :variant="$pendingStatusWillActivate ? 'primary' : 'danger'" class="flex-1">
                        {{ $pendingStatusWillActivate ? 'Activate amenity' : 'Deactivate amenity' }}
                    </x-ui::button>
                    <x-ui::button wire:click="$set('showStatusConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
                </div>
            </div>
        </x-ui::modal>
    @endif
    @endif
</div>
