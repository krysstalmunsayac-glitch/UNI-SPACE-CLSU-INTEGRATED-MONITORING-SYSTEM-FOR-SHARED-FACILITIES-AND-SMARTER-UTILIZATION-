<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class UserInvitationService
{
    public const EXPIRATION_MINUTES = 60;

    public function send(User $user): void
    {
        $broker = Password::broker();
        $broker->deleteToken($user);
        $token = $broker->createToken($user);
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);

        $user->forceFill([
            'email_verified_at' => null,
            'is_active' => false,
            'invitation_sent_at' => now(),
            'invitation_expires_at' => $expiresAt,
            'invitation_revoked_at' => null,
        ])->save();

        $url = URL::temporarySignedRoute('invitation.accept', $expiresAt, [
            'token' => $token,
            'email' => $user->email,
        ]);

        $user->notify(new UserInvitationNotification($url, $expiresAt->format('M j, Y g:i A T')));
    }

    public function revoke(User $user): void
    {
        Password::broker()->deleteToken($user);
        $user->forceFill([
            'is_active' => false,
            'invitation_expires_at' => null,
            'invitation_revoked_at' => now(),
        ])->save();
    }
}
