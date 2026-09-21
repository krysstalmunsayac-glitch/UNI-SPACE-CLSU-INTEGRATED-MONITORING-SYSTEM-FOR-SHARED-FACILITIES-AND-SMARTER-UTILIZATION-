@php($stats = $this->userStats)

<section aria-label="User summary" class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    @foreach ([
        ['label' => 'Total users', 'value' => $stats['total'], 'color' => 'text-emerald-700 dark:text-emerald-300', 'accent' => 'bg-emerald-500'],
        ['label' => 'Superadmins', 'value' => $stats['super_admins'], 'color' => 'text-violet-700 dark:text-violet-300', 'accent' => 'bg-violet-500'],
        ['label' => 'Office admins', 'value' => $stats['office_admins'], 'color' => 'text-blue-700 dark:text-blue-300', 'accent' => 'bg-blue-500'],
        ['label' => 'End users', 'value' => $stats['end_users'], 'color' => 'text-zinc-700 dark:text-zinc-200', 'accent' => 'bg-zinc-500'],
        ['label' => 'Inactive', 'value' => $stats['inactive'], 'color' => 'text-amber-700 dark:text-amber-300', 'accent' => 'bg-amber-500'],
    ] as $stat)
        <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white px-5 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
            <span class="absolute inset-y-0 left-0 w-1 {{ $stat['accent'] }}"></span>
            <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight {{ $stat['color'] }}">{{ number_format($stat['value']) }}</p>
        </div>
    @endforeach
</section>
