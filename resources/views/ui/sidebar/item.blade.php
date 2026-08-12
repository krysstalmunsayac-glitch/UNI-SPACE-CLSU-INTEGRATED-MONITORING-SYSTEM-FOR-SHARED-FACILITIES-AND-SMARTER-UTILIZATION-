@props(['href' => '#', 'current' => false, 'icon' => null, 'as' => null])
@php $classes = 'flex w-full items-center rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-white/10'.($current ? ' bg-yellow-400 text-emerald-800' : ''); @endphp
@if($as === 'button')<button {{ $attributes->except(['href'])->class($classes) }}>{{ $slot }}</button>@else<a href="{{ $href }}" @if($current) aria-current="page" @endif {{ $attributes->except(['href'])->class($classes) }}>{{ $slot }}</a>@endif
