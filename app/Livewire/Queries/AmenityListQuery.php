<?php

namespace App\Livewire\Queries;

use App\Models\Amenity;
use App\Models\Facility;
use App\Models\User;

class AmenityListQuery
{
    public function active(User $actor, string $search, string $sortBy, string $direction, ?int $facilityId = null)
    {
        $this->authorizeActor($actor);

        return Amenity::query()
            ->with(['facilities:FID,Facility_Name', 'creator:id,name'])
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('Description', 'like', "%{$search}%")))
            ->when($facilityId, fn ($query) => $query->whereHas(
                'facilities',
                fn ($facilities) => $facilities->where('facilities.FID', $facilityId),
            ))
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facilities.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->orderBy($sortBy, $direction)
            ->orderBy('AID', $direction)
            ->paginate(8, pageName: 'amenitiesPage');
    }

    public function archived(User $actor, string $search)
    {
        $this->authorizeActor($actor);

        return Amenity::onlyTrashed()->with('facilities:FID,Facility_Name')
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facilities.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('Description', 'like', "%{$search}%")))
            ->orderBy('deleted_at')
            ->orderBy('AID')
            ->paginate(8, pageName: 'archivedAmenitiesPage');
    }

    public function facilities(User $actor)
    {
        $this->authorizeActor($actor);

        return Facility::query()
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->orderBy('Facility_Name')->get(['FID', 'Facility_Name', 'Office']);
    }

    public function scoped(User $actor, int $id, bool $withTrashed = false): Amenity
    {
        $this->authorizeActor($actor);

        $query = Amenity::query()->when($withTrashed, fn ($query) => $query->withTrashed());
        if ($actor->isAdmin()) {
            $ids = $actor->assignedFacilityIds();
            $query->whereHas('facilities', fn ($facilities) => $facilities->whereIn('facilities.FID', $ids))
                ->whereDoesntHave('facilities', fn ($facilities) => $facilities->whereNotIn('facilities.FID', $ids));
        }

        return $query->findOrFail($id);
    }

    public function visible(User $actor, int $id): Amenity
    {
        $this->authorizeActor($actor);

        return Amenity::query()
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facilities.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->findOrFail($id);
    }

    private function authorizeActor(User $actor): void
    {
        abort_unless(
            $actor->isSuperAdminOrAdmin(),
            403,
            'Only a Super Admin or Office Admin can access amenities management.',
        );
    }
}
