    <flux:modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Amenity' : 'Add Amenity' }}</flux:heading>
                <flux:subheading>{{ $editingId ? 'Update this amenity.' : 'Create a new amenity.' }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Amenity Name" placeholder="Enter amenity name" />
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <flux:textarea wire:model="Description" label="Description" placeholder="Enter amenity description" rows="3" />
            @error('Description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-gray-200 mb-2">Facilities</label>
                <select
                    wire:model="facilityIds"
                    multiple
                    class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                >
                    @foreach($this->facilityOptions as $facility)
                        <option value="{{ $facility->FID }}">
                            {{ $facility->Facility_Name }} @if($facility->Office) ({{ $facility->Office }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('facilityIds') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <flux:select wire:model="Status" label="Status">
                <flux:select.option value="Available">Available</flux:select.option>
                <flux:select.option value="Unavailable">Unavailable</flux:select.option>
            </flux:select>
            @error('Status') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <div class="flex gap-2">
                <flux:button wire:click="save" variant="primary" class="flex-1">{{ $editingId ? 'Update' : 'Create' }}</flux:button>
                <flux:button wire:click="$set('showModal', false)" variant="ghost" class="flex-1">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
