<?php

namespace App\Actions\Amenities;

use App\Models\Amenity;

class ToggleAmenityStatus
{
    public function handle(Amenity $amenity): Amenity
    {
        $amenity->update(['Status' => $amenity->Status === 'Available' ? 'Unavailable' : 'Available']);

        return $amenity->refresh();
    }
}
