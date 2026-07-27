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
        <flux:select wire:model.live="statusFilter" label="Availability" class="w-full sm:w-[190px]">
            <flux:select.option value="">All facilities</flux:select.option>
            <flux:select.option value="Available">Available</flux:select.option>
            <flux:select.option value="Under Maintenance">Under Maintenance</flux:select.option>
            <flux:select.option value="Unavailable">Unavailable</flux:select.option>
        </flux:select>

        <flux:input
            wire:model="searchInput"
            wire:keydown.enter="applySearch"
            placeholder="Search by name, location, or office..."
            class="w-full sm:w-[240px]"
        />

        <flux:button wire:click="applySearch" icon="magnifying-glass">
            Search
        </flux:button>
    </div>
</div>
