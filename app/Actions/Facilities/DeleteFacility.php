<?php

namespace App\Actions\Facilities;

use App\Models\Facility;
use Illuminate\Support\Facades\Storage;

class DeleteFacility
{
    public function permanently(Facility $facility): void
    {
        $facility->loadMissing('images');
        Storage::disk('public')->delete($facility->images->pluck('image_path')->filter()->all());
        $facility->assignedAdmins()->detach();
        $facility->images()->delete();
        $facility->forceDelete();
    }
}
