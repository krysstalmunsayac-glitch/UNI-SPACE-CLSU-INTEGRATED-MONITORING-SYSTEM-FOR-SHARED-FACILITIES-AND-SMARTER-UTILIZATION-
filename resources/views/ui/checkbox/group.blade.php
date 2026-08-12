@props(['label' => null])
<fieldset {{ $attributes->class('grid gap-2') }}>@if($label)<legend class="mb-2 text-sm font-semibold">{{ $label }}</legend>@endif{{ $slot }}</fieldset>
