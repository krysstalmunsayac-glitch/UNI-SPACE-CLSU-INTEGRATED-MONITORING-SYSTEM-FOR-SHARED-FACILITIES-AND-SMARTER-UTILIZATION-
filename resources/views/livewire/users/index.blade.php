<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('livewire.users.components.archived-users-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
    @else
        @include('livewire.users.components.page-header')
        @include('livewire.users.components.users-table')

        @if ($showArchivedModal)
            <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
                @include('livewire.users.components.archived-users-modal')
            </x-ui::modal>
        @endif

        @if ($showAssignmentModal)
            @include('livewire.users.components.assignment-modal')
        @endif
        @if ($showModal || $showRoleChangeConfirmation || $showAccountStatusConfirmation || $showCreateConfirmation)
            @include('livewire.users.components.user-form-modal')
        @endif

        @if ($showQuickStatusConfirmation)
            <x-ui::modal wire:model.self="showQuickStatusConfirmation" class="md:w-[28rem]">
                <div class="space-y-6">
                    <div>
                        <x-ui::heading size="lg">
                            Confirm account {{ $pendingStatusWillActivate ? 'activation' : 'deactivation' }}
                        </x-ui::heading>
                        <x-ui::subheading>
                            {{ $pendingStatusWillActivate ? 'Restore system access for' : 'Remove system access from' }}
                            <span class="font-semibold">{{ $pendingStatusUserName }}</span>?
                        </x-ui::subheading>
                    </div>

                    @if (! $pendingStatusWillActivate)
                        <x-ui::input
                            wire:model="deactivationConfirmation"
                            label="Type DEACTIVATE to confirm"
                            placeholder="DEACTIVATE"
                            autocomplete="off"
                        />
                    @endif

                    <div class="flex gap-2">
                        <x-ui::button
                            wire:click="confirmToggleActive"
                            wire:loading.attr="disabled"
                            wire:target="confirmToggleActive"
                            :variant="$pendingStatusWillActivate ? 'primary' : 'danger'"
                            class="flex-1"
                        >
                            {{ $pendingStatusWillActivate ? 'Activate account' : 'Deactivate account' }}
                        </x-ui::button>
                        <x-ui::button wire:click="$set('showQuickStatusConfirmation', false)" variant="ghost" class="flex-1">
                            Cancel
                        </x-ui::button>
                    </div>
                </div>
            </x-ui::modal>
        @endif
    @endif
</div>

