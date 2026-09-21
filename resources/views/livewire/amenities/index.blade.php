<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('livewire.amenities.components.archived-amenities-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
    @else
    @include('livewire.amenities.components.page-header')
    @include('livewire.amenities.components.amenities-table')
    @if ($showArchivedModal)
        <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
            @include('livewire.amenities.components.archived-amenities-modal')
        </x-ui::modal>
    @endif
    @if ($showModal)
        @include('livewire.amenities.components.amenity-form-modal')
    @endif
    @if ($showViewModal)
        @include('livewire.amenities.components.amenity-view-modal')
    @endif
    @if ($showCreateConfirmation)
        <x-ui::modal wire:model.self="showCreateConfirmation" class="md:w-[28rem]">
            <div class="space-y-6">
                <div>
                    <x-ui::heading size="lg">Confirm new amenity</x-ui::heading>
                    <x-ui::subheading>
                        Are you sure you want to add <span class="font-semibold">{{ $name }}</span> as a new amenity?
                    </x-ui::subheading>
                </div>
                <div class="flex gap-2">
                    <x-ui::button wire:click="save(true)" variant="primary" class="flex-1">Add amenity</x-ui::button>
                    <x-ui::button wire:click="$set('showCreateConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
                </div>
            </div>
        </x-ui::modal>
    @endif
    @if ($showStatusConfirmation)
        <x-ui::modal wire:model.self="showStatusConfirmation" class="md:w-[28rem]">
            <div class="space-y-6">
                <div>
                    <x-ui::heading size="lg">
                        Confirm amenity {{ $pendingStatusWillActivate ? 'activation' : 'deactivation' }}
                    </x-ui::heading>
                    <x-ui::subheading>
                        Make
                        <span class="font-semibold">{{ $pendingStatusName }}</span>
                        {{ $pendingStatusWillActivate ? 'available again?' : 'unavailable for new requests?' }}
                    </x-ui::subheading>
                </div>

                @if (! $pendingStatusWillActivate)
                    <x-ui::input wire:model="deactivationConfirmation" label="Type DEACTIVATE to confirm" placeholder="DEACTIVATE" autocomplete="off" />
                @endif

                <div class="flex gap-2">
                    <x-ui::button wire:click="confirmToggleStatus" :variant="$pendingStatusWillActivate ? 'primary' : 'danger'" class="flex-1">
                        {{ $pendingStatusWillActivate ? 'Activate amenity' : 'Deactivate amenity' }}
                    </x-ui::button>
                    <x-ui::button wire:click="$set('showStatusConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
                </div>
            </div>
        </x-ui::modal>
    @endif
    @endif
</div>

