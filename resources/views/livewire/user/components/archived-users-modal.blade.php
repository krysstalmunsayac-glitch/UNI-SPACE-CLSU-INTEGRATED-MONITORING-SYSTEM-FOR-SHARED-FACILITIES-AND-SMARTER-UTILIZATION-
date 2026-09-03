    {{-- Archived users table --}}
    <div class="space-y-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <x-ui::heading size="lg">Archived Users</x-ui::heading>
                    <x-ui::subheading>Restore archived accounts or delete them permanently.</x-ui::subheading>
                </div>
                <div class="w-full lg:w-auto lg:min-w-[28rem]">
                    <x-ui::input
                        wire:model.live.debounce.400ms="searchInput"
                        label="Search archived users"
                        placeholder="Search by name, email, or role..."
                        icon="magnifying-glass"
                        class="w-full"
                    />
                </div>
            </div>

            <div class="min-h-56 overflow-x-auto">
                <x-ui::table :paginate="$this->archivedUsers">
                    <x-ui::table.columns>
                        <x-ui::table.column>
                            User
                        </x-ui::table.column>

                        <x-ui::table.column>
                            Email
                        </x-ui::table.column>

                        <x-ui::table.column>
                            Role
                        </x-ui::table.column>

                        <x-ui::table.column>
                            Archived
                        </x-ui::table.column>

                        <x-ui::table.column>
                            Actions
                        </x-ui::table.column>
                    </x-ui::table.columns>

                    <x-ui::table.rows>
                        @forelse ($this->archivedUsers as $archivedUser)
                            <x-ui::table.row :key="'archived-user-'.$archivedUser->id">
                                <x-ui::table.cell>
                                    <div class="flex items-center gap-3">
                                        <x-ui::avatar
                                            size="sm"
                                            :src="$archivedUser->avatar_url"
                                            :name="$archivedUser->name"
                                        />

                                        <div>
                                            <div class="font-medium">
                                                {{ $archivedUser->name }}
                                            </div>

                                            <div class="text-xs text-zinc-500">
                                                USR-{{ str_pad((string) $archivedUser->id, 5, '0', STR_PAD_LEFT) }}
                                            </div>
                                        </div>
                                    </div>
                                </x-ui::table.cell>

                                <x-ui::table.cell class="whitespace-nowrap">
                                    {{ $archivedUser->email }}
                                </x-ui::table.cell>

                                <x-ui::table.cell>
                                    <x-ui::badge
                                        size="sm"
                                        :color="match ($archivedUser->user_type) {
                                            'super_admin' => 'purple',
                                            'admin' => 'blue',
                                            default => 'zinc',
                                        }"
                                    >
                                        {{ $archivedUser->roleLabel() }}
                                    </x-ui::badge>
                                </x-ui::table.cell>

                                <x-ui::table.cell class="whitespace-nowrap">
                                    <div>
                                        {{ $archivedUser->deleted_at?->format('M d, Y') ?? '—' }}
                                    </div>

                                    @if ($archivedUser->deleted_at)
                                        <div class="text-xs text-zinc-500">
                                            {{ $archivedUser->deleted_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </x-ui::table.cell>

                                <x-ui::table.cell>
                                    <div class="flex justify-end">
                                        <x-ui::dropdown position="bottom" align="end">
                                            <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for {{ $archivedUser->name }}" />
                                            <x-ui::menu>
                                                <x-ui::menu.item icon="arrow-path" wire:click="restoreUser({{ $archivedUser->id }})" data-ui-confirm="Restore this user account?">Restore</x-ui::menu.item>
                                                <x-ui::menu.separator />
                                                <x-ui::menu.item icon="trash" variant="danger" wire:click="forceDeleteUser({{ $archivedUser->id }})" data-ui-confirm="This action cannot be undone. Permanently delete this user?">Delete permanently</x-ui::menu.item>
                                            </x-ui::menu>
                                        </x-ui::dropdown>
                                    </div>
                                </x-ui::table.cell>
                            </x-ui::table.row>
                        @empty
                            <x-ui::table.row>
                                <x-ui::table.cell
                                    colspan="5"
                                    class="py-10 text-center"
                                >
                                    <div class="space-y-2">
                                        <x-ui::icon.archive-box
                                            class="mx-auto size-8 text-zinc-400"
                                        />

                                        <p class="text-sm text-zinc-500">
                                            No archived users found.
                                        </p>
                                    </div>
                                </x-ui::table.cell>
                            </x-ui::table.row>
                        @endforelse
                    </x-ui::table.rows>
                </x-ui::table>
            </div>

            @unless ($archiveOnly ?? false)
            <div class="flex justify-end">
                <x-ui::button
                    wire:click="$set('showArchivedModal', false)"
                    variant="ghost"
                >
                    Close
                </x-ui::button>
            </div>
            @endunless
    </div>
