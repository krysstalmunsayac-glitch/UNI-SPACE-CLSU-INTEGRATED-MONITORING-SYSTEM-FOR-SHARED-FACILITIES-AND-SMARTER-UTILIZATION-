    <div class="mb-6 flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                <x-ui::icon.rectangle-stack class="size-6" />
            </span>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Amenities Management</h1>
                <p class="text-gray-600 dark:text-gray-400">Create, update, and manage amenities for your facilities.</p>
            </div>
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <x-ui::select
                wire:model.live="facilityFilter"
                placeholder="All facilities"
                class="w-full sm:w-[220px]"
            >
                @foreach ($this->facilityOptions as $facility)
                    <x-ui::select.option value="{{ $facility->FID }}">
                        {{ $facility->Facility_Name }}
                    </x-ui::select.option>
                @endforeach
            </x-ui::select>

            <x-ui::input
                wire:model.live.debounce.400ms="searchInput"
                placeholder="Search by name or description..."
                icon="magnifying-glass"
                class="w-full sm:w-[240px]"
            />

        </div>
    </div>
