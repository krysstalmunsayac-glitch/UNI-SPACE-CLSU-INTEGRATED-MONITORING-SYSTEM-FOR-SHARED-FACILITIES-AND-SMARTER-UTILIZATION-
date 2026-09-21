<div class="w-full">
    @include('livewire.feedback.components.page-header')
    @include('livewire.feedback.components.feedback-table')

    @if ($showViewModal)
        @include('livewire.feedback.components.feedback-view-modal')
    @endif
</div>

