<?php

namespace App\Livewire\Users\Concerns;

use App\Actions\Lifecycle\ArchiveRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Services\UserManagementService;
use App\Support\Ui;
use Illuminate\Validation\Rule;

trait ManagesUserStatus
{
    public function delete(int $userId): void
    {
        $user = $this->managedUser($userId);

        if ($user->id === auth()->id()) {
            Ui::toast(
                text: 'You cannot archive your own account.',
                variant: 'danger'
            );

            return;
        }

        // An administrator archive overrides any self-service recovery window.
        $user->update(['self_deleted_at' => null]);
        app(ArchiveRecord::class)->handle($user);

        Ui::toast(
            text: 'User moved to archived records.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'User archived',
            'text' => 'The user can be restored from Archived Users.',
            'icon' => 'success',
        ]);

        // Keep the administrator on the current paginated table after archiving.
    }

    public function requestToggleActive(int $userId): void
    {
        $user = $this->managedUser($userId);

        if (! $user->email_verified_at && ! $user->is_active) {
            Ui::toast(text: 'This account must complete its email invitation before it can be activated.', variant: 'danger');

            return;
        }

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
        $user = $this->managedUser((int) $this->pendingStatusUserId);

        if ($user->id === auth()->id()) {
            $this->showQuickStatusConfirmation = false;

            Ui::toast(text: 'You cannot deactivate your own account.', variant: 'danger');

            return;
        }

        if (! $user->email_verified_at && ! $user->is_active) {
            $this->showQuickStatusConfirmation = false;
            Ui::toast(text: 'This account must complete its email invitation before it can be activated.', variant: 'danger');

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

        $user = app(UserManagementService::class)->toggleActive($user);

        Ui::toast(
            text: $user->is_active
                ? 'User activated.'
                : 'User deactivated.',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => $user->is_active ? 'Account activated' : 'Account deactivated',
            'text' => $user->is_active
                ? "{$user->name} can now access the system."
                : "{$user->name} can no longer access the system.",
            'icon' => 'success',
        ]);

        $this->showQuickStatusConfirmation = false;
        $this->pendingStatusUserId = null;
        $this->deactivationConfirmation = '';
    }

    public function openArchivedUsers(): void
    {
        $this->resetPage('archivedUsersPage');
        $this->showArchivedModal = true;
    }

    public function restoreUser(int $userId): void
    {
        $user = $this->managedArchivedUser($userId);
        app(RestoreRecord::class)->handle($user);
        $user->update(['self_deleted_at' => null]);

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
        $user = $this->managedArchivedUser($userId);

        /*
         * Remove facility assignments before permanent deletion.
         * This prevents pivot records from remaining in the database.
         */
        app(UserManagementService::class)->permanentlyDelete($user);

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
}
