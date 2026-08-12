@props(['vertical' => false])
@if($vertical)
    <span aria-hidden="true" {{ $attributes->class('block w-px self-stretch bg-zinc-200 dark:bg-zinc-700') }}></span>
@else
    <hr {{ $attributes->class('border-0 border-t border-zinc-200 dark:border-zinc-700') }}>
@endif
