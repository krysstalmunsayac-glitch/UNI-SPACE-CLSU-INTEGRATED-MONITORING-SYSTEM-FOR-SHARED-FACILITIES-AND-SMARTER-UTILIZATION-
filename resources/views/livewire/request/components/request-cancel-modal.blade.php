<x-ui::modal wire:model.self="showCancelModal" class="md:w-[34rem]">
    <div class="space-y-6">
        <div>
            <x-ui::heading size="lg">Cancel approved request</x-ui::heading>
            <x-ui::subheading>The reserved schedule will be released immediately.</x-ui::subheading>
        </div>

        <x-ui::textarea
            wire:model="adminCancellationReason"
            label="Reason for cancellation"
            rows="4"
            maxlength="500"
            placeholder="Explain why this approved request is being cancelled."
        />
        @error('adminCancellationReason')
            <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror

        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <input wire:model="emailCancellationNotice" type="checkbox" class="mt-0.5 size-4 rounded">
            <span>
                <span class="block text-sm font-bold text-zinc-900 dark:text-white">Email the requester</span>
                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">Send the cancellation status and reason to the requester’s email address.</span>
            </span>
        </label>

        <div class="flex gap-3">
            <x-ui::button wire:click="$set('showCancelModal', false)" variant="ghost" class="flex-1">Keep request</x-ui::button>
            <x-ui::button wire:click="confirmCancellation" variant="danger" class="flex-1">Cancel request</x-ui::button>
        </div>
    </div>
</x-ui::modal>
