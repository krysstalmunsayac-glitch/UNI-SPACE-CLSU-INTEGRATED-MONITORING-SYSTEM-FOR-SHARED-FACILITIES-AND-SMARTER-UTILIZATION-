<x-ui::modal wire:model.self="showModal" class="w-[95vw] max-w-5xl">
    <div class="space-y-6">
        <div>
            <x-ui::heading size="lg">
                {{ $viewMode ? 'View Facility' : ($editingId ? 'Edit Facility' : 'Add Facility') }}
            </x-ui::heading>
            <x-ui::subheading>
                {{ $viewMode ? 'Review this facility\'s complete details.' : ($editingId ? 'Update this facility\'s details.' : 'Create a new facility.') }}
            </x-ui::subheading>
        </div>

        <fieldset @disabled($viewMode) class="grid gap-6 md:grid-cols-2">
        <div>
            <x-ui::input wire:model="Facility_Name" label="Facility Name" placeholder="Enter facility name" required minlength="2" maxlength="150" />
        </div>

        <div>
            <x-ui::input wire:model="Price" type="number" min="0" max="9999999.99" step="0.01" label="Price (optional)" placeholder="Leave empty if not applicable" />
        </div>

        <div>
            <x-ui::input wire:model="Capacity" type="number" min="70" max="100000" label="Capacity" placeholder="Minimum 70" required />
        </div>

        <div>
            <x-ui::input wire:model="Location" label="Location" placeholder="Enter location" required minlength="2" maxlength="255" />
        </div>

        @if (auth()->user()?->isSuperAdmin())
            <div
                x-data="facilityLocationPicker($wire)"
                x-effect="$wire.showModal ? openPicker() : closePicker()"
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
            <x-ui::select wire:model="facility_type" label="Facility type" required>
                <x-ui::select.option value="">Select type</x-ui::select.option>
                <x-ui::select.option value="sports">Sports</x-ui::select.option>
                <x-ui::select.option value="conference">Conference</x-ui::select.option>
                <x-ui::select.option value="auditorium">Auditorium</x-ui::select.option>
                <x-ui::select.option value="classroom">Classroom</x-ui::select.option>
                <x-ui::select.option value="laboratory">Laboratory</x-ui::select.option>
                <x-ui::select.option value="other">Other</x-ui::select.option>
            </x-ui::select>
        </div>

        <div>
            <x-ui::input wire:model="Office" label="Office" placeholder="Enter office" required minlength="2" maxlength="150" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Images (maximum of 5)
            </label>
            @if ($editingId && $existingImages)
                <p class="mb-2 text-xs text-zinc-500">Remove individual images or upload replacements. Changes are applied when you click Update.</p>
                <div class="mb-3 grid grid-cols-5 gap-2">
                    @foreach ($existingImages as $existingImage)
                        <div class="group relative" wire:key="existing-facility-image-{{ $existingImage['id'] }}">
                            <img src="{{ asset('storage/'.ltrim($existingImage['path'], '/')) }}" class="h-20 w-full rounded object-cover" alt="Current facility image" />
                            <button
                                type="button"
                                wire:click="removeExistingImage({{ $existingImage['id'] }})"
                                class="absolute right-1 top-1 flex size-6 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white shadow transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-yellow-300"
                                aria-label="Remove current image"
                                title="Remove image"
                            >×</button>
                        </div>
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
                    @foreach ($images as $index => $image)
                        <div class="group relative" wire:key="new-facility-image-{{ $index }}">
                            <img src="{{ $image->temporaryUrl() }}" class="h-20 w-full rounded object-cover" alt="New image preview" />
                            <button
                                type="button"
                                wire:click="removeNewImage({{ $index }})"
                                class="absolute right-1 top-1 flex size-6 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white shadow transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-yellow-300"
                                aria-label="Remove new image"
                                title="Remove image"
                            >×</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <x-ui::textarea wire:model="Description" label="Description" placeholder="Enter facility description" rows="3" required minlength="5" maxlength="2000" />
        </div>

        <div>
            <x-ui::select wire:model="Status" label="Status" required>
                <x-ui::select.option value="Available">Available</x-ui::select.option>
                <x-ui::select.option value="Unavailable">Unavailable</x-ui::select.option>
            </x-ui::select>
        </div>

        </fieldset>

        <div class="flex gap-2">
            @if (! $viewMode)
            <x-ui::button wire:click="save" variant="primary" class="flex-1">
                {{ $editingId ? 'Update' : 'Create' }}
            </x-ui::button>
            @endif

            <x-ui::button wire:click="$set('showModal', false)" variant="ghost" class="flex-1">
                {{ $viewMode ? 'Close' : 'Cancel' }}
            </x-ui::button>
        </div>
    </div>
</x-ui::modal>
