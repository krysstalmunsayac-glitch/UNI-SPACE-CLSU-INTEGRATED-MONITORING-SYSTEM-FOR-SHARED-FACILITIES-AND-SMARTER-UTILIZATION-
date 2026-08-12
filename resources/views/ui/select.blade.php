@props(['label' => null, 'description' => null, 'name' => null, 'placeholder' => null])
@php
    $fieldName = $name ?: optional($attributes->wire('model'))->value();
    $hasError = $fieldName && $errors->has($fieldName);
@endphp
<label class="grid min-w-0 gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">
    @if($label)<span>{{ $label }}</span>@endif
    <select data-ui-control @if($name) name="{{ $name }}" @endif @if($hasError) aria-invalid="true" @endif {{ $attributes->except(['label', 'description', 'placeholder'])->class(['h-10 min-w-0 w-full max-w-full rounded-lg border bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none dark:bg-zinc-900 dark:text-white', 'border-red-500 focus:border-red-600 focus:ring-2 focus:ring-red-500/20 dark:border-red-500' => $hasError, 'border-zinc-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-600' => ! $hasError]) }}>
        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
    </select>
    @if($description)<span class="text-xs font-normal text-zinc-500">{{ $description }}</span>@endif
    @if($fieldName) @error($fieldName)<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror @endif
</label>
