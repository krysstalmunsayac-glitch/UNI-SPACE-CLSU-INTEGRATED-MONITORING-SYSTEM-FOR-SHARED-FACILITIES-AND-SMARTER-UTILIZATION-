<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\UserManagementService;

class CreateUser
{
    public function __construct(private readonly UserManagementService $users) {}

    public function handle(array $data, mixed $photo = null): User
    {
        return $this->users->create($data, $photo);
    }
}
