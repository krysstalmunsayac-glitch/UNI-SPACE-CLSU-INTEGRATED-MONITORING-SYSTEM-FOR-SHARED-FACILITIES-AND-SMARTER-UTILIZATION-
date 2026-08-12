<div
    x-cloak
    x-show="open"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
    x-on:click.stop
    x-ref="menu"
    data-ui-dropdown-menu
    class="ui-dropdown-menu absolute right-0 top-full z-50 mt-2 max-w-[calc(100vw-2rem)] min-w-48 rounded-lg border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
    {{ $attributes }}
>
    {{ $slot }}
</div>
