@props(['size' => 'base', 'level' => null])
@php
    $tag = $level ? 'h'.$level : ($size === 'xl' ? 'h1' : ($size === 'lg' ? 'h2' : 'h3'));
    $classes = match ($size) { 'xl' => 'text-[32px] leading-tight', 'lg' => 'text-2xl leading-tight', default => 'text-base leading-snug' };
@endphp
<{{ $tag }} data-ui-heading="{{ $size }}" {{ $attributes->class("$classes font-bold text-zinc-900 dark:text-white") }}>{{ $slot }}</{{ $tag }}>
