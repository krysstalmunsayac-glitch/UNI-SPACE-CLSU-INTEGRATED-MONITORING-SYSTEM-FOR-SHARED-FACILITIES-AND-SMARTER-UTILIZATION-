@props(['label' => null, 'description' => null, 'value' => null])
<label {{ $attributes->only('class')->class('flex items-start gap-2 text-sm') }}>
    <input type="radio" value="{{ $value }}" {{ $attributes->except(['class', 'label', 'description', 'value'])->class('mt-0.5 size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-600') }}>
    <span>@if($label)<span class="font-medium">{{ $label }}</span>@endif @if($description)<span class="block text-xs text-zinc-500">{{ $description }}</span>@endif {{ $slot }}</span>
</label>
