@auth
    @php
        $notificationUser = auth()->user();
        $unreadNotificationCount = $notificationUser->unreadNotifications()->count();
        $recentNotifications = $notificationUser->notifications()->latest()->limit(10)->get();
        $notificationDestination = match ($notificationUser->user_type) {
            'user' => route('dashboard').'#requests',
            'admin', 'super_admin' => route('Request'),
            default => route('dashboard'),
        };
    @endphp

    <flux:dropdown position="bottom" align="end" x-data="{ unread: {{ $unreadNotificationCount }} }">
        <button
            type="button"
            class="relative inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 transition hover:border-emerald-600! hover:bg-emerald-100! hover:text-emerald-800! dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-400! dark:hover:bg-emerald-500/25! dark:hover:text-emerald-200!"
            aria-label="Notifications{{ $unreadNotificationCount ? ' ('.$unreadNotificationCount.' unread)' : '' }}"
            title="Notifications"
            x-on:click="if (unread > 0) { fetch('{{ route('notifications.read') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(() => unread = 0) }"
        >
            <flux:icon.bell class="size-5" />

            <span
                x-cloak
                x-show="unread > 0"
                x-text="unread > 99 ? '99+' : unread"
                class="absolute right-0.5 top-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-black leading-4 text-white"
            ></span>
        </button>

        <flux:menu class="w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl p-0! shadow-2xl">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div>
                    <p class="font-bold text-zinc-950 dark:text-white">Notifications</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><span x-text="unread"></span> unread</p>
                </div>
                <flux:icon.bell class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>

            <div class="max-h-80 overflow-y-auto overscroll-contain">
                @forelse ($recentNotifications as $notification)
                    <a href="{{ $notificationDestination }}" class="group relative block border-b border-zinc-100 px-4 py-3 transition hover:bg-emerald-100! dark:border-zinc-800 dark:hover:bg-emerald-500/25!">
                        @if (is_null($notification->read_at))
                            <span x-show="unread > 0" class="absolute right-3 top-4 size-2 rounded-full bg-emerald-500" aria-label="Unread"></span>
                        @endif

                        <p class="pr-5 text-sm font-semibold leading-5 text-zinc-900 transition group-hover:text-emerald-900! dark:text-white dark:group-hover:text-emerald-100!">
                            {{ $notification->data['message'] ?? 'You have a new notification.' }}
                        </p>
                        @if (! empty($notification->data['facility']))
                            <p class="mt-1 truncate text-xs text-zinc-600 transition group-hover:text-emerald-800! dark:text-zinc-300 dark:group-hover:text-emerald-200!">
                                {{ $notification->data['facility'] }}
                            </p>
                        @endif
                        @if (! empty($notification->data['rejection_reason']))
                            <p class="mt-1 line-clamp-2 text-xs text-red-600 dark:text-red-300">
                                Reason: {{ $notification->data['rejection_reason'] }}
                            </p>
                        @endif
                        <p class="mt-1 text-[11px] text-zinc-400 transition group-hover:text-emerald-700! dark:group-hover:text-emerald-300!">{{ $notification->created_at->diffForHumans() }}</p>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <flux:icon.bell-slash class="mx-auto size-7 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No notifications yet.</p>
                    </div>
                @endforelse
            </div>

        </flux:menu>
    </flux:dropdown>
@endauth
