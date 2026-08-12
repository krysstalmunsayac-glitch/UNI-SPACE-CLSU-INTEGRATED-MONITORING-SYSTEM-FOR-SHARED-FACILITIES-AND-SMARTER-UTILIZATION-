<x-layouts.home.header>
    <x-ui::main class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl space-y-8">
            @include('events.components.create-page-header')
            @include('events.components.success-message')
            @include('events.components.event-form')
        </div>
    </x-ui::main>
</x-layouts.home.header>
