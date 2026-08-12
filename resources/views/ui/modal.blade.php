@props(['name' => null, 'show' => false])
@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel?->value();
    $cleanAttributes = $attributes->whereDoesntStartWith('wire:model');
@endphp
<div
    data-ui-modal
    x-data="{ open: {{ $model ? '$wire.entangle('.\Illuminate\Support\Js::from($model).')' : \Illuminate\Support\Js::from((bool) $show) }} }"
    @if($name)
        x-on:ui-modal-show.window="if ($event.detail.name === @js($name)) open = true"
        x-on:ui-modal-close.window="if (!$event.detail.name || $event.detail.name === @js($name)) open = false"
    @endif
    x-on:local-modal-close="open = false"
    x-on:keydown.escape.window="open = false"
    x-effect="if (open) $nextTick(() => $refs.dialogPanel?.scrollTo({ top: 0, behavior: 'auto' }))"
    x-cloak
    x-show="open"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    role="dialog"
    aria-modal="true"
>
    <section x-ref="dialogPanel" x-on:click.outside="open = false" {{ $cleanAttributes->class('max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white p-5 shadow-2xl dark:bg-zinc-800') }}>
        {{ $slot }}
    </section>
</div>
