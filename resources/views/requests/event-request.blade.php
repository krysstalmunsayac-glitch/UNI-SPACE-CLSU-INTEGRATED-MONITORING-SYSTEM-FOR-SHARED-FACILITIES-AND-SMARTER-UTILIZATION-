<x-layouts.home.header>
    <x-ui::main class="bg-emerald-50/70 px-4 py-8 dark:bg-zinc-950 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <x-ui::card class="rounded-3xl border-emerald-900/10 p-6 shadow-xl dark:border-white/10 sm:p-8">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Event request</p>
                <h1 class="mt-2 text-3xl font-black text-emerald-950 dark:text-white">{{ $event->Event_Title }}</h1>
                <p class="mt-2 text-sm text-emerald-900/70 dark:text-zinc-300">Choose a valid schedule and provide the request details.</p>

                @if ($errors->any())
                    <div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <p class="font-bold">Please correct the following:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('requests.event.store', $event) }}" method="POST" class="mt-6 grid gap-5 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-ui::input label="First event day" name="Proposed_Date" type="date" min="{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}" value="{{ old('Proposed_Date') }}" required />
                        @error('Proposed_Date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-ui::input label="Last event day" name="Proposed_End_Date" type="date" min="{{ old('Proposed_Date', app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user())) }}" value="{{ old('Proposed_End_Date', old('Proposed_Date')) }}" required />
                        @error('Proposed_End_Date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-ui::input label="Start time" name="Proposed_Start_Time" type="time" min="05:00" max="23:30" step="1800" value="{{ old('Proposed_Start_Time') }}" required />
                        @error('Proposed_Start_Time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-ui::input label="End time" name="Proposed_End_Time" type="time" min="06:00" max="24:00" step="1800" value="{{ old('Proposed_End_Time') }}" required />
                        @error('Proposed_End_Time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui::textarea label="Purpose" name="Purpose" rows="4" minlength="5" maxlength="1000" required>{{ old('Purpose') }}</x-ui::textarea>
                        @error('Purpose') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui::input label="Expected Number of Attendees" name="Capacity" type="number" min="1" max="100000" value="{{ old('Capacity') }}" required />
                        @error('Capacity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($amenities->isNotEmpty())
                        <fieldset class="sm:col-span-2">
                            <legend class="text-sm font-bold text-emerald-950 dark:text-white">Optional amenities</legend>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($amenities as $amenity)
                                    <x-ui::checkbox name="Amenity_ID[]" value="{{ $amenity->AID }}" label="{{ $amenity->name }}" :checked="in_array($amenity->AID, array_map('intval', old('Amenity_ID', [])), true)" />
                                @endforeach
                            </div>
                            @error('Amenity_ID.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </fieldset>
                    @endif

                    <div class="flex flex-wrap gap-3 sm:col-span-2">
                        <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Submit request</button>
                        <a href="{{ route('dashboard') }}" class="rounded-xl border border-emerald-900/10 px-5 py-3 text-sm font-semibold text-emerald-900">Cancel</a>
                    </div>
                </form>
            </x-ui::card>
        </div>
    </x-ui::main>
</x-layouts.home.header>
