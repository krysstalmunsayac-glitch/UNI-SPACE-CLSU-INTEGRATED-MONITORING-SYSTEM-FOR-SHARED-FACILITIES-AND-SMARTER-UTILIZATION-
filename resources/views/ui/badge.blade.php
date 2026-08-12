@props(['color' => null, 'variant' => null, 'size' => null])
@php
    $classes = match ($color) {
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'yellow', 'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'green', 'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
    };
@endphp
<span data-ui-badge {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold $classes") }}>{{ $slot }}</span>
