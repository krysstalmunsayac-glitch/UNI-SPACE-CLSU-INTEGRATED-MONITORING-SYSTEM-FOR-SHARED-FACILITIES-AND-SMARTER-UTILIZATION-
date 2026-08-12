@props(['paginate' => null])
<div
    data-ui-table
    wire:loading.class.delay="pointer-events-none opacity-60"
    {{ $attributes->class('ui-table-scroll transition-opacity duration-150') }}
>
    <table class="w-full min-w-[48rem] text-left text-sm">{{ $slot }}</table>
</div>
@if($paginate)
    <div class="mt-4" wire:loading.class.delay="pointer-events-none opacity-60">
        {{ $paginate->links(data: ['scrollTo' => false]) }}
    </div>
@endif
