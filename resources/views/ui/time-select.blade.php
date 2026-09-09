@props(['label' => null, 'options', 'name' => null, 'value' => null])
@php
    $model = $attributes->wire('model')->value();
    $live = $attributes->wire('model')->hasModifier('live');
    $labels = collect($options)->mapWithKeys(fn ($time) => [$time => \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A')]);
@endphp
<div
    class="relative grid min-w-0 gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200"
    x-data="{
        expanded: false,
        value: {{ $model ? '$wire.entangle('.\Illuminate\Support\Js::from($model).')'.($live ? '.live' : '') : \Illuminate\Support\Js::from($value) }},
        selectedLabel() {
            if (!this.value) return 'Select time';
            const [hour, minute] = this.value.split(':');
            if (Number(hour) === 24) return '12:' + minute + ' AM';
            return ((Number(hour) + 11) % 12 + 1) + ':' + minute + (Number(hour) >= 12 ? ' PM' : ' AM');
        },
        showOptions() {
            this.expanded = true;
            this.$nextTick(() => this.$refs.options.focus());
        },
        closeOptions() {
            this.expanded = false;
            this.$refs.trigger.focus();
        }
    }"
    x-on:click.outside="expanded = false"
    x-on:focusout="if (!$el.contains($event.relatedTarget)) expanded = false"
    x-on:keydown.escape.stop="closeOptions()"
>
    @if ($label)<span>{{ $label }}</span>@endif
    @if ($name)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}" x-bind:value="value ?? ''">
    @endif
    <button
        x-ref="trigger"
        type="button"
        {{ $attributes->only(['id', 'aria-labelledby']) }}
        aria-label="{{ $label }}"
        aria-haspopup="listbox"
        x-bind:aria-expanded="expanded"
        x-on:click="expanded ? closeOptions() : showOptions()"
        x-on:keydown.arrow-down.prevent="showOptions()"
        x-on:keydown.arrow-up.prevent="showOptions()"
        class="select-picker-trigger flex h-10 w-full items-center justify-between rounded-lg border border-zinc-300 bg-white px-3 text-left text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white"
    >
        <span x-text="selectedLabel()"></span>
    </button>
    <select
        x-ref="options"
        x-cloak
        x-show="expanded"
        x-model="value"
        size="6"
        aria-label="{{ $label }}"
        x-on:click="if ($event.target.tagName === 'OPTION') closeOptions()"
        x-on:keydown.enter.prevent="closeOptions()"
        class="absolute bottom-full left-0 z-50 mb-1 w-full overflow-y-auto rounded-lg border border-zinc-300 bg-white text-sm text-zinc-900 shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white"
        style="height: calc(12rem + 2px); padding: 0;"
    >
        @foreach ($labels as $optionValue => $text)
            <option value="{{ $optionValue }}" style="height: 2rem; padding: 0.375rem 0.75rem;">{{ $text }}</option>
        @endforeach
    </select>
    @if ($model || $name)
        @error($model ?: $name)<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
    @endif
</div>
