<form method="GET" action="{{ url()->current() }}" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:flex-row sm:items-end">
    <label class="block flex-1">
        <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400">From</span>
        <input type="date" name="date_from" value="{{ $analyticsDateFrom }}" max="{{ $analyticsDateTo }}" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
    </label>
    <label class="block flex-1">
        <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400">To</span>
        <input type="date" name="date_to" value="{{ $analyticsDateTo }}" min="{{ $analyticsDateFrom }}" max="{{ today()->toDateString() }}" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
    </label>
    <button type="submit" class="h-10 rounded-lg bg-emerald-700 px-5 text-sm font-bold text-white transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950">Apply range</button>
    <a href="{{ url()->current() }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-bold text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-zinc-700 dark:text-zinc-300">Reset</a>
</form>
@error('date_from') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
@error('date_to') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
