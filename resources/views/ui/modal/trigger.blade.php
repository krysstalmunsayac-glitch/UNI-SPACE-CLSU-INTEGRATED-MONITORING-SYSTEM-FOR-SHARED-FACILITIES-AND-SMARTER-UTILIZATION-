@props(['name'])
<span x-data x-on:click="$dispatch('ui-modal-show', { name: @js($name) })">{{ $slot }}</span>
