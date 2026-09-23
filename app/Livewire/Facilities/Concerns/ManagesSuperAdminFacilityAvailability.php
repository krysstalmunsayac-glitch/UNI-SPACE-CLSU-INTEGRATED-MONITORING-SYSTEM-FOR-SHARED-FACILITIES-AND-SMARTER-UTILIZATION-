<?php

namespace App\Livewire\Facilities\Concerns;

use App\Models\Facility;
use App\Services\FacilityAvailabilityService;
use App\Support\Ui;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait ManagesSuperAdminFacilityAvailability
{
    public function requestToggleStatus(int $facilityId): void
    {
        $facility = Facility::query()->findOrFail($facilityId);
        $this->pendingStatusId = $facility->FID;
        $this->pendingStatusName = $facility->Facility_Name;
        $this->pendingStatusWillActivate = $facility->Status === 'Unavailable';
        $this->setAvailableAtFields($facility->Available_At);
        $this->deactivationConfirmation = '';
        $this->resetValidation('deactivationConfirmation');
        $this->showStatusConfirmation = true;
    }

    public function confirmToggleStatus(): void
    {
        $facility = Facility::query()->findOrFail($this->pendingStatusId);

        if ($facility->Status !== 'Unavailable') {
            $this->validate([
                'deactivationConfirmation' => ['required', 'in:DEACTIVATE'],
                'Available_Date' => ['required', 'date', 'after_or_equal:today'],
                'Available_Hour' => ['required_with:Available_Date', 'in:01,02,03,04,05,06,07,08,09,10,11,12'],
                'Available_Minute' => ['required_with:Available_Date', 'in:00,15,30,45'],
                'Available_Period' => ['required_with:Available_Date', 'in:AM,PM'],
            ], [
                'deactivationConfirmation.required' => 'Type DEACTIVATE to confirm.',
                'deactivationConfirmation.in' => 'Type DEACTIVATE exactly to confirm.',
                'Available_Date.required' => 'Choose when the facility will become available again.',
                'Available_Date.after_or_equal' => 'Choose today or a future date.',
            ]);
        }

        $cancelledCount = app(FacilityAvailabilityService::class)->toggle($facility, $this->availableAtValue());
        $facility->refresh();

        Ui::toast(
            text: $facility->Status === 'Available'
                ? 'Facility reactivated successfully!'
                : "Facility deactivated. {$cancelledCount} active request(s) cancelled.",
            variant: 'success'
        );

        $this->showStatusConfirmation = false;
        $this->pendingStatusId = null;
        $this->Available_At = null;
        $this->Available_Date = null;
        $this->deactivationConfirmation = '';
    }

    private function setAvailableAtFields($availableAt): void
    {
        $this->Available_At = $availableAt?->format('Y-m-d H:i:s');
        $this->Available_Date = $availableAt?->format('Y-m-d');
        $this->Available_Hour = $availableAt?->format('h') ?? '08';
        $this->Available_Minute = $availableAt?->format('i') ?? '00';
        $this->Available_Period = $availableAt?->format('A') ?? 'AM';
    }

    private function availableAtValue(): ?string
    {
        if (! $this->Available_Date) {
            return null;
        }

        $hour = (int) $this->Available_Hour;
        $hour = $this->Available_Period === 'PM' && $hour !== 12 ? $hour + 12 : $hour;
        $hour = $this->Available_Period === 'AM' && $hour === 12 ? 0 : $hour;

        $availableAt = Carbon::parse(sprintf('%s %02d:%s:00', $this->Available_Date, $hour, $this->Available_Minute));

        if ($availableAt->isPast()) {
            throw ValidationException::withMessages([
                'Available_Date' => 'Choose a future date and time.',
            ]);
        }

        return $availableAt->format('Y-m-d H:i:s');
    }
}
