<x-ui::modal wire:model.self="showPaymentProofReplacementModal" class="md:w-[34rem]">
    <div class="space-y-5">
        <div>
            <x-ui::heading size="lg">Request a new payment proof</x-ui::heading>
            <x-ui::subheading>The current proof will be removed and the requester will be notified to upload a replacement.</x-ui::subheading>
        </div>

        <x-ui::textarea wire:model="paymentProofReplacementReason" label="Why is a replacement needed?" rows="4" maxlength="1000" placeholder="For example: the receipt image is blurry or the amount does not match." />

        <div class="flex gap-3">
            <x-ui::button wire:click="$set('showPaymentProofReplacementModal', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
            <x-ui::button wire:click="requestPaymentProofReplacement" variant="primary" class="flex-1">Request re-upload</x-ui::button>
        </div>
    </div>
</x-ui::modal>
