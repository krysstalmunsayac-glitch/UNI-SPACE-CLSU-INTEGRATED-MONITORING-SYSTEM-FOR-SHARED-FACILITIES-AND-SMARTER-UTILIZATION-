    {{-- Facility assignment modal --}}
    <flux:modal
        wire:model.self="showAssignmentModal"
        class="md:w-[42rem]"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Assign Facilities
                </flux:heading>

                <flux:subheading>
                    Select which facilities this Office Admin can manage.
                </flux:subheading>
            </div>

            <div class="grid max-h-80 gap-3 overflow-y-auto rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                @forelse ($this->availableFacilities as $facility)
                    <label class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ $facility->Facility_Name }}
                        </span>

                        <flux:checkbox
                            wire:model="assignedFacilityIds"
                            :value="$facility->FID"
                        />
                    </label>
                @empty
                    <p class="py-5 text-center text-sm text-zinc-500">
                        No facilities are available.
                    </p>
                @endforelse
            </div>

            <div class="flex gap-2">
                <flux:button
                    wire:click="saveAssignments"
                    wire:confirm="Apply these facility assignments? This will change which facilities this Office Admin can manage."
                    variant="primary"
                    class="flex-1"
                >
                    Save Assignments
                </flux:button>

                <flux:button
                    wire:click="$set('showAssignmentModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
