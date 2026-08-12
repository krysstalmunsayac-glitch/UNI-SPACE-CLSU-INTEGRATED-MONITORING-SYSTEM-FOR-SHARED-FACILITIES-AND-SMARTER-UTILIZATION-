    {{-- ===== View toggle ===== --}}
    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Calendar View</h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400">Weekly planning and monthly availability overview</p>
        </div>

        <x-ui::button.group>
            <x-ui::button
                variant="{{ $view === 'weekly' ? 'primary' : 'outline' }}"
                x-on:click="
                    switchView('timeGridWeek');
                    $wire.setView('weekly');
                "
            >
                Weekly
            </x-ui::button>

            <x-ui::button
                variant="{{ $view === 'monthly' ? 'primary' : 'outline' }}"
                x-on:click="
                    switchView('dayGridMonth');
                    $wire.setView('monthly');
                "
            >
                Monthly
            </x-ui::button>
        </x-ui::button.group>
    </div>
