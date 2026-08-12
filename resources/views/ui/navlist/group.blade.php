@props(['expandable' => false, 'expanded' => true, 'heading' => null])
<section {{ $attributes->class('grid gap-1') }}>
    @if($heading)<h3 class="px-3 py-2 text-xs font-bold uppercase tracking-wide opacity-80">{{ $heading }}</h3>@endif
    {{ $slot }}
</section>
