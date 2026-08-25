@props(['label' => null, 'description' => null, 'type' => 'text', 'name' => null, 'as' => null, 'icon' => null, 'revealable' => false])
@php
    $fieldName = $name ?: optional($attributes->wire('model'))->value();
    $hasError = $fieldName && $errors->has($fieldName);
    $inputAttributes = $attributes->except(['label', 'description', 'icon', 'as', 'revealable']);
@endphp
<label class="grid min-w-0 gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">
    @if($label)<span>{{ $label }}</span>@endif
    @if($as === 'button')
        <button data-ui-control type="button" {{ $inputAttributes->class('h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 text-left text-sm text-zinc-500 shadow-sm dark:border-zinc-600 dark:bg-zinc-900') }}>{{ $attributes->get('placeholder') }}</button>
    @else
        <div @if($revealable) x-data="{ visible: false }" @endif class="relative min-w-0">
            <input data-ui-control type="{{ $type }}" @if($revealable) x-bind:type="visible ? 'text' : 'password'" @endif @if($name) name="{{ $name }}" @endif @if($hasError) aria-invalid="true" @endif {{ $inputAttributes->class(['h-10 min-w-0 w-full rounded-lg border bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none transition dark:bg-zinc-900 dark:text-white', 'pr-11' => $revealable, 'border-red-500 focus:border-red-600 focus:ring-2 focus:ring-red-500/20 dark:border-red-500' => $hasError, 'border-zinc-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-600' => ! $hasError]) }}>
            @if($revealable)
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-zinc-500 transition hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-300"
                    x-on:click="visible = ! visible"
                    x-bind:aria-label="visible ? 'Hide password' : 'Show password'"
                    x-bind:aria-pressed="visible"
                >
                    <svg x-show="! visible" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M2.1 12a10.8 10.8 0 0 1 19.8 0 10.8 10.8 0 0 1-19.8 0Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-cloak x-show="visible" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m3 3 18 18" />
                        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                        <path d="M9.9 4.2A10.8 10.8 0 0 1 21.9 12a11.7 11.7 0 0 1-2.1 3.2" />
                        <path d="M6.6 6.6A11.5 11.5 0 0 0 2.1 12a10.8 10.8 0 0 0 14.1 6" />
                    </svg>
                </button>
            @endif
        </div>
    @endif
    @if($description)<span class="text-xs font-normal text-zinc-500">{{ $description }}</span>@endif
    @if($fieldName) @error($fieldName)<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror @endif
</label>
