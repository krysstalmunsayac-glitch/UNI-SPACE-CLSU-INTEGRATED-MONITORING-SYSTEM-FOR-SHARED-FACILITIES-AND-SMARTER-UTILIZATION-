    {{-- ===== CREATE / EDIT MODAL ===== --}}
    <x-ui::modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    {{ $editingId ? 'Edit Schedule' : 'Add Schedule' }}
                </x-ui::heading>

                <x-ui::subheading>
                    {{ $editingId ? "Update this booking's details." : 'Create a new facility booking.' }}
                </x-ui::subheading>
            </div>

            <div>
                <x-ui::select wire:model="Request_ID" label="Request" placeholder="Select a request">
                    @foreach ($this->requestsList as $request)
                        <x-ui::select.option value="{{ $request->RID }}">
                            #{{ $request->RID }}
                            —
                            {{ $request->facility?->Facility_Name ?? '—' }}
                            ({{ $request->Purpose ?? 'No purpose' }})
                        </x-ui::select.option>
                    @endforeach
                </x-ui::select>

            </div>

            <div>
                <x-ui::input
                    wire:model="Date"
                    type="date"
                    label="Date"
                />

            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-ui::input
                        wire:model="Start_Time"
                        type="time"
                        label="Start Time"
                    />

                </div>

                <div>
                    <x-ui::input
                        wire:model="End_Time"
                        type="time"
                        label="End Time"
                    />

                </div>
            </div>

            <div>
                <x-ui::select wire:model="Status" label="Status">
                    <x-ui::select.option value="Booked">
                        Booked
                    </x-ui::select.option>

                    <x-ui::select.option value="Blocked">
                        Blocked
                    </x-ui::select.option>
                </x-ui::select>

            </div>

            <div class="flex gap-2">
                <x-ui::button
                    wire:click="save"
                    variant="primary"
                    class="flex-1"
                >
                    {{ $editingId ? 'Update' : 'Create' }}
                </x-ui::button>

                @if ($editingId)
                    <x-ui::button
                        wire:click="delete({{ $editingId }})"
                        data-ui-confirm="Are you sure you want to delete this schedule?"
                        variant="danger"
                    >
                        Delete
                    </x-ui::button>
                @endif

                <x-ui::button
                    wire:click="$set('showModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>
