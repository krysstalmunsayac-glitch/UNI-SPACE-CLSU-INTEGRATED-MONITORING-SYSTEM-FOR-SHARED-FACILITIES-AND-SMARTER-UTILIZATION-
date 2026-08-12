    <x-ui::modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">{{ $editingId ? 'Edit Amenity' : 'Add Amenity' }}</x-ui::heading>
                <x-ui::subheading>{{ $editingId ? 'Update this amenity.' : 'Create a new amenity.' }}</x-ui::subheading>
            </div>

            <x-ui::input wire:model="name" label="Amenity Name" placeholder="Enter amenity name" required minlength="2" maxlength="100" />

            <x-ui::textarea wire:model="Description" label="Description" placeholder="Enter amenity description" rows="3" maxlength="1000" />

            <div>
                <x-ui::input
                    wire:model="reservation_limit"
                    type="number"
                    min="1"
                    max="100000"
                    label="Concurrent usage limit"
                    description="Maximum overlapping reservations allowed. Leave blank for unlimited usage."
                    placeholder="Unlimited"
                />
            </div>

            <div>
                <x-ui::checkbox.group label="Facilities">
                    <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        @forelse($this->facilityOptions as $facility)
                            <x-ui::checkbox
                                wire:key="amenity-facility-{{ $facility->FID }}"
                                wire:model="facilityIds"
                                value="{{ $facility->FID }}"
                                label="{{ $facility->Facility_Name }}{{ $facility->Office ? ' ('.$facility->Office.')' : '' }}"
                            />
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No facilities available.</p>
                        @endforelse
                    </div>
                </x-ui::checkbox.group>
                @error('facilityIds') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                @error('facilityIds.*') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <x-ui::select wire:model="Status" label="Status">
                <x-ui::select.option value="Available">Available</x-ui::select.option>
                <x-ui::select.option value="Unavailable">Unavailable</x-ui::select.option>
            </x-ui::select>

            <div class="flex gap-2">
                <x-ui::button wire:click="save" variant="primary" class="flex-1">{{ $editingId ? 'Update' : 'Create' }}</x-ui::button>
                <x-ui::button wire:click="$set('showModal', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
            </div>
        </div>
    </x-ui::modal>
