@props(['src' => null, 'name' => '', 'initials' => null, 'size' => 'base'])
@php $dimension = $size === 'sm' ? 'size-8' : 'size-10'; @endphp
<span {{ $attributes->class("$dimension inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-100 font-semibold text-emerald-800") }}>
    @if($src)<img src="{{ $src }}" alt="{{ $name }}" class="h-full w-full object-cover">@else{{ $initials ?: mb_substr($name, 0, 1) }}@endif
</span>
