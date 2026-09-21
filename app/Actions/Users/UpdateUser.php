<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\UserManagementService;

class UpdateUser
{
    public function __construct(private readonly UserManagementService $users) {}

    public function handle(User $user, array $data, mixed $photo = null): array
    {
        return $this->users->update($user, $data, $photo);
    }
}
