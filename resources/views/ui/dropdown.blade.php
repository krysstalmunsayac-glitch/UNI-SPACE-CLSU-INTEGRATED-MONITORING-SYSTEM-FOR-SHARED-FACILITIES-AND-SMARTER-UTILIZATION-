@props(['position' => 'bottom', 'align' => 'end'])
<div
    x-data="{
        open: false,
        dropdownId: 'dropdown-' + Math.random().toString(36).slice(2),
        positionMenu() {
            const menu = this.$refs.menu;
            if (!menu) return;

            const trigger = this.$el.getBoundingClientRect();
            const gap = 8;
            const margin = 12;
            const menuWidth = menu.offsetWidth;
            const menuHeight = menu.offsetHeight;
            const align = this.$el.dataset.align;
            const preferredPosition = this.$el.dataset.position;
            let left = align === 'start' ? trigger.left : trigger.right - menuWidth;
            let top = preferredPosition === 'top'
                ? trigger.top - menuHeight - gap
                : trigger.bottom + gap;

            if (top + menuHeight > window.innerHeight - margin) top = trigger.top - menuHeight - gap;
            if (top < margin) top = trigger.bottom + gap;

            left = Math.max(margin, Math.min(left, window.innerWidth - menuWidth - margin));
            menu.style.position = 'fixed';
            menu.style.left = `${left}px`;
            menu.style.right = 'auto';
            menu.style.top = `${top}px`;
            menu.style.bottom = 'auto';
        }
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window="if (open) positionMenu()"
    x-on:scroll.window="open = false"
    x-on:ui-dropdown-opened.window="if ($event.detail !== dropdownId) open = false"
    data-ui-dropdown
    data-position="{{ $position }}"
    data-align="{{ $align }}"
    {{ $attributes->except(['position', 'align'])->class('relative inline-block align-middle') }}
>
    <div x-on:click="
        if (!$event.target.closest('[data-ui-dropdown-menu]')) {
            open = !open;
            if (open) {
                window.dispatchEvent(new CustomEvent('ui-dropdown-opened', { detail: dropdownId }));
                $nextTick(() => positionMenu());
            }
        }
    ">{{ $slot }}</div>
</div>
