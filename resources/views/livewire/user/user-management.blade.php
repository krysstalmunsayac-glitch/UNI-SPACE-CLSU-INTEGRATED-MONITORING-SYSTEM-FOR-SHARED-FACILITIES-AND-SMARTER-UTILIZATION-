<?php

use App\Models\Facilities;
use App\Models\User;
use App\Support\Ui;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithFileUploads, WithPagination;

    public $editingId = null;

    public bool $showModal = false;

    public bool $showRoleChangeConfirmation = false;

    public bool $showAccountStatusConfirmation = false;

    public bool $showQuickStatusConfirmation = false;

    public ?int $pendingStatusUserId = null;

    public string $pendingStatusUserName = '';

    public bool $pendingStatusWillActivate = false;

    public string $deactivationConfirmation = '';

    public bool $showCreateConfirmation = false;

    public bool $showAssignmentModal = false;

    public bool $showArchivedModal = false;

    public bool $archiveOnly = false;

    public string $searchInput = '';

    public string $search = '';

    public string $roleFilter = '';

    public string $accountStatusFilter = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    #[Validate('required|string|min:2|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $password = '';

    public $profile_photo = null;

    public ?string $existingProfilePhotoUrl = null;

    #[Validate('nullable|string|max:20')]
    public ?string $contact_number = null;

    #[Validate('nullable|string|max:255')]
    public ?string $office = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    #[Validate('required|in:super_admin,admin,user')]
    public string $user_type = 'user';

    public ?string $originalUserType = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    public ?bool $originalIsActive = null;

    public ?int $selectedAdminId = null;

    public array $assignedFacilityIds = [];

    public function mount(): void
    {
        $this->archiveOnly = request()->boolean('archive');
        $this->showArchivedModal = $this->archiveOnly;
    }

    /*
    |--------------------------------------------------------------------------
    | Search and sorting
    |--------------------------------------------------------------------------
    */

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('usersPage');
        $this->resetPage('archivedUsersPage');
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage('usersPage');
    }

    public function updatedAccountStatusFilter(): void
    {
        $this->resetPage('usersPage');
    }

    public function sort(string $column): void
    {
        $allowedColumns = ['name', 'email', 'created_at'];

        if (! in_array($column, $allowedColumns, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc'
                ? 'desc'
                : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('usersPage');
    }

    /*
    |--------------------------------------------------------------------------
    | User form
    |--------------------------------------------------------------------------
    */

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'email',
            'password',
            'profile_photo',
            'contact_number',
            'office',
            'address',
        ]);

        $this->user_type = 'user';
        $this->existingProfilePhotoUrl = null;
        $this->originalUserType = null;
        $this->is_active = true;
        $this->originalIsActive = null;
        $this->editingId = null;
        $this->showCreateConfirmation = false;

        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->profile_photo = null;
        $this->existingProfilePhotoUrl = $user->avatar_url;
        $this->contact_number = $user->contact_number;
        $this->office = $user->office;
        $this->address = $user->address;
        $this->user_type = $user->user_type;
        $this->originalUserType = $user->user_type;
        $this->is_active = (bool) $user->is_active;
        $this->originalIsActive = (bool) $user->is_active;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(
        bool $roleChangeConfirmed = false,
        bool $accountStatusConfirmed = false,
        bool $createConfirmed = false
    ): void {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'contact_number' => ['nullable', 'string', 'regex:'.User::PH_CONTACT_REGEX],
            'office' => ['nullable', 'string', 'min:2', 'max:150'],
            'address' => ['nullable', 'string', 'min:5', 'max:500'],
            'user_type' => ['required', Rule::in(['super_admin', 'admin', 'user'])],
            'is_active' => ['boolean'],
            'password' => $this->editingId
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];

        $validated = $this->validate($rules, [
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        if ($this->editingId === auth()->id() && ! $validated['is_active']) {
            $this->is_active = true;
            $this->addError('is_active', 'You cannot deactivate your own account.');
            Ui::toast(text: 'You cannot deactivate your own account.', variant: 'danger');

            return;
        }

        if (
            (
                ($this->editingId && $validated['user_type'] !== $this->originalUserType)
                || (! $this->editingId && $validated['user_type'] === 'admin')
            )
            && ! $roleChangeConfirmed
        ) {
            $this->showRoleChangeConfirmation = true;

            return;
        }

        $this->showRoleChangeConfirmation = false;

        if (! $this->editingId && ! $createConfirmed) {
            $this->showCreateConfirmation = true;

            return;
        }

        $this->showCreateConfirmation = false;

        if (
            $this->editingId
            && $validated['is_active'] !== $this->originalIsActive
            && ! $accountStatusConfirmed
        ) {
            $this->deactivationConfirmation = '';
            $this->showAccountStatusConfirmation = true;

            return;
        }

        if ($accountStatusConfirmed && ! $validated['is_active']) {
            $this->validate([
                'deactivationConfirmation' => ['required', Rule::in(['DEACTIVATE'])],
            ], [
                'deactivationConfirmation.required' => 'Type DEACTIVATE to confirm.',
                'deactivationConfirmation.in' => 'Type DEACTIVATE exactly to confirm.',
            ]);
        }

        $this->showAccountStatusConfirmation = false;
        $this->showCreateConfirmation = false;

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'office' => $validated['office'] ?? null,
            'address' => $validated['address'] ?? null,
            'user_type' => $validated['user_type'],
            'is_active' => $validated['is_active'],
        ];
        $photo = $validated['profile_photo'] ?? null;

        if (filled($validated['password'] ?? null)) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);

            if ($photo) {
                $newPhotoPath = $photo->store('profile-photos', 'public');

                if ($user->ImageID && ! filter_var($user->ImageID, FILTER_VALIDATE_URL)) {
                    Storage::disk('public')->delete($user->ImageID);
                }

                $data['ImageID'] = $newPhotoPath;
            }

            $user->update($data);

            Ui::toast(
                text: 'User updated successfully!',
                variant: 'success'
            );

            $this->dispatch('swal', [
                'title' => 'User updated',
                'text' => 'User updated successfully!',
                'icon' => 'success',
            ]);
        } else {
            $data['email_verified_at'] = now();

            if ($photo) {
                $data['ImageID'] = $photo->store('profile-photos', 'public');
            }

            User::query()->create($data);

            Ui::toast(
                text: 'User created successfully!',
                variant: 'success'
            );

            $this->dispatch('swal', [
                'title' => 'User created',
                'text' => 'User created successfully!',
                'icon' => 'success',
            ]);
        }

        $this->showModal = false;
        $this->showRoleChangeConfirmation = false;
        $this->showAccountStatusConfirmation = false;
        $this->resetForm();
        $this->resetPage('usersPage');
    }

    /*
    |--------------------------------------------------------------------------
    | User account actions
    |--------------------------------------------------------------------------
    */

    public function delete(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->id === auth()->id()) {
            Ui::toast(
                text: 'You cannot archive your own account.',
                variant: 'danger'
            );

            return;
        }

        $user->delete();

        Ui::toast(
            text: 'User moved to archived records.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'User archived',
            'text' => 'The user can be restored from Archived Users.',
            'icon' => 'success',
        ]);

        $this->resetPage('usersPage');
        $this->resetPage('archivedUsersPage');
    }

    public function requestToggleActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->id === auth()->id()) {
            Ui::toast(
                text: 'You cannot deactivate your own account.',
                variant: 'danger'
            );

            return;
        }

        $this->pendingStatusUserId = $user->id;
        $this->pendingStatusUserName = $user->name;
        $this->pendingStatusWillActivate = ! $user->is_active;
        $this->deactivationConfirmation = '';
        $this->resetValidation('deactivationConfirmation');
        $this->showQuickStatusConfirmation = true;
    }

    public function confirmToggleActive(): void
    {
        $user = User::query()->findOrFail($this->pendingStatusUserId);

        if ($user->id === auth()->id()) {
            $this->showQuickStatusConfirmation = false;

            Ui::toast(text: 'You cannot deactivate your own account.', variant: 'danger');

            return;
        }

        if ($user->is_active) {
            $this->validate([
                'deactivationConfirmation' => ['required', Rule::in(['DEACTIVATE'])],
            ], [
                'deactivationConfirmation.required' => 'Type DEACTIVATE to confirm.',
                'deactivationConfirmation.in' => 'Type DEACTIVATE exactly to confirm.',
            ]);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        Ui::toast(
            text: $user->is_active
                ? 'User activated.'
                : 'User deactivated.',
            variant: 'success'
        );

        $this->showQuickStatusConfirmation = false;
        $this->pendingStatusUserId = null;
        $this->deactivationConfirmation = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Archived users
    |--------------------------------------------------------------------------
    */

    public function openArchivedUsers(): void
    {
        $this->resetPage('archivedUsersPage');
        $this->showArchivedModal = true;
    }

    public function restoreUser(int $userId): void
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();

        Ui::toast(
            text: 'User restored successfully!',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'User restored',
            'text' => 'The user account is active in User Management again.',
            'icon' => 'success',
        ]);

        $this->resetPage('usersPage');
        $this->resetPage('archivedUsersPage');
    }

    public function forceDeleteUser(int $userId): void
    {
        $user = User::onlyTrashed()->findOrFail($userId);

        /*
         * Remove facility assignments before permanent deletion.
         * This prevents pivot records from remaining in the database.
         */
        $user->facilities()->detach();
        $user->forceDelete();

        Ui::toast(
            text: 'User permanently deleted.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'User permanently deleted',
            'text' => 'This account can no longer be restored.',
            'icon' => 'success',
        ]);

        $this->resetPage('archivedUsersPage');
    }

    /*
    |--------------------------------------------------------------------------
    | Facility assignment
    |--------------------------------------------------------------------------
    */

    public function openAssignments(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->user_type !== 'admin') {
            Ui::toast(
                text: 'Facility assignment is only available for Office Admin accounts.',
                variant: 'info'
            );

            return;
        }

        $this->selectedAdminId = $user->id;

        $this->assignedFacilityIds = $user->facilities()
            ->pluck('facilities.FID')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->showAssignmentModal = true;
    }

    public function saveAssignments(): void
    {
        if (! $this->selectedAdminId) {
            return;
        }

        $admin = User::query()->findOrFail($this->selectedAdminId);

        if ($admin->user_type !== 'admin') {
            Ui::toast(
                text: 'Only Office Admin accounts can be assigned facilities.',
                variant: 'danger'
            );

            return;
        }

        $validated = $this->validate([
            'assignedFacilityIds' => ['array'],
            'assignedFacilityIds.*' => ['integer', 'distinct', Rule::exists('facilities', 'FID')->whereNull('deleted_at')],
        ]);

        $facilityIds = array_values(array_unique(array_map(
            'intval',
            $validated['assignedFacilityIds'],
        )));

        $conflictingFacility = Facilities::query()
            ->whereIn('FID', $facilityIds)
            ->whereHas(
                'assignedAdmins',
                fn ($query) => $query->where('users.id', '!=', $admin->id),
            )
            ->first();

        if ($conflictingFacility) {
            $this->addError(
                'assignedFacilityIds',
                "{$conflictingFacility->Facility_Name} is already assigned to another Office Admin.",
            );

            return;
        }

        $admin->syncFacilities($facilityIds);

        Ui::toast(
            text: 'Facilities assigned successfully.',
            variant: 'success'
        );

        $this->showAssignmentModal = false;
        $this->selectedAdminId = null;
        $this->assignedFacilityIds = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Computed properties
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function users()
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->when(
                in_array($this->roleFilter, ['super_admin', 'admin', 'user'], true),
                fn ($query) => $query->where('user_type', $this->roleFilter),
            )
            ->when(
                $this->accountStatusFilter !== '',
                fn ($query) => $query->where('is_active', $this->accountStatusFilter === 'active'),
            )
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(
                perPage: 8,
                pageName: 'usersPage'
            );
    }

    #[Computed]
    public function archivedUsers()
    {
        return User::onlyTrashed()
            ->whereNotNull('email_verified_at')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('user_type', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('deleted_at')
            ->paginate(
                perPage: 8,
                pageName: 'archivedUsersPage'
            );
    }

    #[Computed]
    public function availableFacilities()
    {
        if (! $this->showAssignmentModal) {
            return collect();
        }

        return Facilities::query()
            ->whereDoesntHave(
                'assignedAdmins',
                fn ($query) => $query->where('users.id', '!=', $this->selectedAdminId),
            )
            ->orderBy('Facility_Name')
            ->get(['FID', 'Facility_Name']);
    }

    #[Computed]
    public function userStats(): array
    {
        $stats = User::query()
            ->whereNotNull('email_verified_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN user_type = 'super_admin' THEN 1 ELSE 0 END) as super_admins")
            ->selectRaw("SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as office_admins")
            ->selectRaw("SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) as end_users")
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->first();

        return [
            'total' => (int) $stats->total,
            'super_admins' => (int) $stats->super_admins,
            'office_admins' => (int) $stats->office_admins,
            'end_users' => (int) $stats->end_users,
            'inactive' => (int) $stats->inactive,
        ];
    }
}; ?>

<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('user.components.archived-users-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
    @else
        @include('user.components.page-header')
        @include('user.components.users-table')

        @if ($showArchivedModal)
            <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
                @include('user.components.archived-users-modal')
            </x-ui::modal>
        @endif

        @if ($showAssignmentModal)
            @include('user.components.assignment-modal')
        @endif
        @if ($showModal || $showRoleChangeConfirmation || $showAccountStatusConfirmation || $showCreateConfirmation)
            @include('user.components.user-form-modal')
        @endif

        @if ($showQuickStatusConfirmation)
            <x-ui::modal wire:model.self="showQuickStatusConfirmation" class="md:w-[28rem]">
                <div class="space-y-6">
                    <div>
                        <x-ui::heading size="lg">
                            Confirm account {{ $pendingStatusWillActivate ? 'activation' : 'deactivation' }}
                        </x-ui::heading>
                        <x-ui::subheading>
                            {{ $pendingStatusWillActivate ? 'Restore system access for' : 'Remove system access from' }}
                            <span class="font-semibold">{{ $pendingStatusUserName }}</span>?
                        </x-ui::subheading>
                    </div>

                    @if (! $pendingStatusWillActivate)
                        <x-ui::input
                            wire:model="deactivationConfirmation"
                            label="Type DEACTIVATE to confirm"
                            placeholder="DEACTIVATE"
                            autocomplete="off"
                        />
                    @endif

                    <div class="flex gap-2">
                        <x-ui::button
                            wire:click="confirmToggleActive"
                            :variant="$pendingStatusWillActivate ? 'primary' : 'danger'"
                            class="flex-1"
                        >
                            {{ $pendingStatusWillActivate ? 'Activate account' : 'Deactivate account' }}
                        </x-ui::button>
                        <x-ui::button wire:click="$set('showQuickStatusConfirmation', false)" variant="ghost" class="flex-1">
                            Cancel
                        </x-ui::button>
                    </div>
                </div>
            </x-ui::modal>
        @endif
    @endif
</div>
