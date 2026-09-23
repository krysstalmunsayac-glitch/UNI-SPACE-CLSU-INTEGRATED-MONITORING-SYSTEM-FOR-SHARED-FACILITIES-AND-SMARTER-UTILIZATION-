<?php

namespace App\Livewire\Amenities\Concerns;

use App\Actions\Lifecycle\ArchiveRecord;
use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Support\Ui;

trait ManagesAmenityLifecycle
{
    public function delete(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId);
        app(ArchiveRecord::class)->handle($amenity);

        Ui::toast(text: 'Amenity archived successfully!', variant: 'success');
        $this->dispatch(
            'swal',
            [
                'title' => 'Amenity archived',
                'text' => 'Amenity archived successfully!',
                'icon' => 'success',
            ]
        );
    }

    public function openArchivedRecords(): void
    {
        $this->resetPage('archivedAmenitiesPage');
        $this->showArchivedModal = true;
    }

    public function restore(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId, withTrashed: true);
        app(RestoreRecord::class)->handle($amenity);

        Ui::toast(text: 'Amenity restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $amenityId): void
    {
        $amenity = $this->getScopedAmenity($amenityId, withTrashed: true);
        app(PermanentlyDeleteRecord::class)->handle($amenity);

        Ui::toast(text: 'Amenity permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }
}
