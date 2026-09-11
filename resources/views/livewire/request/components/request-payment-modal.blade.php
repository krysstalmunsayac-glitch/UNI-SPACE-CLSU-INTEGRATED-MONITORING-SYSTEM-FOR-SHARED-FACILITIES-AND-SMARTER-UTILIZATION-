<x-ui::modal wire:model.self="showPaymentModal" class="md:w-[32rem]">
    <div class="space-y-5">
        <div>
            <x-ui::heading size="lg">Request payment</x-ui::heading>
            <x-ui::subheading>The requester will receive these instructions by email.</x-ui::subheading>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            Payment method: <strong>Cash at the Admin Cashier</strong>. The requester will upload the official receipt from their dashboard.
        </div>

        <x-ui::input wire:model="paymentAmount" type="number" min="0.01" step="0.01" label="Amount due (PHP)" prefix="₱" />
        @error('paymentAmount') <p class="text-sm font-semibold text-red-600">{{ $message }}</p> @enderror

        <x-ui::input wire:model="paymentDeadline" type="datetime-local" label="Payment deadline" />
        @error('paymentDeadline') <p class="text-sm font-semibold text-red-600">{{ $message }}</p> @enderror

        <div class="flex gap-3">
            <x-ui::button wire:click="$set('showPaymentModal', false)" variant="ghost" class="flex-1">Go back</x-ui::button>
            <x-ui::button wire:click="requestPayment" wire:loading.attr="disabled" wire:target="requestPayment" class="flex-1">
                <span wire:loading.remove wire:target="requestPayment">Send instructions</span>
                <span wire:loading wire:target="requestPayment">Sending...</span>
            </x-ui::button>
        </div>
    </div>
</x-ui::modal>
