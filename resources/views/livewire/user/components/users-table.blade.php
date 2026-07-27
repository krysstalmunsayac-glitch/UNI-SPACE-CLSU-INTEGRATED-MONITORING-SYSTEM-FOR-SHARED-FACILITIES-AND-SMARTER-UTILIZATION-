    {{-- Users table --}}
    <flux:card>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">
                Users
            </flux:heading>

            <div class="flex flex-wrap gap-2">
                <flux:button
                    wire:click="openArchivedUsers"
                    icon="archive-box"
                    variant="danger"
                >
                    Archived
                </flux:button>

                <flux:button
                    wire:click="create"
                    icon="plus"
                    variant="primary"
                >
                    Add User
                </flux:button>
            </div>
        </div>

        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'email'"
                    :direction="$sortDirection"
                    wire:click="sort('email')"
                >
                    Email
                </flux:table.column>

                <flux:table.column>
                    Role
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                >
                    Joined
                </flux:table.column>

                <flux:table.column>
                    Status
                </flux:table.column>

                <flux:table.column>
                    Actions
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="'user-'.$user->id">
                        {{-- Name --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    size="sm"
                                    :src="$user->avatar_url"
                                    :name="$user->name"
                                    class="bg-gradient-to-br from-blue-500 to-purple-600"
                                />

                                <div>
                                    <div class="font-medium">
                                        {{ $user->name }}
                                    </div>

                                    <div class="text-xs text-zinc-500">
                                        #{{ $user->id }}
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Email --}}
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $user->email }}
                        </flux:table.cell>

                        {{-- Role --}}
                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match ($user->user_type) {
                                    'super_admin' => 'purple',
                                    'admin' => 'blue',
                                    default => 'zinc',
                                }"
                                inset="top bottom"
                            >
                                {{ $user->roleLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Joined --}}
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $user->created_at?->format('M d, Y') ?? 'N/A' }}
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="$user->is_active ? 'green' : 'red'"
                                inset="top bottom"
                            >
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    inset="top bottom"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        icon="pencil"
                                        wire:click="edit({{ $user->id }})"
                                    >
                                        Edit
                                    </flux:menu.item>

                                    @if ($user->user_type === 'admin')
                                        <flux:menu.item
                                            icon="building-office"
                                            wire:click="openAssignments({{ $user->id }})"
                                        >
                                            Assign Facilities
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.item
                                        icon="power"
                                        wire:click="toggleActive({{ $user->id }})"
                                        wire:confirm="{{ $user->is_active
                                            ? 'Deactivate '.$user->name.'? They will lose access to the system.'
                                            : 'Activate '.$user->name.'? They will regain access to the system.' }}"
                                    >
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item
                                        icon="archive-box"
                                        class="text-red-600 dark:text-red-400"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Are you sure you want to archive this user?"
                                    >
                                        Archive
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell
                            colspan="6"
                            class="py-8 text-center"
                        >
                            No users found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
