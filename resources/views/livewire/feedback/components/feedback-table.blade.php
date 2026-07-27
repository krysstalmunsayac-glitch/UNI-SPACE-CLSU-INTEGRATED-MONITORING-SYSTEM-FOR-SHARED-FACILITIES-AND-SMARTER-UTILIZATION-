    <flux:card>
        <div class="mb-4">
            <flux:heading size="lg">Feedback Entries</flux:heading>
        </div>

        @php($feedbacks = $this->feedbacks())

        <flux:table :paginate="$feedbacks">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'User_ID'"
                    :direction="$sortDirection"
                    wire:click="sort('User_ID')"
                >
                    User
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'Comment'"
                    :direction="$sortDirection"
                    wire:click="sort('Comment')"
                >
                    Comment
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'Created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('Created_at')"
                >
                    Submitted
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($feedbacks as $feedback)
                    <flux:table.row :key="$feedback->getKey()">
                        <flux:table.cell>
                            <div class="flex flex-col">
                                <div class="font-medium">{{ $feedback->user?->name ?? 'Anonymous' }}</div>
                                <div class="text-xs text-zinc-500">#{{ $feedback->getKey() }}</div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="line-clamp-2 text-sm text-slate-700 dark:text-slate-300">{{ $feedback->Comment ?? 'No comment provided.' }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $feedback->Created_at?->format('M d, Y H:i') ?? '—' }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="trash"
                                        class="text-red-600"
                                        wire:click="delete({{ $feedback->getKey() }})"
                                        wire:confirm="Are you sure you want to delete this feedback?"
                                    >
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8">
                            No feedback found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
