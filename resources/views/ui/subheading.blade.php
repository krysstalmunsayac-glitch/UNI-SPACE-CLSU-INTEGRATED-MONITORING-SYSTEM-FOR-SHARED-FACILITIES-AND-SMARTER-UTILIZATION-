@props(['size' => 'base'])
<p {{ $attributes->class(($size === 'lg' ? 'text-base' : 'text-sm').' text-zinc-600 dark:text-zinc-400') }}>{{ $slot }}</p>
