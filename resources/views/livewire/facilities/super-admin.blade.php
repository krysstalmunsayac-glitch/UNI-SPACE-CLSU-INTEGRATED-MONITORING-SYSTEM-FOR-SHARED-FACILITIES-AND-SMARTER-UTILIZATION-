<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('livewire.facilities.components.super-admin.archived-facilities-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
    @else
    @include('livewire.facilities.components.super-admin.page-header')
    @include('livewire.facilities.components.super-admin.facilities-table')
    @if ($showArchivedModal)
        <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
            @include('livewire.facilities.components.super-admin.archived-facilities-modal')
        </x-ui::modal>
    @endif
    @if ($showModal)
        @include('livewire.facilities.components.super-admin.facility-form-modal')
    @endif
    @if ($showCreateConfirmation)
        <x-ui::modal wire:model.self="showCreateConfirmation" class="md:w-[28rem]">
            <div class="space-y-6">
                <div>
                    <x-ui::heading size="lg">Confirm new facility</x-ui::heading>
                    <x-ui::subheading>
                        Are you sure you want to add <span class="font-semibold">{{ $Facility_Name }}</span> as a new facility?
                    </x-ui::subheading>
                </div>
                <div class="flex gap-2">
                    <x-ui::button wire:click="save(true)" variant="primary" class="flex-1">Add facility</x-ui::button>
                    <x-ui::button wire:click="$set('showCreateConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
                </div>
            </div>
        </x-ui::modal>
    @endif
    @include('livewire.facilities.components.status-confirmation-modal')
    @endif
</div>

