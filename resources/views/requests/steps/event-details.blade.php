                            {{-- STEP 1: Event details --}}
                            <div x-ref="eventDetails" x-show="step === 1" x-cloak class="space-y-4">
                                <x-ui::heading size="lg">Tell us about your event</x-ui::heading>
                                <p class="text-sm text-emerald-900/70 dark:text-zinc-300">Add a clear event name, explain the purpose of your request, and provide the details the facility team needs for review.</p>

                                @if ($guestBooking)
                                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-800 dark:bg-emerald-950/20">
                                        <h3 class="text-sm font-black text-emerald-950 dark:text-white">Guest information</h3>
                                        <p class="mt-1 text-xs text-emerald-900/65 dark:text-zinc-300">The request will be recorded as created by {{ auth()->user()->name }} for audit purposes.</p>
                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <x-ui::input label="Guest name" name="Guest_Name" value="{{ old('Guest_Name') }}" required minlength="2" maxlength="150" />
                                                @error('Guest_Name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <x-ui::input label="Organization or affiliation" name="Guest_Organization" value="{{ old('Guest_Organization') }}" maxlength="200" placeholder="Optional" />
                                                @error('Guest_Organization') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <x-ui::input label="Guest email" name="Guest_Email" type="email" value="{{ old('Guest_Email') }}" maxlength="255" placeholder="Optional" />
                                                @error('Guest_Email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <x-ui::input label="Contact number" name="Guest_Contact" value="{{ old('Guest_Contact') }}" type="tel" minlength="11" maxlength="11" inputmode="numeric" pattern="09[0-9]{9}" title="Enter an 11-digit mobile number starting with 09." placeholder="Optional (09XXXXXXXXX)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" />
                                                @error('Guest_Contact') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <x-ui::input
                                        label="Event name"
                                        name="Event_Title"
                                        value="{{ old('Event_Title') }}"
                                        required
                                        minlength="3"
                                        maxlength="255"
                                    />
                                    @error('Event_Title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-ui::select label="What kind of event is this?" name="Type_Event" x-model="eventType" required>
                                            <x-ui::select.option value="">Choose an event type</x-ui::select.option>
                                            {{-- Adjust these to match your Type_Event enum/values --}}
                                            <x-ui::select.option value="Meeting" :selected="old('Type_Event') == 'Meeting'">Meeting</x-ui::select.option>
                                            <x-ui::select.option value="Seminar" :selected="old('Type_Event') == 'Seminar'">Seminar</x-ui::select.option>
                                            <x-ui::select.option value="Workshop" :selected="old('Type_Event') == 'Workshop'">Workshop</x-ui::select.option>
                                            <x-ui::select.option value="Conference" :selected="old('Type_Event') == 'Conference'">Conference</x-ui::select.option>
                                            <x-ui::select.option value="Other" :selected="old('Type_Event') == 'Other'">Other</x-ui::select.option>
                                        </x-ui::select>
                                        @error('Type_Event') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <x-ui::select label="Event scope" name="Event_Scope" required>
                                            <x-ui::select.option value="">Choose an event scope</x-ui::select.option>
                                            <x-ui::select.option value="Internal" :selected="old('Event_Scope') === 'Internal'">Yes — official CLSU-related event</x-ui::select.option>
                                            <x-ui::select.option value="External" :selected="old('Event_Scope') === 'External'">No — personal or non-CLSU event</x-ui::select.option>
                                        </x-ui::select>
                                        <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">A personal event is non-CLSU even when requested by a CLSU student or employee.</p>
                                        @error('Event_Scope') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div x-cloak x-show="eventType === 'Other'" x-transition class="sm:col-span-2">
                                        <x-ui::input
                                            label="Specify event type"
                                            name="Other_Event_Type"
                                            value="{{ old('Other_Event_Type') }}"
                                            placeholder="e.g. Recognition ceremony"
                                            maxlength="100"
                                            pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ\s]+"
                                            title="Use letters and spaces only; numbers and special characters are not allowed."
                                            x-on:input="$event.target.value = $event.target.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿÑñ\s]/g, '')"
                                            x-bind:required="eventType === 'Other'"
                                        />
                                        @error('Other_Event_Type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    @include('requests.partials.purpose-questionnaire')

                                    <div class="sm:col-span-2">
                                    <x-ui::textarea
                                        label="Event Description"
                                        name="Request_Details"
                                        rows="4"
                                        required
                                        minlength="5"
                                        maxlength="2000"
                                        placeholder="Describe the event, activity, setup, or other important details"
                                    >{{ old('Request_Details') }}</x-ui::textarea>
                                    @error('Request_Details') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800"
                                        x-on:click="
                                            const invalidField = Array.from($refs.eventDetails.querySelectorAll('input, select, textarea'))
                                                .find(field => !field.checkValidity());
                                            if (invalidField) {
                                                invalidField.reportValidity();
                                            } else {
                                                step = 2;
                                                window.scrollTo({ top: 0, behavior: 'smooth' });
                                            }
                                        "
                                    >
                                        Choose date and time
                                    </button>
                                </div>
                            </div>
