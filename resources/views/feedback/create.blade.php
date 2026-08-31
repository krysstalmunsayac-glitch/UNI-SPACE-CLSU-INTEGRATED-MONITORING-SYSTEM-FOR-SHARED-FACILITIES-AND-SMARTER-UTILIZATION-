<x-layouts.home.header>
    <main class="min-h-[calc(100vh-5rem)] bg-gradient-to-b from-white to-emerald-50/50 px-4 py-12 dark:from-zinc-950 dark:to-emerald-950/20 sm:px-6">
        <div class="mx-auto max-w-2xl">
            <a href="{{ route('dashboard') }}#requests" class="text-sm font-bold text-emerald-700 hover:text-emerald-900 dark:text-emerald-300">← Back to requests</a>

            <section class="mt-5 overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-900">
                <div class="border-b border-emerald-900/10 bg-emerald-50 p-6 dark:border-white/10 dark:bg-emerald-950/30 sm:p-8">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Event completed</p>
                    <h1 class="mt-2 text-3xl font-black text-emerald-950 dark:text-white">Rate your facility</h1>
                    <p class="mt-2 text-emerald-900/70 dark:text-emerald-100/70">
                        Tell us about your experience at <strong>{{ $facilityRequest->facility?->Facility_Name }}</strong> for request #{{ $facilityRequest->RID }}.
                    </p>
                </div>

                <form action="{{ route('facility-feedback.store', $facilityRequest) }}" method="POST" class="space-y-6 p-6 sm:p-8">
                    @csrf

                    <fieldset>
                        <legend class="text-sm font-black text-emerald-950 dark:text-white">Facility rating</legend>
                        <div class="mt-3 flex flex-wrap gap-2" role="radiogroup" aria-label="Facility rating">
                            @foreach (range(1, 5) as $rating)
                                <label class="cursor-pointer">
                                    <input type="radio" name="Rating" value="{{ $rating }}" class="peer sr-only" required @checked((int) old('Rating') === $rating)>
                                    <span class="inline-flex h-11 min-w-14 items-center justify-center rounded-xl border border-amber-300 bg-white px-3 font-black text-amber-600 transition hover:bg-amber-50 peer-checked:border-amber-500 peer-checked:bg-amber-100 peer-focus:ring-2 peer-focus:ring-amber-500 dark:bg-zinc-950">{{ $rating }} ★</span>
                                </label>
                            @endforeach
                        </div>
                        @error('Rating') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </fieldset>

                    <label class="block" for="feedback-comment">
                        <span class="text-sm font-black text-emerald-950 dark:text-white">Comments <span class="font-normal text-emerald-900/60 dark:text-zinc-400">(optional)</span></span>
                        <textarea
                            id="feedback-comment"
                            name="Comment"
                            rows="5"
                            maxlength="1000"
                            placeholder="Share your experience with this facility..."
                            class="mt-3 w-full rounded-xl border border-emerald-900/15 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/15 dark:bg-zinc-950 dark:text-white"
                        >{{ old('Comment') }}</textarea>
                        @error('Comment') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </label>

                    <button type="submit" class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800 disabled:opacity-60" data-ui-confirm="Submit this facility rating? Feedback can only be submitted once." data-ui-confirm-title="Confirm feedback submission" data-ui-confirm-label="Submit feedback">
                        Submit facility rating
                    </button>
                </form>
            </section>
        </div>
    </main>
</x-layouts.home.header>
