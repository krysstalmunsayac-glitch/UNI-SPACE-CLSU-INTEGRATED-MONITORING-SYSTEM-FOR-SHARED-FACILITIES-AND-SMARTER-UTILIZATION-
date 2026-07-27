<?php

use App\Models\Facilities;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $editingId = null;

    public bool $showModal = false;
    public bool $showRoleChangeConfirmation = false;
    public bool $showAccountStatusConfirmation = false;
    public bool $showAssignmentModal = false;
    public bool $showArchivedModal = false;

    public string $searchInput = '';
    public string $search = '';

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $password = '';

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

    /*
    |--------------------------------------------------------------------------
    | Search and sorting
    |--------------------------------------------------------------------------
    */

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
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
            'contact_number',
            'office',
            'address',
        ]);

        $this->user_type = 'user';
        $this->originalUserType = null;
        $this->is_active = true;
        $this->originalIsActive = null;
        $this->editingId = null;

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
        bool $accountStatusConfirmed = false
    ): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'office' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'user_type' => ['required', Rule::in(['super_admin', 'admin', 'user'])],
            'is_active' => ['boolean'],
            'password' => $this->editingId
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
        ];

        $validated = $this->validate($rules);

        if (
            $this->editingId
            && $validated['user_type'] !== $this->originalUserType
            && ! $roleChangeConfirmed
        ) {
            $this->showRoleChangeConfirmation = true;

            return;
        }

        $this->showRoleChangeConfirmation = false;

        if (
            $this->editingId
            && $validated['is_active'] !== $this->originalIsActive
            && ! $accountStatusConfirmed
        ) {
            $this->showAccountStatusConfirmation = true;

            return;
        }

        $this->showAccountStatusConfirmation = false;

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'office' => $validated['office'] ?? null,
            'address' => $validated['address'] ?? null,
            'user_type' => $validated['user_type'],
            'is_active' => $validated['is_active'],
        ];

        if (filled($validated['password'] ?? null)) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);
            $user->update($data);

            Flux::toast(
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

            User::query()->create($data);

            Flux::toast(
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
            Flux::toast(
                text: 'You cannot archive your own account.',
                variant: 'danger'
            );

            return;
        }

        $user->delete();

        Flux::toast(
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

    public function toggleActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->id === auth()->id()) {
            Flux::toast(
                text: 'You cannot deactivate your own account.',
                variant: 'danger'
            );

            return;
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        Flux::toast(
            text: $user->is_active
                ? 'User activated.'
                : 'User deactivated.',
            variant: 'success'
        );
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

        Flux::toast(
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

        Flux::toast(
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
            Flux::toast(
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
            Flux::toast(
                text: 'Only Office Admin accounts can be assigned facilities.',
                variant: 'danger'
            );

            return;
        }

        $admin->syncFacilities($this->assignedFacilityIds);

        Flux::toast(
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
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(
                perPage: 10,
                pageName: 'usersPage'
            );
    }

    #[Computed]
    public function archivedUsers()
    {
        return User::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(
                perPage: 10,
                pageName: 'archivedUsersPage'
            );
    }

    #[Computed]
    public function availableFacilities()
    {
        return Facilities::query()
            ->orderBy('Facility_Name')
            ->get();
    }
}; ?>

<div class="w-full">
    @include('user.components.page-header')
    @include('user.components.users-table')
    @include('user.components.archived-users-modal')
    @include('user.components.assignment-modal')
    @include('user.components.user-form-modal')
</div>
