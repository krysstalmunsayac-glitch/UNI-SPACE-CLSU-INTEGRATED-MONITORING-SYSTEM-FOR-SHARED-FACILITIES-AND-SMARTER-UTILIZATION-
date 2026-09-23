<?php

namespace App\Actions\Facilities;

use App\Models\Facility;
use App\Services\FacilityImageProcessor;
use Illuminate\Support\Facades\Storage;

class SaveFacility
{
    public function __construct(private readonly FacilityImageProcessor $imageProcessor) {}

    public function handle(?Facility $facility, array $data, array $newImages = [], array $removedImageIds = []): Facility
    {
        $facility ??= new Facility;
        $facility->fill($data)->save();

        if ($removedImageIds !== []) {
            $images = $facility->images()->whereIn('id', $removedImageIds)->get();
            Storage::disk('public')->delete($images->pluck('image_path')->filter()->all());
            $facility->images()->whereIn('id', $removedImageIds)->delete();
        }

        foreach ($newImages as $image) {
            $facility->images()->create(['image_path' => $this->imageProcessor->store($image)]);
        }

        return $facility->refresh();
    }
}
