<?php

namespace App\Livewire\Users\Concerns;

use App\Services\UserManagementService;
use App\Support\Ui;
use Illuminate\Support\Facades\RateLimiter;

trait ManagesUserInvitations
{
    public function resendInvitation(int $userId): void
    {
        $user = $this->managedUser($userId);
        if ($user->email_verified_at) {
            Ui::toast(text: 'This email is already verified.', variant: 'info');

            return;
        }

        $key = 'managed-user-invitation:'.auth()->id().':'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            Ui::toast(text: 'Too many resend attempts. Try again later.', variant: 'danger');

            return;
        }

        app(UserManagementService::class)->resendInvitation($user);
        RateLimiter::hit($key, 3600);
        Ui::toast(text: 'New invitation sent; the previous link is invalid.', variant: 'success');
    }

    public function revokeInvitation(int $userId): void
    {
        $user = $this->managedUser($userId);
        abort_if($user->email_verified_at, 409, 'This account is already verified.');
        app(UserManagementService::class)->revokeInvitation($user);
        Ui::toast(text: 'Invitation revoked.', variant: 'success');
    }
}
