                                <div x-show="step === 3" x-cloak>
                                    @if ($permanentAmenities->isNotEmpty())
                                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-800 dark:bg-emerald-950/20">
                                            <h3 class="text-sm font-bold text-emerald-950 dark:text-white">Included amenities</h3>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">These built-in amenities are automatically included when the facility is available for your selected schedule.</p>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($permanentAmenities as $amenity)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-bold text-emerald-800 dark:border-emerald-700 dark:bg-zinc-900 dark:text-emerald-200">
                                                        <span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                                        {{ $amenity->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                                        <div>
                                            <h3 class="text-sm font-bold text-emerald-950 dark:text-white">Additional amenities</h3>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Select any available additional items you need and specify the quantity. Booking does not reduce their configured unit count.</p>
                                        </div>
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200" x-text="`${selectedAmenities.length} selected`"></span>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @forelse ($additionalAmenities as $amenity)
                                            <div
                                                class="rounded-xl border p-4 transition"
                                                :class="selectedAmenities.includes('{{ $amenity->AID }}') ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/10 dark:border-emerald-500 dark:bg-emerald-950/25' : 'border-zinc-200 bg-white hover:border-emerald-300 dark:border-zinc-700 dark:bg-zinc-900'"
                                            >
                                                <label class="flex cursor-pointer items-start gap-3">
                                                    <input
                                                        type="checkbox"
                                                        name="Amenity_ID[]"
                                                        value="{{ $amenity->AID }}"
                                                        x-model="selectedAmenities"
                                                        class="mt-0.5 size-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600"
                                                    >
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block font-bold text-emerald-950 dark:text-white">{{ $amenity->name }}</span>
                                                        <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                                            <span x-text="`${amenityRemaining('{{ $amenity->AID }}')} configured ${amenityRemaining('{{ $amenity->AID }}') === 1 ? 'unit' : 'units'}`"></span>
                                                        </span>
                                                    </span>
                                                    <span x-show="selectedAmenities.includes('{{ $amenity->AID }}')" class="text-emerald-600" aria-hidden="true">✓</span>
                                                </label>

                                                <div x-cloak x-show="selectedAmenities.includes('{{ $amenity->AID }}')" x-transition class="mt-4 border-t border-emerald-200 pt-3 dark:border-emerald-800">
                                                    <label for="amenity-quantity-{{ $amenity->AID }}" class="mb-1.5 block text-xs font-bold text-emerald-900 dark:text-emerald-200">How many do you need?</label>
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            id="amenity-quantity-{{ $amenity->AID }}"
                                                            name="Amenity_Quantity[{{ $amenity->AID }}]"
                                                            type="number"
                                                            min="1"
                                                            x-bind:max="amenityRemaining('{{ $amenity->AID }}')"
                                                            value="{{ old('Amenity_Quantity.'.$amenity->AID, 1) }}"
                                                            x-bind:disabled="!selectedAmenities.includes('{{ $amenity->AID }}')"
                                                            class="h-10 w-24 rounded-lg border border-emerald-200 bg-white px-3 text-sm font-semibold text-emerald-950 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 dark:border-emerald-800 dark:bg-zinc-950 dark:text-white"
                                                        >
                                                        <span class="text-xs text-zinc-500" x-text="`Maximum ${amenityRemaining('{{ $amenity->AID }}')}`"></span>
                                                    </div>
                                                    @error('Amenity_Quantity.'.$amenity->AID)
                                                        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        @empty
                                            <div class="rounded-xl border border-dashed border-zinc-300 p-5 text-center text-sm text-zinc-500 dark:border-zinc-700 sm:col-span-2">
                                                No additional amenities are currently available for this facility.
                                            </div>
                                        @endforelse
                                    </div>

                                    @error('Amenity_ID')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror

                                    @error('Amenity_ID.*')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
