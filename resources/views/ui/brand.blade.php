@props(['href' => '/', 'logo' => null, 'name' => ''])
<a href="{{ $href }}" {{ $attributes->class('inline-flex items-center gap-3 font-bold') }}>
    @if($logo)<img src="{{ $logo }}" alt="" class="size-10 object-contain">@endif
    <span>{{ $name }}</span>
</a>
