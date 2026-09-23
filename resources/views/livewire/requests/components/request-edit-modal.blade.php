    {{-- Edit Modal --}}
    <x-ui::modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">Edit Request #{{ $editingId }}</x-ui::heading>
                <x-ui::subheading>Update the details of this request.</x-ui::subheading>
            </div>

            <x-ui::select wire:model="form.User_ID" label="User">
                <x-ui::select.option value="">Select a user</x-ui::select.option>
                @foreach ($this->users as $user)
                    <x-ui::select.option value="{{ $user->id }}">{{ $user->name }}</x-ui::select.option>
                @endforeach
            </x-ui::select>

            <x-ui::input wire:model="form.Proposed_Date" type="date" label="Proposed Date" />
            <x-ui::input wire:model="form.Proposed_End_Date" type="date" label="Proposed End Date" />

            <x-ui::input wire:model="form.Proposed_Start_Time" type="time" label="Start Time" />

            <x-ui::input wire:model="form.Proposed_End_Time" type="time" label="End Time" />

            <fieldset class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-900 dark:bg-emerald-950/20">
                <legend class="px-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Purpose of Request</legend>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Select every category that applies.</p>

                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (\App\Models\FacilityRequest::PURPOSE_OPTIONS as $purposeOption)
                        <label class="flex items-start gap-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                            <input
                                type="checkbox"
                                value="{{ $purposeOption }}"
                                wire:model="Purpose_Categories"
                                class="mt-0.5 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-600"
                            >
                            <span>{{ $purposeOption }}</span>
                        </label>
                    @endforeach
                </div>

                @error('Purpose_Categories')
                    <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror

                <x-ui::input
                    wire:model="Other_Purpose"
                    label="Other purpose"
                    placeholder="Complete this only when Other is selected"
                />
                @error('Other_Purpose')
                    <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-ui::textarea wire:model="form.Request_Details" label="Event Description" placeholder="Describe the event, activity, setup, or other important details" rows="4" />

            <x-ui::input wire:model="form.Capacity" type="number" min="1" label="Expected Attendees" />

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

            <x-ui::select wire:model="form.Status" label="Status">
                @foreach (array_unique([$form->Status, ...\App\Models\FacilityRequest::allowedTransitionsFrom($form->Status)]) as $statusOption)
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

