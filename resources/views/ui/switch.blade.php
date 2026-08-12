@props(['label' => null, 'description' => null])
<label class="flex items-center justify-between gap-4 text-sm">
    <span>@if($label)<span class="font-medium">{{ $label }}</span>@endif @if($description)<span class="block text-xs text-zinc-500">{{ $description }}</span>@endif</span>
    <input type="checkbox" {{ $attributes->except(['label', 'description'])->class('size-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600') }}>
</label>
