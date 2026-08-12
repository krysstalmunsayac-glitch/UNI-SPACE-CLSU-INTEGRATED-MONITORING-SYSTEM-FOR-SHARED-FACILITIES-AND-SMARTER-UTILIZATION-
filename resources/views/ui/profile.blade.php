@props(['avatar' => null, 'name' => '', 'description' => '', 'initials' => null])
<button type="button" {{ $attributes->class('flex items-center gap-3 rounded-lg p-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700') }}>
    <x-ui::avatar :src="$avatar" :name="$name" :initials="$initials" size="sm" />
    <span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ $name }}</span><span class="block truncate text-xs text-zinc-500">{{ $description }}</span></span>
</button>
