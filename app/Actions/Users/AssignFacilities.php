<?php

namespace App\Actions\Users;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignFacilities
{
    public function handle(User $admin, array $facilityIds): void
    {
        $conflict = Facility::query()->whereIn('FID', $facilityIds)
            ->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', '!=', $admin->id))
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'assignedFacilityIds' => "{$conflict->Facility_Name} is already assigned to another Office Admin.",
            ]);
        }

        $admin->syncFacilities($facilityIds);
    }
}
