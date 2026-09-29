<x-ui::modal wire:model.self="showWalkInRequestModal" class="md:w-[32rem]">
    <div class="space-y-5">
        <div>
            <x-ui::heading size="lg">New walk-in request</x-ui::heading>
            <x-ui::subheading>Choose the facility, then enter the guest reservation details. Walk-in reservations are approved immediately.</x-ui::subheading>
        </div>

        <x-ui::select wire:model="walkInFacilityId" label="Facility" placeholder="Select an available facility">
            @foreach ($this->walkInFacilities as $facility)
                <x-ui::select.option value="{{ $facility->FID }}">
                    {{ $facility->Facility_Name }}{{ $facility->Office ? ' — '.$facility->Office : '' }}
                </x-ui::select.option>
            @endforeach
        </x-ui::select>

        <x-ui::select wire:model="walkInInitialStatus" label="Initial request status">
            <x-ui::select.option value="Pending">Pending — review it later in Request Management</x-ui::select.option>
            <x-ui::select.option value="Approved">Approved — reserve the facility immediately</x-ui::select.option>
            @if (auth()->user()->isSuperAdmin())
                <x-ui::select.option value="Ended">Completed — enter a past event for calendar history</x-ui::select.option>
            @endif
        </x-ui::select>

        @if ($this->walkInFacilities->isEmpty())
            <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                No available facilities are assigned to your account.
            </p>
        @endif

        <div class="flex justify-end gap-3">
            <x-ui::button variant="ghost" wire:click="$set('showWalkInRequestModal', false)">Cancel</x-ui::button>
            <x-ui::button variant="primary" icon="arrow-right" wire:click="beginWalkInRequest" :disabled="$this->walkInFacilities->isEmpty()">
                Continue
            </x-ui::button>
        </div>
    </div>
</x-ui::modal>
