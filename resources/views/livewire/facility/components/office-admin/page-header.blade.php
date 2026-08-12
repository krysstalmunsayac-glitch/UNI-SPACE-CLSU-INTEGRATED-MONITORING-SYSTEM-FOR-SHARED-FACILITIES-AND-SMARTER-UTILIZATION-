<div class="mb-6 flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            My Assigned Facilities
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            View and manage availability of facilities assigned to your office.
        </p>
    </div>

    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-end">
        <x-ui::select wire:model.live="statusFilter" label="Availability" class="w-full sm:w-[190px]">
            <x-ui::select.option value="">All facilities</x-ui::select.option>
            <x-ui::select.option value="Available">Available</x-ui::select.option>
            <x-ui::select.option value="Unavailable">Unavailable</x-ui::select.option>
        </x-ui::select>

        <x-ui::input
            wire:model.live.debounce.400ms="searchInput"
            placeholder="Search by name, location, or office..."
            class="w-full sm:w-[240px]"
        />

    </div>
</div>
