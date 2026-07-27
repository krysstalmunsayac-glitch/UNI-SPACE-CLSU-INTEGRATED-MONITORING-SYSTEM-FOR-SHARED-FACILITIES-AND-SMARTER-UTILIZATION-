            <flux:card>
                <flux:heading size="lg">Event details</flux:heading>

                <form action="{{ route('events.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <flux:input label="Event title" name="Event_Title" value="{{ old('Event_Title') }}" />
                        @error('Event_Title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:textarea label="Description" name="Description" rows="4">{{ old('Description') }}</flux:textarea>
                        @error('Description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:input label="Event type" name="Type_Event" value="{{ old('Type_Event') }}" placeholder="e.g. Workshop, Seminar, Social" />
                        @error('Type_Event') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:button type="submit" class="w-full sm:w-auto">Create event</flux:button>
                        <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Back to home</a>
                    </div>
                </form>
            </flux:card>
