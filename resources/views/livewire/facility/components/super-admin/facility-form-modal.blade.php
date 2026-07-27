<flux:modal wire:model.self="showModal" class="md:w-[28rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $editingId ? 'Edit Facility' : 'Add Facility' }}
            </flux:heading>
            <flux:subheading>
                {{ $editingId ? 'Update this facility\'s details.' : 'Create a new facility.' }}
            </flux:subheading>
        </div>

        <div>
            <flux:input wire:model="Facility_Name" label="Facility Name" placeholder="Enter facility name" />
            @error('Facility_Name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <flux:input wire:model="Price" type="number" step="0.01" label="Price" placeholder="0.00" />
            @error('Price') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <flux:input wire:model="Capacity" type="number" label="Capacity" placeholder="Enter capacity" />
            @error('Capacity') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <flux:input wire:model="Location" label="Location" placeholder="Enter location" />
            @error('Location') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        @if (auth()->user()?->isSuperAdmin())
            <div
                x-data="facilityLocationPicker($wire)"
                x-effect="$wire.showModal && openPicker()"
                class="space-y-3"
            >
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Exact map pin</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Search the saved location, then click or drag the marker to the exact building.</p>
                </div>
                <button
                    type="button"
                    x-on:click="findLocation()"
                    x-bind:disabled="searching"
                    class="shrink-0 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-800 disabled:opacity-50"
                >
                    <span x-text="searching ? 'Finding...' : 'Find location'"></span>
                </button>
            </div>

            <div x-ref="map" class="h-64 w-full overflow-hidden rounded-xl border border-emerald-900/10 dark:border-white/10"></div>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                    Latitude
                    <input wire:model="Latitude" readonly class="mt-1 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="Click the map">
                </label>
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                    Longitude
                    <input wire:model="Longitude" readonly class="mt-1 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="Click the map">
                </label>
            </div>
            <button type="button" x-on:click="clearPin()" class="text-xs font-bold text-red-600 hover:text-red-700">Clear exact pin</button>
            @error('Latitude') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            @error('Longitude') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
        @endif

        <div>
            <flux:select wire:model="facility_type" label="Facility type">
                <flux:select.option value="">Select type</flux:select.option>
                <flux:select.option value="sports">Sports</flux:select.option>
                <flux:select.option value="conference">Conference</flux:select.option>
                <flux:select.option value="auditorium">Auditorium</flux:select.option>
                <flux:select.option value="classroom">Classroom</flux:select.option>
                <flux:select.option value="laboratory">Laboratory</flux:select.option>
                <flux:select.option value="other">Other</flux:select.option>
            </flux:select>
            @error('facility_type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <flux:input wire:model="Office" label="Office" placeholder="Enter office" />
            @error('Office') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                {{ $editingId ? 'Replace images (maximum of 5)' : 'Images (maximum of 5)' }}
            </label>
            @if ($editingId && $existingImages && ! $images)
                <p class="mb-2 text-xs text-zinc-500">Current images. Choosing new images will replace all of them.</p>
                <div class="mb-3 grid grid-cols-5 gap-2">
                    @foreach ($existingImages as $imagePath)
                        <img src="{{ asset('storage/'.ltrim($imagePath, '/')) }}" class="h-20 w-20 rounded object-cover" alt="Current facility image" />
                    @endforeach
                </div>
            @endif
            <input
                type="file"
                wire:model="images"
                multiple
                accept="image/*"
                class="w-full rounded border p-2 dark:border-zinc-700 dark:bg-zinc-800"
            />

            <div wire:loading wire:target="images" class="mt-1 text-sm text-zinc-500">
                Uploading...
            </div>

            @error('images') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            @error('images.*') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

            @if ($images)
                <div class="mt-2 grid grid-cols-5 gap-2">
                    @foreach ($images as $image)
                        <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded object-cover" alt="Preview" />
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <flux:textarea wire:model="Description" label="Description" placeholder="Enter facility description" rows="3" />
            @error('Description') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <flux:select wire:model="Status" label="Status">
                <flux:select.option value="Available">Available</flux:select.option>
                <flux:select.option value="Under Maintenance">Under Maintenance</flux:select.option>
                <flux:select.option value="Unavailable">Unavailable</flux:select.option>
            </flux:select>
            @error('Status') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <flux:checkbox.group label="Available amenities">
                @forelse ($this->amenities as $amenity)
                    <flux:checkbox
                        wire:model="selectedAmenityIds"
                        value="{{ $amenity->AID }}"
                        label="{{ $amenity->name }}"
                    />
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No available amenities found.</p>
                @endforelse
            </flux:checkbox.group>
            @error('selectedAmenityIds') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex gap-2">
            <flux:button wire:click="save" variant="primary" class="flex-1">
                {{ $editingId ? 'Update' : 'Create' }}
            </flux:button>

            <flux:button wire:click="$set('showModal', false)" variant="ghost" class="flex-1">
                Cancel
            </flux:button>
        </div>
    </div>
</flux:modal>
