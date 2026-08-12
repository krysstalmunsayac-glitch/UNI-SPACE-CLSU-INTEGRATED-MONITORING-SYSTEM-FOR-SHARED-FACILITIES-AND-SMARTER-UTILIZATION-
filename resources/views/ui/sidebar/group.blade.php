@props(['heading' => null, 'expandable' => false])
<section {{ $attributes->class('grid gap-1') }}>@if($heading)<h3 class="px-3 pt-4 pb-1 text-xs font-bold uppercase tracking-wide opacity-75">{{ $heading }}</h3>@endif{{ $slot }}</section>
