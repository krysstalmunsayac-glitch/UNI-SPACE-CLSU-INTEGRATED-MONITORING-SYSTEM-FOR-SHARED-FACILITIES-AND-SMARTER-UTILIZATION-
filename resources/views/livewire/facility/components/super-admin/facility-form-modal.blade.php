<x-ui::modal wire:model.self="showModal" class="!w-[94vw] !max-w-6xl sm:!p-7">
    <div class="space-y-5">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <x-ui::heading size="lg">
                {{ $viewMode ? 'View Facility' : ($editingId ? 'Edit Facility' : 'Add Facility') }}
            </x-ui::heading>
            <x-ui::subheading>
                {{ $viewMode ? 'Review this facility\'s complete details.' : ($editingId ? 'Update this facility\'s details.' : 'Create a new facility.') }}
            </x-ui::subheading>
        </div>

        <fieldset @disabled($viewMode) class="space-y-5">
            <section class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-700 dark:bg-zinc-900/60">
                <div class="mb-4">
                    <h3 class="font-bold text-zinc-900 dark:text-white">Basic information</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Identify the facility and set its main booking details.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="sm:col-span-2 xl:col-span-2">
                        <x-ui::input wire:model="Facility_Name" label="Facility Name" placeholder="Enter facility name" required minlength="2" maxlength="150" />
                    </div>

                    <div>
                        <x-ui::select wire:model="Status" label="Status" required>
                            <x-ui::select.option value="Available">Available</x-ui::select.option>
                            <x-ui::select.option value="Unavailable">Unavailable</x-ui::select.option>
                        </x-ui::select>
                    </div>

                    @if ($Status === 'Unavailable')
                        <div class="sm:col-span-2 xl:col-span-3">
                            <div class="grid gap-3 rounded-xl border border-red-100 bg-red-50/60 p-4 dark:border-red-900/40 dark:bg-red-950/10 sm:grid-cols-[minmax(0,1fr)_6rem_6rem_6rem]">
                                <x-ui::input wire:model="Available_Date" type="date" label="Available again date" min="{{ now()->toDateString() }}" />
                                <x-ui::select wire:model="Available_Hour" label="Hour">
                                    @foreach (range(1, 12) as $hour)
                                        <x-ui::select.option value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</x-ui::select.option>
                                    @endforeach
                                </x-ui::select>
                                <x-ui::select wire:model="Available_Minute" label="Minute">
                                    @foreach (['00', '15', '30', '45'] as $minute)
                                        <x-ui::select.option value="{{ $minute }}">{{ $minute }}</x-ui::select.option>
                                    @endforeach
                                </x-ui::select>
                                <x-ui::select wire:model="Available_Period" label="AM/PM">
                                    <x-ui::select.option value="AM">AM</x-ui::select.option>
                                    <x-ui::select.option value="PM">PM</x-ui::select.option>
                                </x-ui::select>
                            </div>
                            @error('Available_Date') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
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
                        <x-ui::input wire:model="Capacity" type="number" min="1" max="100000" label="Capacity" placeholder="Number of people" required />
                    </div>

                    <div>
                        <x-ui::input wire:model="Office" label="Managing office" placeholder="Enter office" required minlength="2" maxlength="150" />
                    </div>

                    <div class="sm:col-span-2 xl:col-span-3">
                        <x-ui::input wire:model="Location" label="Location" placeholder="Building, street, or campus area" required minlength="2" maxlength="255" />
                    </div>
                </div>
            </section>

            @if (auth()->user()?->isSuperAdmin())
                <section
                    wire:key="facility-location-picker-{{ $editingId ?? 'new' }}"
                    x-data="facilityLocationPicker($wire)"
                    x-init="openPicker()"
                    class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="font-bold text-zinc-900 dark:text-white">Exact map location</h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Find the saved address, then click or drag the marker to the exact building.</p>
                        </div>
                        <button
                            type="button"
                            x-on:click="findLocation()"
                            x-bind:disabled="searching"
                            class="w-full shrink-0 rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800 disabled:opacity-50 sm:w-auto"
                        >
                            <span x-text="searching ? 'Finding...' : 'Find location'"></span>
                        </button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div wire:ignore x-ref="map" class="h-72 w-full overflow-hidden rounded-xl border border-emerald-900/10 dark:border-white/10"></div>

                        <div class="space-y-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
                            <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                Latitude
                                <input
                                    x-ref="latitudeInput"
                                    wire:model.blur="Latitude"
                                    x-on:change="setManualCoordinates()"
                                    type="number"
                                    min="-90"
                                    max="90"
                                    step="0.0000001"
                                    inputmode="decimal"
                                    class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-900"
                                    placeholder="15.7354000"
                                >
                            </label>
                            <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                Longitude
                                <input
                                    x-ref="longitudeInput"
                                    wire:model.blur="Longitude"
                                    x-on:change="setManualCoordinates()"
                                    type="number"
                                    min="-180"
                                    max="180"
                                    step="0.0000001"
                                    inputmode="decimal"
                                    class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-900"
                                    placeholder="120.9335000"
                                >
                            </label>
                            <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">You may also type the exact coordinates manually.</p>
                            <button type="button" x-on:click="clearPin()" class="text-xs font-bold text-red-600 hover:text-red-700">Clear exact pin</button>
                            @error('Latitude') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
                            @error('Longitude') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>
            @endif

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <h3 class="font-bold text-zinc-900 dark:text-white">Rates and pricing</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Include durations, extra-hour charges, discounts, or fee conditions.</p>
                    </div>
                    <x-ui::textarea wire:model="rates" label="Rate details" placeholder="Example: ₱5,000 for 8 hours" rows="5" maxlength="10000" />
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <h3 class="font-bold text-zinc-900 dark:text-white">Facility description</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Give users a short, useful overview of the space.</p>
                    </div>
                    <x-ui::textarea wire:model="Description" label="Description" placeholder="Describe the facility and its common uses" rows="5" required minlength="5" maxlength="2000" />
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                    <div class="mb-4">
                        <h3 class="font-bold text-zinc-900 dark:text-white">Protocols and guidelines</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">List rules, restrictions, approval requirements, and booking procedures.</p>
                    </div>
                    <x-ui::textarea wire:model="protocols_and_guidelines" label="Guidelines" placeholder="Enter the rules users need to know before requesting this facility" rows="4" maxlength="10000" />
                </section>
            </div>

            <section class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-700 dark:bg-zinc-900/60">
                <div class="mb-4">
                    <h3 class="font-bold text-zinc-900 dark:text-white">Facility photos</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Upload up to five clear images of the space.</p>
                </div>

                @if ($editingId && $existingImages)
                    <p class="mb-3 text-xs text-zinc-500">Remove individual images or upload replacements. Changes are applied when you click Update.</p>
                    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($existingImages as $existingImage)
                            <div class="group relative" wire:key="existing-facility-image-{{ $existingImage['id'] }}">
                                <img src="{{ asset('storage/'.ltrim($existingImage['path'], '/')) }}" class="h-24 w-full rounded-lg object-cover" alt="Current facility image" />
                                <button
                                    type="button"
                                    wire:click="removeExistingImage({{ $existingImage['id'] }})"
                                    class="absolute right-1 top-1 flex size-7 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white shadow transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-yellow-300"
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
                    class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                />

                <div wire:loading wire:target="images" class="mt-2 text-sm text-zinc-500">Uploading...</div>
                @error('images') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror
                @error('images.*') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror

                @if ($images)
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($images as $index => $image)
                            <div class="group relative" wire:key="new-facility-image-{{ $index }}">
                                <img src="{{ $image->temporaryUrl() }}" class="h-24 w-full rounded-lg object-cover" alt="New image preview" />
                                <button
                                    type="button"
                                    wire:click="removeNewImage({{ $index }})"
                                    class="absolute right-1 top-1 flex size-7 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white shadow transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-yellow-300"
                                    aria-label="Remove new image"
                                    title="Remove image"
                                >×</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </fieldset>

        <div class="sticky bottom-0 z-20 flex flex-col justify-end gap-3 border-t border-zinc-200 bg-white py-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row">
            @if (! $viewMode)
            <x-ui::button wire:click="save" variant="primary" class="w-full sm:w-auto sm:min-w-36">
                {{ $editingId ? 'Update' : 'Create' }}
            </x-ui::button>
            @endif

            <x-ui::button wire:click="$set('showModal', false)" variant="ghost" class="w-full sm:w-auto sm:min-w-32">
                {{ $viewMode ? 'Close' : 'Cancel' }}
            </x-ui::button>
        </div>
    </div>
</x-ui::modal>
