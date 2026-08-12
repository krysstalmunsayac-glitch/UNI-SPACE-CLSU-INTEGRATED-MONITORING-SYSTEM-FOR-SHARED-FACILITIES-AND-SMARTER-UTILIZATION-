    <x-ui::card class="overflow-hidden">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-ui::heading size="lg">User directory</x-ui::heading>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Showing {{ $this->users->firstItem() ?? 0 }}–{{ $this->users->lastItem() ?? 0 }} of {{ number_format($this->users->total()) }} matching accounts
                </p>
            </div>
        </div>

        <x-ui::table :paginate="$this->users">
            <x-ui::table.columns>
                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    User
                </x-ui::table.column>

                <x-ui::table.column>
                    Role
                </x-ui::table.column>

                <x-ui::table.column>Office</x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                >
                    Joined
                </x-ui::table.column>

                <x-ui::table.column>
                    Status
                </x-ui::table.column>

                <x-ui::table.column>
                    Actions
                </x-ui::table.column>
            </x-ui::table.columns>

            <x-ui::table.rows>
                @forelse ($this->users as $user)
                    <x-ui::table.row :key="'user-'.$user->id">
                        {{-- Name --}}
                        <x-ui::table.cell>
                            <div class="flex items-center gap-3">
                                <x-ui::avatar
                                    size="sm"
                                    :src="$user->avatar_url"
                                    :name="$user->name"
                                    class="bg-gradient-to-br from-blue-500 to-purple-600"
                                />

                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-zinc-950 dark:text-white">{{ $user->name }}</div>
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                                    <div class="mt-0.5 text-[11px] font-medium text-zinc-400">ID #{{ $user->id }}</div>
                                </div>
                            </div>
                        </x-ui::table.cell>

                        {{-- Role --}}
                        <x-ui::table.cell>
                            <x-ui::badge
                                size="sm"
                                :color="match ($user->user_type) {
                                    'super_admin' => 'purple',
                                    'admin' => 'blue',
                                    default => 'zinc',
                                }"
                                inset="top bottom"
                            >
                                {{ $user->roleLabel() }}
                            </x-ui::badge>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $user->office ?: '—' }}</span>
                        </x-ui::table.cell>

                        {{-- Joined --}}
                        <x-ui::table.cell class="whitespace-nowrap">
                            {{ $user->created_at?->format('M d, Y') ?? '—' }}
                        </x-ui::table.cell>

                        {{-- Status --}}
                        <x-ui::table.cell>
                            <button
                                type="button"
                                wire:click="requestToggleActive({{ $user->id }})"
                                wire:loading.attr="disabled"
                                wire:target="requestToggleActive({{ $user->id }})"
                                class="group rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-900"
                                aria-label="{{ $user->is_active ? 'Deactivate' : 'Activate' }} {{ $user->name }} account"
                                title="Click to {{ $user->is_active ? 'deactivate' : 'activate' }} this account"
                            >
                                <span
                                    @class([
                                        'inline-flex min-w-20 items-center justify-center whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none text-white transition-colors',
                                        'bg-emerald-700 group-hover:bg-red-600' => $user->is_active,
                                        'bg-red-600 group-hover:bg-emerald-700' => ! $user->is_active,
                                    ])
                                >
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </button>
                        </x-ui::table.cell>

                        {{-- Actions --}}
                        <x-ui::table.cell>
                            <x-ui::dropdown :position="$loop->remaining < 2 ? 'top' : 'bottom'" align="end">
                                <x-ui::button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    inset="top bottom"
                                />

                                <x-ui::menu>
                                    <x-ui::menu.item
                                        icon="pencil"
                                        wire:click="edit({{ $user->id }})"
                                    >
                                        Edit
                                    </x-ui::menu.item>

                                    @if ($user->user_type === 'admin')
                                        <x-ui::menu.item
                                            icon="building-office"
                                            wire:click="openAssignments({{ $user->id }})"
                                        >
                                            Assign Facilities
                                        </x-ui::menu.item>
                                    @endif

                                    <x-ui::menu.item
                                        icon="power"
                                        wire:click="requestToggleActive({{ $user->id }})"
                                    >
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-ui::menu.item>

                                    <x-ui::menu.separator />

                                    <x-ui::menu.item
                                        icon="archive-box"
                                        variant="danger"
                                        wire:click="delete({{ $user->id }})"
                                        data-ui-confirm="Are you sure you want to archive {{ $user->name }}? Their account will be moved to Archived Users and can be restored later."
                                        data-ui-confirm-title="Confirm archive"
                                        data-ui-confirm-label="Archive user"
                                        data-ui-confirm-variant="danger"
                                    >
                                        Archive
                                    </x-ui::menu.item>

                                </x-ui::menu>
                            </x-ui::dropdown>
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @empty
                    <x-ui::table.row>
                        <x-ui::table.cell
                            colspan="6"
                            class="py-12 text-center"
                        >
                            <div class="flex flex-col items-center gap-2 text-zinc-500">
                                <x-ui::icon.users class="size-9 text-zinc-300 dark:text-zinc-600" />
                                <p class="font-semibold text-zinc-700 dark:text-zinc-300">No users found</p>
                                <p class="text-sm">Try changing the search or account filters.</p>
                            </div>
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @endforelse
            </x-ui::table.rows>
        </x-ui::table>
    </x-ui::card>
