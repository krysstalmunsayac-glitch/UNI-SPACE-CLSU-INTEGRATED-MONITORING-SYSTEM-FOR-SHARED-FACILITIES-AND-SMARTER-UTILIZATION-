    <x-ui::card>
        <div class="mb-4">
            <x-ui::heading size="lg">Feedback Entries</x-ui::heading>
        </div>

        @php($feedbacks = $this->feedbacks)

        <x-ui::table :paginate="$feedbacks">
            <x-ui::table.columns>
                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'User_ID'"
                    :direction="$sortDirection"
                    wire:click="sort('User_ID')"
                >
                    User
                </x-ui::table.column>

                <x-ui::table.column>Facility</x-ui::table.column>
                <x-ui::table.column>Rating</x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'Comment'"
                    :direction="$sortDirection"
                    wire:click="sort('Comment')"
                >
                    Comment
                </x-ui::table.column>

                <x-ui::table.column
                    sortable
                    :sorted="$sortBy === 'Created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('Created_at')"
                >
                    Submitted
                </x-ui::table.column>

                <x-ui::table.column>Actions</x-ui::table.column>
            </x-ui::table.columns>

            <x-ui::table.rows>
                @forelse ($feedbacks as $feedback)
                    <x-ui::table.row :key="$feedback->getKey()">
                        <x-ui::table.cell>
                            <div class="flex flex-col">
                                <div class="font-medium">{{ $feedback->user?->name ?? 'Anonymous' }}</div>
                                <div class="text-xs text-zinc-500">#{{ $feedback->getKey() }}</div>
                            </div>
                        </x-ui::table.cell>

                        <x-ui::table.cell>{{ $feedback->facility?->Facility_Name ?? '—' }}</x-ui::table.cell>

                        <x-ui::table.cell>
                            <span class="whitespace-nowrap font-semibold text-amber-500">
                                {{ str_repeat('★', $feedback->Rating ?? 0) }}<span class="text-zinc-300">{{ str_repeat('★', 5 - ($feedback->Rating ?? 0)) }}</span>
                            </span>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <div class="line-clamp-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $feedback->Comment ?? '—' }}</div>
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <div class="font-medium">{{ $feedback->Created_at?->format('M d, Y') ?? '—' }}</div>
                            @if ($feedback->Created_at)
                                <div class="text-xs text-zinc-500">{{ $feedback->Created_at->format('h:i A') }}</div>
                            @endif
                        </x-ui::table.cell>

                        <x-ui::table.cell>
                            <x-ui::dropdown :position="$loop->remaining < 2 ? 'top' : 'bottom'" align="end">
                                <x-ui::button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Actions for feedback #{{ $feedback->getKey() }}" />
                                <x-ui::menu>
                                    <x-ui::menu.item
                                        icon="trash"
                                        class="text-red-600"
                                        wire:click="delete({{ $feedback->getKey() }})"
                                        data-ui-confirm="Are you sure you want to delete this feedback?"
                                    >
                                        Delete
                                    </x-ui::menu.item>
                                </x-ui::menu>
                            </x-ui::dropdown>
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @empty
                    <x-ui::table.row>
                        <x-ui::table.cell colspan="6" class="text-center py-8">
                            No feedback found.
                        </x-ui::table.cell>
                    </x-ui::table.row>
                @endforelse
            </x-ui::table.rows>
        </x-ui::table>
    </x-ui::card>
