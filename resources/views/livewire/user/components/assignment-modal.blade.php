    {{-- Facility assignment modal --}}
    <x-ui::modal
        wire:model.self="showAssignmentModal"
        class="md:w-[42rem]"
    >
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    Assign Facilities
                </x-ui::heading>

                <x-ui::subheading>
                    Select which facilities this Office Admin can manage.
                </x-ui::subheading>
            </div>

            <div class="grid max-h-80 gap-3 overflow-y-auto rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                @forelse ($this->availableFacilities as $facility)
                    <label wire:key="assign-facility-{{ $facility->FID }}" class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ $facility->Facility_Name }}
                        </span>

                        <x-ui::checkbox
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

            @error('assignedFacilityIds')
                <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
            @enderror
            @error('assignedFacilityIds.*')
                <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <x-ui::button
                    wire:click="saveAssignments"
                    data-ui-confirm="Apply these facility assignments? This will change which facilities this Office Admin can manage."
                    variant="primary"
                    class="flex-1"
                >
                    Save Assignments
                </x-ui::button>

                <x-ui::button
                    wire:click="$set('showAssignmentModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>
