<div class="w-full" @if (! $showModal && ! $showStatusConfirmation) wire:poll.15s @endif>
    @include('livewire.facilities.components.office-admin.page-header')
    @include('livewire.facilities.components.office-admin.facilities-grid')
    @if ($showModal)
        @include('livewire.facilities.components.super-admin.facility-form-modal')
    @endif
    @include('livewire.facilities.components.status-confirmation-modal')
</div>

