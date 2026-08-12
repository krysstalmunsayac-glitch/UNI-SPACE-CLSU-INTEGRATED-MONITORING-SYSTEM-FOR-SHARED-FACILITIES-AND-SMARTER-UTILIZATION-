@props(['href' => null, 'as' => null, 'variant' => null, 'icon' => null])
@php
    $classes = 'flex min-h-9 w-full items-center gap-2 rounded-md px-3 py-1.5 text-left text-sm hover:bg-zinc-100 disabled:opacity-60 dark:hover:bg-zinc-700'.($variant === 'danger' ? ' text-red-600' : ' text-zinc-800 dark:text-zinc-100');
    $wireClick = $attributes->get('wire:click');
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->except(['href'])->class($classes) }}>
        @if($icon)<x-ui::icon :name="$icon" class="size-5 shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $attributes->get('type', 'button') }}"
        @if($wireClick) wire:loading.attr="disabled" wire:target="{{ $wireClick }}" @endif
        {{ $attributes->except(['type'])->class($classes) }}
    >
        <span class="contents" @if($wireClick) wire:loading.remove wire:target="{{ $wireClick }}" @endif>
            @if($icon)<x-ui::icon :name="$icon" class="size-5 shrink-0" />@endif
            {{ $slot }}
        </span>
        @if($wireClick)
            <span class="items-center gap-2" wire:loading.flex wire:target="{{ $wireClick }}">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z" />
                </svg>
                <span>Processing...</span>
            </span>
        @endif
    </button>
@endif
