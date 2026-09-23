<?php

namespace App\Livewire\Users;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Livewire\Forms\UserForm;
use App\Livewire\Queries\UserListQuery;
use App\Livewire\Users\Concerns\ManagesUserAssignments;
use App\Livewire\Users\Concerns\ManagesUserInvitations;
use App\Livewire\Users\Concerns\ManagesUserStatus;
use App\Models\User;
use App\Support\Ui;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UserManagement extends Component
{
    use ManagesUserAssignments;
    use ManagesUserInvitations;
    use ManagesUserStatus;
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

    public UserForm $form;

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $existingProfilePhotoUrl = null;

    public ?string $originalUserType = null;

    public ?bool $originalIsActive = null;

    public ?int $selectedAdminId = null;

    public array $assignedFacilityIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
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
        $this->form->reset();
        $this->reset(['password', 'password_confirmation']);
        $this->existingProfilePhotoUrl = null;
        $this->originalUserType = null;
        $this->form->is_active = true;
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
        $user = $this->managedUser($userId);

        $this->editingId = $user->id;
        $this->form->editingId = $user->id;
        $this->form->name = $user->name;
        $this->form->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->form->profile_photo = null;
        $this->existingProfilePhotoUrl = $user->avatar_url;
        $this->form->contact_number = $this->normalizeContactNumber($user->contact_number);
        $this->form->office = $user->office;
        $this->form->address = $user->address;
        $this->form->user_type = $user->user_type;
        $this->originalUserType = $user->user_type;
        $this->form->is_active = (bool) $user->is_active;
        $this->originalIsActive = (bool) $user->is_active;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(
        bool $roleChangeConfirmed = false,
        bool $accountStatusConfirmed = false,
        bool $createConfirmed = false
    ): void {
        $this->form->name = trim($this->form->name);
        $this->form->email = Str::lower(trim($this->form->email));
        $this->form->contact_number = $this->normalizeContactNumber($this->form->contact_number);

        if (User::onlyTrashed()->where('email', $this->form->email)->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId))->exists()) {
            $this->addError('form.email', 'This email belongs to an archived account. Restore that account instead.');

            return;
        }

        $this->form->editingId = $this->editingId;
        try {
            $validated = $this->form->validate();
        } catch (ValidationException $exception) {
            Ui::toast(
                text: collect($exception->errors())->flatten()->first() ?? 'Please correct the highlighted fields.',
                variant: 'danger'
            );

            throw $exception;
        }

        if ($this->editingId === auth()->id() && ! $validated['is_active']) {
            $this->form->is_active = true;
            $this->addError('form.is_active', 'You cannot deactivate your own account.');
            Ui::toast(text: 'You cannot deactivate your own account.', variant: 'danger');

            return;
        }

        if (in_array($validated['user_type'], ['admin', 'super_admin'], true) && ! str_ends_with($validated['email'], '@clsu.edu.ph')) {
            $this->addError('form.email', 'Administrative accounts must use an @clsu.edu.ph email address.');

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
        if ($this->editingId) {
            $user = $this->managedUser($this->editingId);
            ['user' => $user, 'emailChanged' => $emailChanged, 'roleChanged' => $roleChanged, 'statusChanged' => $statusChanged]
                = app(UpdateUser::class)->handle($user, $data, $photo);

            Ui::toast(
                text: 'User updated successfully!',
                variant: 'success'
            );

            $successTitle = $roleChanged
                ? 'Role changed successfully'
                : ($statusChanged
                    ? ($validated['is_active'] ? 'Account activated' : 'Account deactivated')
                    : ($emailChanged ? 'Verification required' : 'User updated'));
            $successText = $roleChanged
                ? "{$user->name} is now {$user->roleLabel()}."
                : ($statusChanged
                    ? ($validated['is_active']
                        ? "{$user->name} can now access the system."
                        : "{$user->name} can no longer access the system.")
                    : ($emailChanged
                        ? "A new invitation was sent to {$user->email}. The account is inactive until verification is completed."
                        : 'User details were updated successfully.'));

            $this->dispatch('swal', [
                'title' => $successTitle,
                'text' => $successText,
                'icon' => 'success',
            ]);
        } else {
            $user = app(CreateUser::class)->handle($data, $photo);

            Ui::toast(
                text: 'User created and invitation sent.',
                variant: 'success'
            );

            $this->dispatch('swal', [
                'title' => 'Invitation sent',
                'text' => "{$user->name} must use the emailed link to verify the address and create a password.",
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

    private function normalizeContactNumber(?string $contactNumber): ?string
    {
        $contactNumber = trim((string) $contactNumber);

        if ($contactNumber === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $contactNumber) ?? '';

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }

    /*
    |--------------------------------------------------------------------------
    | Archived users
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Facility assignment
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Computed properties
    |--------------------------------------------------------------------------
    */

    private function managedUser(int $userId): User
    {
        return app(UserListQuery::class)->managed($userId, (int) auth()->id());
    }

    private function managedArchivedUser(int $userId): User
    {
        return app(UserListQuery::class)->archived($userId);
    }

    #[Computed]
    public function users()
    {
        return app(UserListQuery::class)->users(
            $this->roleFilter, $this->accountStatusFilter, $this->search, $this->sortBy, $this->sortDirection
        );
    }

    #[Computed]
    public function archivedUsers()
    {
        return app(UserListQuery::class)->archivedUsers($this->search);
    }

    #[Computed]
    public function availableFacilities()
    {
        if (! $this->showAssignmentModal) {
            return collect();
        }

        return app(UserListQuery::class)->availableFacilities($this->selectedAdminId);
    }

    #[Computed]
    public function userStats(): array
    {
        return app(UserListQuery::class)->stats();
    }

    public function render(): View
    {
        return view('livewire.users.index');
    }
}
