@props(['name'])

@php
    $paths = match ($name) {
        'ellipsis-horizontal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>',
        'pencil' => '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Zm0 0L19.5 7.125M18 14.25v4.125A1.875 1.875 0 0 1 16.125 20.25H5.625A1.875 1.875 0 0 1 3.75 18.375V7.875A1.875 1.875 0 0 1 5.625 6H9.75"/>',
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>',
        'power' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9m5.657-6.657a8 8 0 1 1-11.314 0"/>',
        'trash' => '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79 18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.477c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>',
        'arrow-uturn-left' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 6 6v3"/>',
        'eye' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.25"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 4.5 4.5 10.5-10.5"/>',
        'x-mark' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>',
        'archive-box' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M6 7v12h12V7M9 11h6M5 4h14v3H5z"/>',
        'building-office' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M6 21V4.5A1.5 1.5 0 0 1 7.5 3h6A1.5 1.5 0 0 1 15 4.5V21m0-12h2.25A1.75 1.75 0 0 1 19 10.75V21M9 7.5h3m-3 3h3m-3 3h3m-3 3h3"/>',
        'magnifying-glass' => '<circle cx="10.5" cy="10.5" r="6.75"/><path stroke-linecap="round" d="m15.5 15.5 5 5"/>',
        'arrow-down-tray' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'document', 'document-text', 'document-magnifying-glass' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h7.5L18 8.25v12H6v-16.5Z M13.5 3.75v4.5H18 M9 12h6m-6 3h6"/>',
        'table-cells' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v13.5H3.75V5.25Zm0 4.5h16.5m-11 0v9"/>',
        'paper-airplane' => '<path stroke-linecap="round" stroke-linejoin="round" d="m3.75 3.75 16.5 8.25-16.5 8.25 3-8.25-3-8.25Zm3 8.25h7.5"/>',
        'funnel' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7.5 8.25v6l-3 1.5v-7.5L3 4.5Z"/>',
        'user', 'profile' => '<circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/>',
        'cog', 'cog-6-tooth' => '<circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.16.38.37.72.6 1 .3.36.7.57 1.1.6h.1v4h-.1a1.7 1.7 0 0 0-1.7.4Z"/>',
        'appearance' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 9 9c0-1.1-.2-2.15-.56-3.12A7 7 0 0 1 12 3Z"/>',
        'arrow-right-start-on-rectangle', 'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="m10 17 5-5-5-5m5 5H3m11-9h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
        default => '<circle cx="12" cy="12" r="8"/><path stroke-linecap="round" d="M12 8v8m-4-4h8"/>',
    };
@endphp

<svg
    {{ $attributes->class('size-5 shrink-0') }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    aria-hidden="true"
>
    {!! $paths !!}
</svg>
