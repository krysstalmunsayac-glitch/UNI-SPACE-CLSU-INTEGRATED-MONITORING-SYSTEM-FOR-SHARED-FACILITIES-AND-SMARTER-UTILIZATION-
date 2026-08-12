@if ($showStatusConfirmation)
    <x-ui::modal wire:model.self="showStatusConfirmation" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    Confirm facility {{ $pendingStatusWillActivate ? 'activation' : 'deactivation' }}
                </x-ui::heading>
                <x-ui::subheading>
                    Make
                    <span class="font-semibold">{{ $pendingStatusName }}</span>
                    {{ $pendingStatusWillActivate ? 'available again?' : 'unavailable and cancel its active requests?' }}
                </x-ui::subheading>
            </div>

            @if (! $pendingStatusWillActivate)
                <x-ui::input wire:model="deactivationConfirmation" label="Type DEACTIVATE to confirm" placeholder="DEACTIVATE" autocomplete="off" />
            @endif

            <div class="flex gap-2">
                <x-ui::button wire:click="confirmToggleStatus" :variant="$pendingStatusWillActivate ? 'primary' : 'danger'" class="flex-1">
                    {{ $pendingStatusWillActivate ? 'Activate facility' : 'Deactivate facility' }}
                </x-ui::button>
                <x-ui::button wire:click="$set('showStatusConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
            </div>
        </div>
    </x-ui::modal>
@endif
