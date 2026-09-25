<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('livewire.requests.components.archived-requests-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
        @if ($showViewModal)
            @include('livewire.requests.components.request-view-modal')
        @endif
    @else
    @include('livewire.requests.components.page-header')
    @include('livewire.requests.components.requests-table')
    @if ($showArchivedModal)
        <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
            @include('livewire.requests.components.archived-requests-modal')
        </x-ui::modal>
    @endif
    @if ($showViewModal)
        @include('livewire.requests.components.request-view-modal')
    @endif
    @if ($showReviewModal)
        @include('livewire.requests.components.request-review-modal')
    @endif
    @if ($showRejectModal)
        @include('livewire.requests.components.request-reject-modal')
    @endif
    @if ($showCancelModal)
        @include('livewire.requests.components.request-cancel-modal')
    @endif
    @if ($showPaymentModal)
        @include('livewire.requests.components.request-payment-modal')
    @endif
    @if ($showPaymentProofReplacementModal)
        @include('livewire.requests.components.payment-proof-replacement-modal')
    @endif
    @if ($showPaymentProofPreview)
        @include('livewire.requests.components.payment-proof-preview-modal')
    @endif
    @if ($showModal)
        @include('livewire.requests.components.request-edit-modal')
    @endif
    @endif
</div>


