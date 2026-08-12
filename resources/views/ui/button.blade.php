@props(['variant' => 'outline', 'size' => 'base', 'href' => null, 'icon' => null, 'iconTrailing' => null, 'type' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition disabled:pointer-events-none disabled:opacity-60';
    $sizes = $size === 'sm' ? 'h-8 px-3 text-sm' : ($size === 'xs' ? 'h-7 px-2 text-xs' : 'min-h-10 px-4 text-sm');
    $colors = match ($variant) {
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'ghost', 'subtle' => 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700',
        default => 'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white',
    };
    $wireClick = $attributes->get('wire:click');
    $hasLoadingState = ! $href && ($wireClick || $type === 'submit');
@endphp
@if($href)
    <a data-ui-button href="{{ $href }}" {{ $attributes->except(['href'])->class("$base $sizes $colors") }}>
        @if($icon)
            <x-ui::icon :name="$icon" class="size-4 shrink-0" />
        @endif
        {{ $slot }}
        @if($iconTrailing)
            <x-ui::icon :name="$iconTrailing" class="size-4 shrink-0" />
        @endif
    </a>
@else
    <button
        data-ui-button
        type="{{ $type }}"
        @if($hasLoadingState) wire:loading.attr="disabled" @endif
        @if($wireClick) wire:target="{{ $wireClick }}" @endif
        {{ $attributes->except(['type'])->class("$base $sizes $colors") }}
    >
        <span class="inline-flex items-center justify-center gap-2" @if($hasLoadingState) wire:loading.remove @if($wireClick) wire:target="{{ $wireClick }}" @endif @endif>
            @if($icon)
                <x-ui::icon :name="$icon" class="size-4 shrink-0" />
            @endif
            {{ $slot }}
            @if($iconTrailing)
                <x-ui::icon :name="$iconTrailing" class="size-4 shrink-0" />
            @endif
        </span>
        @if($hasLoadingState)
            <span class="items-center justify-center gap-2" wire:loading.flex @if($wireClick) wire:target="{{ $wireClick }}" @endif>
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z" />
                </svg>
                <span>Processing...</span>
            </span>
        @endif
    </button>
@endif
