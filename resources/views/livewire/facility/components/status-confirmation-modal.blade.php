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
                <div>
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Available again at</span>
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_5rem_5rem_5rem]">
                        <x-ui::input wire:model="Available_Date" type="date" label="Date" min="{{ now()->toDateString() }}" />
                        <x-ui::select wire:model="Available_Hour" label="Hour">
                            @foreach (range(1, 12) as $hour)
                                <x-ui::select.option value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</x-ui::select.option>
                            @endforeach
                        </x-ui::select>
                        <x-ui::select wire:model="Available_Minute" label="Min">
                            @foreach (['00', '15', '30', '45'] as $minute)
                                <x-ui::select.option value="{{ $minute }}">{{ $minute }}</x-ui::select.option>
                            @endforeach
                        </x-ui::select>
                        <x-ui::select wire:model="Available_Period" label="AM/PM">
                            <x-ui::select.option value="AM">AM</x-ui::select.option>
                            <x-ui::select.option value="PM">PM</x-ui::select.option>
                        </x-ui::select>
                    </div>
                    @error('Available_Date') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
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
