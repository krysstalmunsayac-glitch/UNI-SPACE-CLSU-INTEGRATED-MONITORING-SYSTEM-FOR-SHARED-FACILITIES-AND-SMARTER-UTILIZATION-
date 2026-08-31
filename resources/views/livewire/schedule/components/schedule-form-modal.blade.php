    {{-- ===== EDIT MODAL ===== --}}
    <x-ui::modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    Edit Schedule
                </x-ui::heading>

                <x-ui::subheading>
                    Update this booking's details.
                </x-ui::subheading>
            </div>

            <div>
                <p class="mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">Request</p>
                @php($originalRequest = $this->requestsList->firstWhere('RID', $Request_ID))
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    #{{ $Request_ID }}
                    — {{ $originalRequest?->facility?->Facility_Name ?? '—' }}
                    ({{ $originalRequest?->Purpose ?? 'No purpose' }})
                </div>
            </div>

            <div>
                <x-ui::input
                    wire:model="Date"
                    type="date"
                    label="Date"
                    min="{{ now()->toDateString() }}"
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
                    Update
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
