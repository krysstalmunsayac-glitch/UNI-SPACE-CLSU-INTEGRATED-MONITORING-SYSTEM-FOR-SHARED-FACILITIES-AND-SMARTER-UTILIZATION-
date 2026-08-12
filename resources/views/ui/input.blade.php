@props(['label' => null, 'description' => null, 'type' => 'text', 'name' => null, 'as' => null, 'icon' => null])
@php
    $fieldName = $name ?: optional($attributes->wire('model'))->value();
    $hasError = $fieldName && $errors->has($fieldName);
    $inputAttributes = $attributes->except(['label', 'description', 'icon', 'as']);
@endphp
<label class="grid min-w-0 gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">
    @if($label)<span>{{ $label }}</span>@endif
    @if($as === 'button')
        <button data-ui-control type="button" {{ $inputAttributes->class('h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-left text-sm text-zinc-500 shadow-sm dark:border-zinc-600 dark:bg-zinc-900') }}>{{ $attributes->get('placeholder') }}</button>
    @else
        <input data-ui-control type="{{ $type }}" @if($name) name="{{ $name }}" @endif @if($hasError) aria-invalid="true" @endif {{ $inputAttributes->class(['h-10 min-w-0 w-full rounded-lg border bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none transition dark:bg-zinc-900 dark:text-white', 'border-red-500 focus:border-red-600 focus:ring-2 focus:ring-red-500/20 dark:border-red-500' => $hasError, 'border-zinc-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-600' => ! $hasError]) }}>
    @endif
    @if($description)<span class="text-xs font-normal text-zinc-500">{{ $description }}</span>@endif
    @if($fieldName) @error($fieldName)<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror @endif
</label>
