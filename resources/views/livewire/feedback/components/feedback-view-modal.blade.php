<x-ui::modal wire:model.self="showViewModal" class="w-[95vw] max-w-3xl">
    @if ($this->viewingFeedback)
        @php
            $feedback = $this->viewingFeedback;
            $facility = $feedback->facility ?? $feedback->request?->facility;
            $answers = [
                'Reservation frequency' => $feedback->Reservation_Frequency,
                'Purpose importance' => $feedback->Purpose_Importance,
                'Requirements met' => $feedback->Requirements_Met,
                'Would reserve again' => $feedback->Reserve_Again,
            ];
        @endphp

        <div class="space-y-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                    FDB-{{ str_pad((string) $feedback->getKey(), 5, '0', STR_PAD_LEFT) }}
                </p>
                <x-ui::heading size="lg">Feedback details</x-ui::heading>
                <x-ui::subheading>Review the complete facility evaluation submitted by the user.</x-ui::subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs font-bold text-zinc-500">Submitted by</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $feedback->user?->name ?? 'Anonymous' }}</p>
                    @if ($feedback->user?->email)
                        <p class="mt-1 break-all text-xs text-zinc-500">{{ $feedback->user->email }}</p>
                    @endif
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs font-bold text-zinc-500">Facility</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $facility?->Facility_Name ?? '—' }}</p>
                    @if ($facility?->Office)
                        <p class="mt-1 text-xs text-zinc-500">{{ $facility->Office }}</p>
                    @endif
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs font-bold text-zinc-500">Request and date</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-white">Request #{{ $feedback->Request_ID ?? '—' }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $feedback->Created_at?->format('M d, Y h:i A') ?? '—' }}</p>
                </div>
            </div>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-950/20">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Facility rating</p>
                <div class="mt-2 flex items-center gap-3">
                    <span class="text-2xl tracking-wide text-amber-500" aria-label="{{ $feedback->Rating ?? 0 }} out of 5 stars">
                        {{ str_repeat('★', $feedback->Rating ?? 0) }}<span class="text-zinc-300 dark:text-zinc-700">{{ str_repeat('★', 5 - ($feedback->Rating ?? 0)) }}</span>
                    </span>
                    <span class="font-bold text-zinc-900 dark:text-white">{{ $feedback->Rating ?? 0 }}/5</span>
                </div>
            </section>

            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ($answers as $label => $answer)
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <dt class="text-xs font-bold text-zinc-500">{{ $label }}</dt>
                        <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $answer ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            <section>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Comments</h3>
                <p class="mt-2 whitespace-pre-line rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm leading-6 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $feedback->Comment ?: 'No comment provided.' }}</p>
            </section>

            <div class="flex justify-end">
                <x-ui::button type="button" wire:click="closeView" variant="primary">Close</x-ui::button>
            </div>
        </div>
    @endif
</x-ui::modal>
