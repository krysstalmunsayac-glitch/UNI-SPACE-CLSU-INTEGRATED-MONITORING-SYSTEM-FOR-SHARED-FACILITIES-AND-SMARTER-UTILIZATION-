@props(['href' => '#', 'current' => false, 'icon' => null])
<a href="{{ $href }}" @if($current) aria-current="page" @endif {{ $attributes->except(['href'])->class('rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-zinc-100 dark:hover:bg-zinc-700'.($current ? ' bg-emerald-600 text-white' : '')) }}>{{ $slot }}</a>
