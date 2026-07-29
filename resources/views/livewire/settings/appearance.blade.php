<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    //
}; ?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout heading="Appearance" subheading="Choose how UNI Space looks on this device">
        <flux:radio.group
            x-data
            variant="segmented"
            x-model="$flux.appearance"
            class="appearance-theme-picker"
            aria-label="Color appearance"
        >
            <flux:radio value="light" icon="sun">Light</flux:radio>
            <flux:radio value="dark" icon="moon">Dark</flux:radio>
            <flux:radio value="system" icon="computer-desktop">System</flux:radio>
        </flux:radio.group>

        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
            Default: <span class="font-semibold text-emerald-800 dark:text-emerald-300">Light</span>
            &mdash; you can switch to Dark or System at any time.
        </p>
    </x-settings.layout>
</div>
