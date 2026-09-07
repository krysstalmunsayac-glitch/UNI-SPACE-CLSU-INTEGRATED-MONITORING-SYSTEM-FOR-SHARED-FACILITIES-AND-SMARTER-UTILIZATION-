@props(['selection', 'label'])
<div class="relative" x-data="{ expanded: false }"
    x-on:invalid.capture="expanded = true; $nextTick(() => $refs.options.focus())"
    x-on:click.outside="expanded = false"
    x-on:focusout="if (!$el.contains($event.relatedTarget)) expanded = false"
    x-on:keydown.escape.stop="expanded = false; $refs.trigger.focus()">
    <button type="button" x-ref="trigger" aria-label="{{ $label }}" aria-haspopup="listbox"
        x-bind:aria-expanded="expanded"
        x-on:click="expanded = !expanded; if (expanded) $nextTick(() => $refs.options.focus())"
        x-on:keydown.arrow-down.prevent="expanded = true; $nextTick(() => $refs.options.focus())"
        class="flex h-11 w-full items-center justify-between rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
        <span x-text="{{ $selection }} ? formatTime({{ $selection }}) : 'Select time'"></span>
        <x-ui::icon.chevron-down class="size-4 shrink-0" />
    </button>
    {{ $slot }}
</div>
