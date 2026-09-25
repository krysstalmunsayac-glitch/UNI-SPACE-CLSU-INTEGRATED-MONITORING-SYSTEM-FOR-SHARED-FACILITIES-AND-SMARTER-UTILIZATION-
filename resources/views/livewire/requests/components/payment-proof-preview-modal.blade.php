<x-ui::modal wire:model.self="showPaymentProofPreview" style="width: min(96vw, 1280px); height: min(88vh, 900px); max-width: none; max-height: none; overflow: hidden; padding: 0;">
    <div class="flex h-full min-h-0 flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <div>
                <x-ui::heading size="lg">{{ $documentPreviewTitle }}</x-ui::heading>
                <x-ui::subheading>Review the submitted document before taking action.</x-ui::subheading>
            </div>
            <x-ui::button variant="ghost" icon="x-mark" wire:click="$set('showPaymentProofPreview', false)" aria-label="Close payment proof preview" />
        </div>

        <div class="min-h-0 flex-1 bg-zinc-100 p-2 dark:bg-zinc-950 sm:p-3">
            <iframe
                src="{{ $paymentProofPreviewUrl }}"
                title="Payment proof preview"
                class="h-full w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
            ></iframe>
        </div>
    </div>
</x-ui::modal>
