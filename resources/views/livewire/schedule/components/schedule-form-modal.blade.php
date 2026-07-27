    {{-- ===== CREATE / EDIT MODAL ===== --}}
    <flux:modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Schedule' : 'Add Schedule' }}
                </flux:heading>

                <flux:subheading>
                    {{ $editingId ? "Update this booking's details." : 'Create a new facility booking.' }}
                </flux:subheading>
            </div>

            <div>
                <flux:select wire:model="Request_ID" label="Request" placeholder="Select a request">
                    @foreach ($this->requestsList as $request)
                        <flux:select.option value="{{ $request->RID }}">
                            #{{ $request->RID }}
                            —
                            {{ $request->facility?->Facility_Name ?? 'Facility N/A' }}
                            ({{ $request->Purpose ?? 'No purpose' }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @error('Request_ID')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <flux:input
                    wire:model="Date"
                    type="date"
                    label="Date"
                />

                @error('Date')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:input
                        wire:model="Start_Time"
                        type="time"
                        label="Start Time"
                    />

                    @error('Start_Time')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <flux:input
                        wire:model="End_Time"
                        type="time"
                        label="End Time"
                    />

                    @error('End_Time')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div>
                <flux:select wire:model="Status" label="Status">
                    <flux:select.option value="Booked">
                        Booked
                    </flux:select.option>

                    <flux:select.option value="Blocked">
                        Blocked
                    </flux:select.option>
                </flux:select>

                @error('Status')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex gap-2">
                <flux:button
                    wire:click="save"
                    variant="primary"
                    class="flex-1"
                >
                    {{ $editingId ? 'Update' : 'Create' }}
                </flux:button>

                @if ($editingId)
                    <flux:button
                        wire:click="delete({{ $editingId }})"
                        wire:confirm="Are you sure you want to delete this schedule?"
                        variant="danger"
                    >
                        Delete
                    </flux:button>
                @endif

                <flux:button
                    wire:click="$set('showModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
