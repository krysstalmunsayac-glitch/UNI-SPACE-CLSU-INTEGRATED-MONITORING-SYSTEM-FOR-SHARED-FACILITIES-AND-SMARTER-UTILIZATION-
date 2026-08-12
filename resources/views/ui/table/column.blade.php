@props([
    'sortable' => false,
    'sorted' => false,
    'direction' => 'asc',
])

<th
    scope="col"
    @if ($sortable)
        aria-sort="{{ $sorted ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none' }}"
    @endif
    {{ $attributes->except(['sortable', 'sorted', 'direction'])->class('px-4 py-3 font-semibold leading-5') }}
>
    @if ($sortable)
        <button type="button" class="group inline-flex items-center gap-1.5 whitespace-nowrap text-left transition hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600/30 dark:hover:text-emerald-300">
            <span>{{ $slot }}</span>
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
                @class([
                    'size-3.5 shrink-0 transition',
                    'text-emerald-700 dark:text-emerald-300' => $sorted,
                    'text-zinc-400 group-hover:text-emerald-600' => ! $sorted,
                    'rotate-180' => $sorted && $direction === 'desc',
                ])
            >
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
