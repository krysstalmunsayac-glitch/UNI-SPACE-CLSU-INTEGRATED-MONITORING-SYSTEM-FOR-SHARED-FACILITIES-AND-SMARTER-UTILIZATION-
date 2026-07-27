    {{-- Edit Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Request #{{ $editingId }}</flux:heading>
                <flux:subheading>Update the details of this request.</flux:subheading>
            </div>

            <flux:select wire:model="User_ID" label="User">
                <flux:select.option value="">Select a user</flux:select.option>
                @foreach ($this->users as $user)
                    <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('User_ID') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <flux:input wire:model="Proposed_Date" type="date" label="Proposed Date" />
            @error('Proposed_Date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <flux:input wire:model="Proposed_Start_Time" type="time" label="Start Time" />
            @error('Proposed_Start_Time') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <flux:input wire:model="Proposed_End_Time" type="time" label="End Time" />
            @error('Proposed_End_Time') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <flux:textarea wire:model="Purpose" label="Purpose" placeholder="Enter the purpose of the request" rows="3" />
            @error('Purpose') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <flux:input wire:model="Capacity" type="number" min="1" label="Expected Attendees" />
            @error('Capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            @if ($attachmentPath)
                <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Attachment</span>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="arrow-down-tray"
                        wire:click="downloadAttachment({{ $editingId }})"
                    >
                        Download
                    </flux:button>
                </div>
            @endif

            <flux:select wire:model="Status" label="Status">
                <flux:select.option value="Pending">Pending</flux:select.option>
                <flux:select.option value="Approved">Approved</flux:select.option>
                <flux:select.option value="Rejected">Rejected</flux:select.option>
                <flux:select.option value="Cancelled">Cancelled</flux:select.option>
            </flux:select>
            @error('Status') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <div class="flex gap-2">
                <flux:button wire:click="save" variant="primary" class="flex-1">
                    Update
                </flux:button>
                <flux:button wire:click="$set('showModal', false)" variant="ghost" class="flex-1">
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
