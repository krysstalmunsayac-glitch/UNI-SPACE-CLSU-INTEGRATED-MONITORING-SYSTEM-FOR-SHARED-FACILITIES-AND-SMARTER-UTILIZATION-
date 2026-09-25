<?php

namespace App\Livewire\Queries;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserListQuery
{
    public function managed(int $userId, int $actorId): User
    {
        return User::query()
            ->where(fn ($query) => $query->where('user_type', '!=', 'super_admin')->orWhere('id', $actorId))
            ->findOrFail($userId);
    }

    public function archived(int $userId): User
    {
        return User::onlyTrashed()->where('user_type', '!=', 'super_admin')->findOrFail($userId);
    }

    public function users(string $role, string $status, string $search, string $sortBy, string $direction): LengthAwarePaginator
    {
        return User::query()
            ->where('user_type', '!=', 'super_admin')
            ->when(in_array($role, ['admin', 'user'], true), fn ($query) => $query->where('user_type', $role))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($nested) => $nested->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('clsu_id', 'like', $term)
                    ->orWhere('account_type', 'like', $term));
            })
            ->orderBy($sortBy, $direction)
            ->orderBy('id', $direction)
            ->paginate(perPage: 8, pageName: 'usersPage');
    }

    public function archivedUsers(string $search): LengthAwarePaginator
    {
        return User::onlyTrashed()
            ->where('user_type', '!=', 'super_admin')
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('clsu_id', 'like', '%'.$search.'%')
                ->orWhere('account_type', 'like', '%'.$search.'%')
                ->orWhere('user_type', 'like', '%'.$search.'%')))
            ->orderBy('deleted_at')
            ->orderBy('id')
            ->paginate(perPage: 8, pageName: 'archivedUsersPage');
    }

    public function availableFacilities(?int $selectedAdminId): Collection
    {
        return Facility::query()
            ->whereDoesntHave('assignedAdmins', fn ($query) => $query->where('users.id', '!=', $selectedAdminId))
            ->orderBy('Facility_Name')
            ->get(['FID', 'Facility_Name']);
    }

    public function stats(): array
    {
        $stats = User::query()->where('user_type', '!=', 'super_admin')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as office_admins")
            ->selectRaw("SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) as end_users")
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->first();

        return [
            'total' => (int) $stats->total,
            'office_admins' => (int) $stats->office_admins,
            'end_users' => (int) $stats->end_users,
            'inactive' => (int) $stats->inactive,
        ];
    }
}
