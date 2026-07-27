    <div class="mb-6 flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Amenity Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Create, update, and manage amenities for your facilities.</p>
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <flux:input
                wire:model="searchInput"
                wire:keydown.enter="applySearch"
                placeholder="Search by name or description..."
                class="w-full sm:w-[240px]"
            />

            <flux:button wire:click="applySearch" icon="magnifying-glass">
                Search
            </flux:button>
        </div>
    </div>
