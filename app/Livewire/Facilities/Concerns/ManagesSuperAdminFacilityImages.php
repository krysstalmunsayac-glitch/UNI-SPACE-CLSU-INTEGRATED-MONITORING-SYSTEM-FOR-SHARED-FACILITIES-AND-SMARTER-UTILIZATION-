<?php

namespace App\Livewire\Facilities\Concerns;

trait ManagesSuperAdminFacilityImages
{
    public function removeLegacyImage(): void
    {
        $this->removeLegacyImage = true;
        $this->legacyImageUrl = null;
        $this->resetErrorBag('images');
    }

    public function removeExistingImage(int $imageId): void
    {
        $image = collect($this->existingImages)->firstWhere('id', $imageId);

        if (! $image) {
            return;
        }

        $this->removedImageIds[] = $imageId;
        $this->removedImageIds = array_values(array_unique($this->removedImageIds));
        $this->existingImages = array_values(array_filter(
            $this->existingImages,
            fn (array $existingImage) => $existingImage['id'] !== $imageId,
        ));
        $this->resetErrorBag('images');
    }

    public function removeNewImage(int $index): void
    {
        if (! array_key_exists($index, $this->images)) {
            return;
        }

        unset($this->images[$index]);
        $this->images = array_values($this->images);
        $this->resetErrorBag('images');
    }
}
