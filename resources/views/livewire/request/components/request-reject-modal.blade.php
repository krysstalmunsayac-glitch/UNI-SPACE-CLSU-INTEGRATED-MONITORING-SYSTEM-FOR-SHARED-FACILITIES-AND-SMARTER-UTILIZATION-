<flux:modal wire:model.self="showRejectModal" class="md:w-[32rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Reject Request</flux:heading>
            <flux:subheading>Select at least one reason before rejecting this request.</flux:subheading>
        </div>

        <flux:checkbox.group wire:model.live="rejectionReasons" label="Reason for rejection">
            @foreach ([
                'Schedule conflict',
                'Facility unavailable',
                'Incomplete request information',
                'Capacity exceeds facility limit',
                'Does not meet facility policies',
                'Other',
            ] as $reason)
                <flux:checkbox value="{{ $reason }}" label="{{ $reason }}" />
            @endforeach
        </flux:checkbox.group>
        @error('rejectionReasons') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('rejectionReasons.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        @if (in_array('Other', $rejectionReasons, true))
            <flux:textarea wire:model="otherRejectionReason" label="Please specify the other reason" rows="3" placeholder="Type the reason for rejecting this request..." />
            @error('otherRejectionReason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @endif

        <div class="flex gap-2">
            <flux:button wire:click="reject" variant="danger" class="flex-1">Reject request</flux:button>
            <flux:button wire:click="$set('showRejectModal', false)" variant="ghost" class="flex-1">Cancel</flux:button>
        </div>
    </div>
</flux:modal>
