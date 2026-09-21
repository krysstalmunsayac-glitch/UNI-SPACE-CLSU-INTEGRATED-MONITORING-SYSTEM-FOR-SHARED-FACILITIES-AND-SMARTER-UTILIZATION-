<div
    wire:ignore.self
    x-data="scheduleCalendar(@js($this->calendarEvents), @js($view), $wire)"
    x-init="initCalendar()"
    class="min-w-0 w-full max-w-full"
>
    @include('livewire.schedules.components.calendar-assets')
    @include('livewire.schedules.components.page-header')
    @include('livewire.schedules.components.calendar')
    @if ($showArchivedModal)
        @include('livewire.schedules.components.archived-schedules-modal')
    @endif
    @if ($showModal)
        @include('livewire.schedules.components.schedule-form-modal')
    @endif
</div>

@include('livewire.schedules.components.calendar-styles')

