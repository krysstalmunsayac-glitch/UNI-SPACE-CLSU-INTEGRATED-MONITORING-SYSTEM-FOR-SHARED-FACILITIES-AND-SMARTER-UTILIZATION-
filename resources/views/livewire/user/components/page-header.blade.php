@php($stats = $this->userStats)

<section class="mb-5 overflow-hidden rounded-lg border border-emerald-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/60 dark:bg-zinc-950 dark:text-emerald-300">
                    Administration
                </div>

                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm dark:bg-zinc-950 dark:text-emerald-300">
                        <x-ui::icon.users class="size-6" />
                    </span>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">User Management</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">Manage access, roles, account status, and facility assignments.</p>
                    </div>
                </div>
            </div>

            <x-ui::button wire:click="create" icon="plus" variant="primary" class="w-full justify-center lg:w-auto">
                Add User
            </x-ui::button>
        </div>
    </div>

    <div class="grid gap-3 border-b border-gray-100 px-5 py-4 sm:grid-cols-2 xl:grid-cols-5 dark:border-zinc-800">
        @foreach ([
            ['label' => 'Total users', 'value' => $stats['total'], 'classes' => 'border-gray-100 bg-gray-50 text-gray-950 dark:border-zinc-800 dark:bg-zinc-900/70 dark:text-white'],
            ['label' => 'Superadmins', 'value' => $stats['super_admins'], 'classes' => 'border-violet-100 bg-violet-50 text-violet-900 dark:border-violet-900/50 dark:bg-violet-950/20 dark:text-violet-100'],
            ['label' => 'Office admins', 'value' => $stats['office_admins'], 'classes' => 'border-blue-100 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-100'],
            ['label' => 'End users', 'value' => $stats['end_users'], 'classes' => 'border-emerald-100 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100'],
            ['label' => 'Inactive', 'value' => $stats['inactive'], 'classes' => 'border-amber-100 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100'],
        ] as $stat)
            <div class="rounded-lg border px-4 py-3 {{ $stat['classes'] }}">
                <p class="text-xs font-medium opacity-70">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-3 px-5 py-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <div class="sm:col-span-2 lg:col-span-2">
            <x-ui::input
                wire:model.live.debounce.400ms="searchInput"
                label="Search users"
                placeholder="Search by name or email..."
                icon="magnifying-glass"
                class="w-full"
            />
        </div>

        <x-ui::select wire:model.live="roleFilter" label="Role">
            <x-ui::select.option value="">All roles</x-ui::select.option>
            <x-ui::select.option value="super_admin">Superadmins</x-ui::select.option>
            <x-ui::select.option value="admin">Office admins</x-ui::select.option>
            <x-ui::select.option value="user">End users</x-ui::select.option>
        </x-ui::select>

        <x-ui::select wire:model.live="accountStatusFilter" label="Account status">
            <x-ui::select.option value="">All accounts</x-ui::select.option>
            <x-ui::select.option value="active">Active</x-ui::select.option>
            <x-ui::select.option value="inactive">Inactive</x-ui::select.option>
        </x-ui::select>
    </div>
</section>
