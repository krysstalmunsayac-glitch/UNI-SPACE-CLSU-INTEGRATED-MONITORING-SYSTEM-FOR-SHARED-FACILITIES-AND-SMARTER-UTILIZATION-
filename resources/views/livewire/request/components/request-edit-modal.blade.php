    {{-- Edit Modal --}}
    <x-ui::modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">Edit Request #{{ $editingId }}</x-ui::heading>
                <x-ui::subheading>Update the details of this request.</x-ui::subheading>
            </div>

            <x-ui::select wire:model="User_ID" label="User">
                <x-ui::select.option value="">Select a user</x-ui::select.option>
                @foreach ($this->users as $user)
                    <x-ui::select.option value="{{ $user->id }}">{{ $user->name }}</x-ui::select.option>
                @endforeach
            </x-ui::select>

            <x-ui::input wire:model="Proposed_Date" type="date" label="Proposed Date" />
            <x-ui::input wire:model="Proposed_End_Date" type="date" label="Proposed End Date" />

            <x-ui::input wire:model="Proposed_Start_Time" type="time" label="Start Time" />

            <x-ui::input wire:model="Proposed_End_Time" type="time" label="End Time" />

            <x-ui::textarea wire:model="Purpose" label="Purpose" placeholder="Enter the purpose of the request" rows="3" />

            <x-ui::input wire:model="Capacity" type="number" min="1" label="Expected Attendees" />

            @if ($attachmentPath)
                <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Attachment</span>
                    <x-ui::button
                        size="sm"
                        variant="ghost"
                        icon="arrow-down-tray"
                        href="{{ route('requests.attachment.download', $editingId) }}"
                    >
                        Download
                    </x-ui::button>
                </div>
            @endif

            <x-ui::select wire:model="Status" label="Status">
                @foreach (array_unique([$Status, ...\App\Models\Requests::allowedTransitionsFrom($Status)]) as $statusOption)
                    <x-ui::select.option value="{{ $statusOption }}">{{ $statusOption }}</x-ui::select.option>
                @endforeach
            </x-ui::select>

            <div class="flex gap-2">
                <x-ui::button wire:click="save" variant="primary" class="flex-1">
                    Update
                </x-ui::button>
                <x-ui::button wire:click="$set('showModal', false)" variant="ghost" class="flex-1">
                    Cancel
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>
