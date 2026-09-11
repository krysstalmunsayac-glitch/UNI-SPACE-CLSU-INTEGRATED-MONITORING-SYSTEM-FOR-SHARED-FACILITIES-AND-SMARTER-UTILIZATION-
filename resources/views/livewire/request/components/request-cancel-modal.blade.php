<x-ui::modal wire:model.self="showCancelModal" class="md:w-[34rem]">
    <div class="space-y-6">
        <div class="flex items-start gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.84 2.4 17.5A1.75 1.75 0 0 0 3.92 20h16.16a1.75 1.75 0 0 0 1.52-2.5L13.7 3.84a1.96 1.96 0 0 0-3.4 0Z" />
                </svg>
            </span>
            <div>
                <x-ui::heading size="lg">Cancel this approved request?</x-ui::heading>
                <x-ui::subheading>Please review the impact before continuing.</x-ui::subheading>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-bold">What happens next</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>The reserved date and time will become available immediately.</li>
                <li>This request will be marked as cancelled and cannot be approved again.</li>
            </ul>
        </div>

        <div class="space-y-2">
            <x-ui::textarea
                wire:model.live="adminCancellationReason"
                label="Why are you cancelling this request?"
                rows="4"
                minlength="5"
                maxlength="500"
                placeholder="Example: The facility will be closed for scheduled maintenance."
            />
            <div class="flex items-start justify-between gap-4 text-xs">
                <p class="text-zinc-500 dark:text-zinc-400">Give the requester a clear reason (at least 5 characters).</p>
                <p class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ mb_strlen($adminCancellationReason) }}/500</p>
            </div>
            @error('adminCancellationReason')
                <p class="text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50">
            <input wire:model="emailCancellationNotice" type="checkbox" class="mt-0.5 size-4 rounded">
            <span>
                <span class="block text-sm font-bold text-zinc-900 dark:text-white">Notify the requester by email</span>
                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">Include the cancellation status and the reason entered above.</span>
            </span>
        </label>

        <div class="flex flex-col-reverse gap-3 sm:flex-row">
            <x-ui::button wire:click="$set('showCancelModal', false)" wire:loading.attr="disabled" wire:target="confirmCancellation" variant="ghost" class="flex-1">
                Go back
            </x-ui::button>
            <x-ui::button wire:click="confirmCancellation" wire:loading.attr="disabled" wire:target="confirmCancellation" variant="danger" class="flex-1">
                <span wire:loading.remove wire:target="confirmCancellation">Yes, cancel request</span>
                <span wire:loading wire:target="confirmCancellation">Cancelling...</span>
            </x-ui::button>
        </div>
    </div>
</x-ui::modal>
