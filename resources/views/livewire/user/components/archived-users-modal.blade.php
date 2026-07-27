    {{-- Archived users modal --}}
    <flux:modal
        wire:model.self="showArchivedModal"
        class="w-[95vw] max-w-7xl"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Archived Users
                </flux:heading>

                <flux:subheading>
                    Restore archived accounts or delete them permanently.
                </flux:subheading>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->archivedUsers">
                    <flux:table.columns>
                        <flux:table.column>
                            User
                        </flux:table.column>

                        <flux:table.column>
                            Email
                        </flux:table.column>

                        <flux:table.column>
                            Role
                        </flux:table.column>

                        <flux:table.column>
                            Archived
                        </flux:table.column>

                        <flux:table.column>
                            Actions
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->archivedUsers as $archivedUser)
                            <flux:table.row :key="'archived-user-'.$archivedUser->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar
                                            size="sm"
                                            :src="$archivedUser->avatar_url"
                                            :name="$archivedUser->name"
                                        />

                                        <div>
                                            <div class="font-medium">
                                                {{ $archivedUser->name }}
                                            </div>

                                            <div class="text-xs text-zinc-500">
                                                #{{ $archivedUser->id }}
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $archivedUser->email }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge
                                        size="sm"
                                        :color="match ($archivedUser->user_type) {
                                            'super_admin' => 'purple',
                                            'admin' => 'blue',
                                            default => 'zinc',
                                        }"
                                    >
                                        {{ $archivedUser->roleLabel() }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    <div>
                                        {{ $archivedUser->deleted_at?->format('M d, Y') ?? 'N/A' }}
                                    </div>

                                    @if ($archivedUser->deleted_at)
                                        <div class="text-xs text-zinc-500">
                                            {{ $archivedUser->deleted_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="arrow-uturn-left"
                                            wire:click="restoreUser({{ $archivedUser->id }})"
                                            wire:confirm="Restore this user account?"
                                        >
                                            Restore
                                        </flux:button>

                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            icon="trash"
                                            wire:click="forceDeleteUser({{ $archivedUser->id }})"
                                            wire:confirm="This action cannot be undone. Permanently delete this user?"
                                        >
                                            Delete
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell
                                    colspan="5"
                                    class="py-10 text-center"
                                >
                                    <div class="space-y-2">
                                        <flux:icon.archive-box
                                            class="mx-auto size-8 text-zinc-400"
                                        />

                                        <p class="text-sm text-zinc-500">
                                            No archived users found.
                                        </p>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="flex justify-end">
                <flux:button
                    wire:click="$set('showArchivedModal', false)"
                    variant="ghost"
                >
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>
