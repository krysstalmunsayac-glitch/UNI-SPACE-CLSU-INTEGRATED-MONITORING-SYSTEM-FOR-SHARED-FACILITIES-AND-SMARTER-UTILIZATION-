@props(['label' => null, 'description' => null, 'type' => 'text', 'name' => null, 'as' => null, 'icon' => null, 'revealable' => false])
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
        <div @class(['relative', 'contents' => ! ($revealable && $type === 'password')])>
            <input data-ui-control type="{{ $type }}" @if($name) name="{{ $name }}" @endif @if($hasError) aria-invalid="true" @endif {{ $inputAttributes->class(['h-10 min-w-0 w-full rounded-lg border bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none transition dark:bg-zinc-900 dark:text-white', 'pr-11' => $revealable && $type === 'password', 'border-red-500 focus:border-red-600 focus:ring-2 focus:ring-red-500/20 dark:border-red-500' => $hasError, 'border-zinc-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-600' => ! $hasError]) }}>

            @if($revealable && $type === 'password')
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-zinc-500 transition hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-300"
                    aria-label="Show password"
                    aria-pressed="false"
                    onclick="const input = this.previousElementSibling; const showing = input.type === 'text'; input.type = showing ? 'password' : 'text'; this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password'); this.setAttribute('aria-pressed', String(!showing)); this.querySelectorAll('svg').forEach((icon, index) => icon.classList.toggle('hidden', showing ? index === 1 : index === 0));"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.06 12.35a1 1 0 0 1 0-.7C3.64 7.6 7.57 5 12 5c4.43 0 8.36 2.6 9.94 6.65a1 1 0 0 1 0 .7C20.36 16.4 16.43 19 12 19c-4.43 0-8.36-2.6-9.94-6.65Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m2 2 20 20" />
                        <path d="M6.71 6.71C4.88 7.9 3.25 9.6 2.06 11.65a1 1 0 0 0 0 .7C3.64 16.4 7.57 19 12 19c1.48 0 2.9-.29 4.18-.82" />
                        <path d="M10.73 5.08C11.15 5.03 11.57 5 12 5c4.43 0 8.36 2.6 9.94 6.65a1 1 0 0 1 0 .7 13.8 13.8 0 0 1-1.67 2.68" />
                        <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88" />
                    </svg>
                </button>
            @endif
        </div>
    @endif
    @if($description)<span class="text-xs font-normal text-zinc-500">{{ $description }}</span>@endif
    @if($fieldName) @error($fieldName)<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror @endif
</label>
