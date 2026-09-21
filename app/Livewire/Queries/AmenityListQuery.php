<?php

namespace App\Livewire\Queries;

use App\Models\Amenity;
use App\Models\Facility;
use App\Models\User;

class AmenityListQuery
{
    public function active(User $actor, string $search, string $sortBy, string $direction)
    {
        return Amenity::query()
            ->with(['facilities:FID,Facility_Name', 'creator:id,name'])
            ->withSum(['requests as current_usage_quantity' => fn ($query) => $query->whereIn('Status', ['Pending', 'Approved'])], 'request_facility_amenities.quantity')
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")->orWhere('Description', 'like', "%{$search}%")))
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facilities.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->orderBy($sortBy, $direction)->paginate(8, pageName: 'amenitiesPage');
    }

    public function archived(User $actor, string $search)
    {
        return Amenity::onlyTrashed()->with('facilities:FID,Facility_Name')
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facilities.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")->orWhere('Description', 'like', "%{$search}%")))
            ->orderByDesc('deleted_at')->paginate(8, pageName: 'archivedAmenitiesPage');
    }

    public function facilities(User $actor)
    {
        return Facility::query()
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->orderBy('Facility_Name')->get(['FID', 'Facility_Name', 'Office']);
    }

    public function scoped(User $actor, int $id, bool $withTrashed = false): Amenity
    {
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
        return Amenity::query()
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facilities.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->findOrFail($id);
    }
}
