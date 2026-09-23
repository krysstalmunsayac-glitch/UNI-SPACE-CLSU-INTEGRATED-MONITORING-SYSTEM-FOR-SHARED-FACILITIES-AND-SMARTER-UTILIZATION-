<?php

namespace App\Livewire\Users\Concerns;

use App\Actions\Users\AssignFacilities;
use App\Support\Ui;
use Illuminate\Validation\Rule;

trait ManagesUserAssignments
{
    public function openAssignments(int $userId): void
    {
        $user = $this->managedUser($userId);

        if ($user->user_type !== 'admin') {
            Ui::toast(
                text: 'Facility assignment is only available for Office Admin accounts.',
                variant: 'info'
            );

            return;
        }

        $this->selectedAdminId = $user->id;

        $this->assignedFacilityIds = $user->facilities()
            ->pluck('facilities.FID')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->showAssignmentModal = true;
    }

    public function saveAssignments(): void
    {
        if (! $this->selectedAdminId) {
            return;
        }

        $admin = $this->managedUser((int) $this->selectedAdminId);

        if ($admin->user_type !== 'admin') {
            Ui::toast(
                text: 'Only Office Admin accounts can be assigned facilities.',
                variant: 'danger'
            );

            return;
        }

        $validated = $this->validate([
            'assignedFacilityIds' => ['array'],
            'assignedFacilityIds.*' => ['integer', 'distinct', Rule::exists('facilities', 'FID')->whereNull('deleted_at')],
        ]);

        $facilityIds = array_values(array_unique(array_map(
            'intval',
            $validated['assignedFacilityIds'],
        )));

        app(AssignFacilities::class)->handle($admin, $facilityIds);

        Ui::toast(
            text: 'Facility assigned successfully.',
            variant: 'success'
        );

        $this->showAssignmentModal = false;
        $this->selectedAdminId = null;
        $this->assignedFacilityIds = [];
    }
}
