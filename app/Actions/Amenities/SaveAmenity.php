<?php

namespace App\Actions\Amenities;

use App\Models\Amenity;

class SaveAmenity
{
    public function handle(?Amenity $amenity, array $data, array $facilityIds, int $actorId): Amenity
    {
        $amenity ??= new Amenity(['created_by' => $actorId]);
        $amenity->fill($data)->save();
        $amenity->facilities()->sync($facilityIds);

        return $amenity->refresh();
    }
}
