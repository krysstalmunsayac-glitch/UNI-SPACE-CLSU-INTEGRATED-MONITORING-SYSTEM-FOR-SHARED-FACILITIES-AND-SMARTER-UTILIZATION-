                                <div x-show="step === 3" x-cloak class="grid gap-4 sm:grid-cols-2">

                                    @include('requests.partials.purpose-questionnaire')

                                    <div class="sm:col-span-2">
                                        <x-ui::input label="Expected Number of Attendees" name="Capacity" type="number" min="1" max="{{ $facility->Capacity ?? 100000 }}" value="{{ old('Capacity') }}" required />
                                        @error('Capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <x-ui::input
                                            type="file"
                                            name="attachment"
                                            label="Request letter"
                                            accept=".pdf,application/pdf"
                                        />
                                        <p class="mt-1 text-xs text-emerald-900/70 dark:text-zinc-300">PDF — max 5 MB.</p>
                                        @error('attachment') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                </div>
