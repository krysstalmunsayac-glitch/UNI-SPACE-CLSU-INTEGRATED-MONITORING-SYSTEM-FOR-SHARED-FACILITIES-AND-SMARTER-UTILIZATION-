<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserManagementService
{
    public function __construct(private readonly UserInvitationService $invitations) {}

    public function create(array $data, mixed $photo = null): User
    {
        $data['password'] = Hash::make(Str::random(64));
        $data['email_verified_at'] = null;
        $data['is_active'] = false;

        if ($photo) {
            $data['ImageID'] = $photo->store('profile-photos', 'public');
        }

        $user = User::query()->create($data);
        $this->invitations->send($user);

        return $user;
    }

    public function update(User $user, array $data, mixed $photo = null): array
    {
        $emailChanged = $data['email'] !== Str::lower($user->email);
        $roleChanged = $data['user_type'] !== $user->user_type;
        $statusChanged = (bool) $data['is_active'] !== (bool) $user->is_active;

        if ($photo) {
            $newPhotoPath = $photo->store('profile-photos', 'public');
            if ($user->ImageID && ! filter_var($user->ImageID, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->ImageID);
            }
            $data['ImageID'] = $newPhotoPath;
        }

        $user->update($data);
        if ($emailChanged) {
            $this->invitations->send($user);
        }

        return compact('user', 'emailChanged', 'roleChanged', 'statusChanged');
    }

    public function resendInvitation(User $user): void
    {
        $this->invitations->send($user);
    }

    public function revokeInvitation(User $user): void
    {
        $this->invitations->revoke($user);
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user->refresh();
    }

    public function permanentlyDelete(User $user): void
    {
        $user->facilities()->detach();
        $user->forceDelete();
    }
}
