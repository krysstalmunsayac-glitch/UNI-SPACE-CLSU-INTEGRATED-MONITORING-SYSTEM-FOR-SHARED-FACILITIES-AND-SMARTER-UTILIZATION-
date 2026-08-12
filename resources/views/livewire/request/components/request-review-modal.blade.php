<x-ui::modal wire:model.self="showReviewModal" class="md:w-[38rem]">
    <div class="space-y-6">
        <div>
            <x-ui::heading size="lg">Review Request #{{ $reviewingId }}</x-ui::heading>
            <x-ui::subheading>Request additional information without rejecting this request.</x-ui::subheading>
        </div>

        <div class="grid gap-3 rounded-2xl border border-emerald-900/10 bg-emerald-50 p-4 text-sm dark:border-white/10 dark:bg-zinc-900 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Requester</p>
                <p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $Requester_Name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Facility</p>
                <p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $Facility_Name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Date and time</p>
                <p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $Proposed_Date }} · {{ $Proposed_Start_Time }}–{{ $Proposed_End_Time }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Expected attendees</p>
                <p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $Capacity ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Event title</p>
                <p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $Event_Title ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Event type</p>
                <p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $Event_Type ?: '—' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Purpose</p>
                <p class="mt-1 whitespace-pre-wrap font-medium text-emerald-950 dark:text-white">{{ $Purpose ?: '—' }}</p>
            </div>
        </div>

        <div>
            <x-ui::textarea
                wire:model="reviewNotes"
                label="What information is missing?"
                rows="5"
                placeholder="Be specific about what the requester needs to add or correct. This exact message will be included in the email."
            />
            <p class="mt-2 text-xs text-emerald-900/60 dark:text-zinc-400">The user will edit and resubmit this same request—no new request is required.</p>
        </div>

        <div class="flex gap-2">
            <x-ui::button wire:click="requestRevision" variant="primary" icon="paper-airplane" class="flex-1">
                Send review message
            </x-ui::button>
            <x-ui::button wire:click="$set('showReviewModal', false)" variant="ghost" class="flex-1">
                Cancel
            </x-ui::button>
        </div>
    </div>
</x-ui::modal>
