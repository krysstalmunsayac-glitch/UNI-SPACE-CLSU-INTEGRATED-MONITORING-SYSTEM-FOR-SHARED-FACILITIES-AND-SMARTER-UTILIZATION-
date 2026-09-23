                                <div x-show="step === 3" x-cloak class="grid gap-4 sm:grid-cols-2">


                                    <div class="sm:col-span-2">
                                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                            <label for="booking-capacity" class="text-sm font-bold text-emerald-950 dark:text-white">Expected Number of Attendees</label>
                                            @if ($facility->Capacity)
                                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200">Facility capacity: {{ number_format($facility->Capacity) }} people</span>
                                            @endif
                                        </div>
                                        <input id="booking-capacity" name="Capacity" type="number" min="1" max="{{ $facility->Capacity ?? 100000 }}" value="{{ old('Capacity') }}" placeholder="{{ $facility->Capacity ? 'Enter 1–'.number_format($facility->Capacity) : 'Enter expected attendees' }}" required class="h-14 w-full rounded-xl border border-emerald-900/15 bg-white px-4 font-semibold text-emerald-950 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                                        <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $facility->Capacity ? 'This facility can accommodate up to '.number_format($facility->Capacity).' attendees.' : 'The facility has no specified attendee limit. Enter the expected total.' }}</p>
                                        @error('Capacity') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2" x-data="{ requestLetterName: '' }">
                                        <span class="mb-2 block text-sm font-bold text-emerald-950 dark:text-white">Request letter <span class="font-normal text-zinc-500">(optional)</span></span>
                                        <label for="request-letter" class="flex min-h-20 cursor-pointer items-center gap-4 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/60 px-4 py-3 transition hover:border-emerald-600 hover:bg-emerald-50 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15 dark:border-emerald-800 dark:bg-emerald-950/20">
                                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-white text-emerald-700 shadow-sm dark:bg-zinc-900 dark:text-emerald-300" aria-hidden="true">
                                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4" /><path d="m7 9 5-5 5 5" /><path d="M5 20h14" /></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-bold text-emerald-950 dark:text-white" x-text="requestLetterName || 'Choose a PDF request letter'"></span>
                                                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">PDF only, maximum file size 5 MB</span>
                                            </span>
                                            <span class="shrink-0 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white">Browse</span>
                                            <input id="request-letter" type="file" name="attachment" accept=".pdf,application/pdf" class="sr-only" x-on:change="requestLetterName = $event.target.files?.[0]?.name || ''">
                                        </label>
                                        @error('attachment') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                </div>
