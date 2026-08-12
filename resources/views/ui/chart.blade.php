@props(['class' => null])

<div {{ $attributes->class('space-y-6 ' . $class) }}>
    {{ $slot }}
</div>
