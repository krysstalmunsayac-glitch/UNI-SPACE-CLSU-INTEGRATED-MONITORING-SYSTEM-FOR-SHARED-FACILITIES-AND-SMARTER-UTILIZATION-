<?php

namespace App\Actions\Amenities;

use App\Models\Amenity;
use Illuminate\Support\Facades\DB;

class SaveAmenity
{
    public function handle(?Amenity $amenity, array $data, array $facilityIds, int $actorId): Amenity
    {
        $data['name'] = trim((string) $data['name']);

        return DB::transaction(function () use ($amenity, $data, $facilityIds, $actorId): Amenity {
            $amenity ??= new Amenity(['created_by' => $actorId]);
            $amenity->fill($data)->save();
            $amenity->facilities()->sync($facilityIds);

            return $amenity->refresh();
        }, 3);
    }
}
