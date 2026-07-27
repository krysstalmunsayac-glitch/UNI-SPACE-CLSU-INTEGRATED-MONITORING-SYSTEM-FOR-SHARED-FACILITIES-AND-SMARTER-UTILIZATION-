<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php
            $profileRoute = auth()->user()->hasrole('user')
                ? route('profile.external')
                : route('settings.profile');
        @endphp
        <flux:sidebar sticky stashable class="navigation-typeface w-72 border-r border-zinc-300 bg-zinc-50/95 shadow-[0_0_0_1px_rgba(0,0,0,0.03)] dark:border-zinc-700 dark:bg-zinc-900 lg:w-80">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="flex min-h-40 w-full items-center justify-center px-3 py-3" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline" class="px-2">
                <flux:navlist.group heading="Platform" class="grid">
                    @if (auth()->user()->hasrole('super_admin'))
                        <flux:navlist.item icon="home" :href="route('dashboard.superadmin')" :current="request()->routeIs('dashboard.superadmin')" >Dashboard</flux:navlist.item>
                        <flux:navlist.item icon="user" :href="route('UserManagement')" :current="request()->routeIs('UserManagement')" wire:navigate>User Management</flux:navlist.item>
                    @elseif (auth()->user()->hasrole('admin'))
                        <flux:navlist.item icon="home" :href="route('dashboard.officeadmin')" :current="request()->routeIs('dashboard.officeadmin')" wire:navigate>Dashboard</flux:navlist.item>
                    @else
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Dashboard</flux:navlist.item>
                    @endif
                </flux:navlist.group>

                @if (auth()->user()->hasrole('super_admin') || auth()->user()->hasrole('admin'))
                    <flux:navlist.group heading="Service" class="grid">
                        @if (auth()->user()->hasrole('super_admin'))
                        <flux:navlist.item icon="building-office" :href="route('Facility')" :current="request()->routeIs('Facility')" wire:navigate>Facility</flux:navlist.item>
                        <flux:navlist.item icon="rectangle-stack" :href="route('Amenities')" :current="request()->routeIs('Amenities')" wire:navigate>Amenities</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('Request')" :current="request()->routeIs('Request')" wire:navigate>Request</flux:navlist.item>
                        <flux:navlist.item icon="calendar" :href="route('Schedule')" :current="request()->routeIs('Schedule')">Schedule</flux:navlist.item>
                        <flux:navlist.item icon="chat-bubble-left-right" :href="route('Feedback')" :current="request()->routeIs('Feedback')" wire:navigate>Feedback</flux:navlist.item>
                    @elseif (auth()->user()->hasrole('admin'))
                        <flux:navlist.item icon="building-office" :href="route('Facility')" :current="request()->routeIs('Facility')" wire:navigate>Facility</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('Request')" :current="request()->routeIs('Request')" wire:navigate>Request</flux:navlist.item>
                        <flux:navlist.item icon="calendar" :href="route('Schedule')" :current="request()->routeIs('Schedule')" >Schedule</flux:navlist.item>
                        <flux:navlist.item icon="chat-bubble-left-right" :href="route('Feedback')" :current="request()->routeIs('Feedback')" wire:navigate>Feedback</flux:navlist.item>
                    @endif
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            @if (auth()->user()->hasrole('user'))
                <flux:navlist variant="outline" class="px-2 pb-4">
                    <flux:navlist.group heading="Profile" class="grid">
                        <flux:navlist.item icon="user" :href="$profileRoute" :current="request()->routeIs('settings.profile', 'profile.external')" wire:navigate>Profile</flux:navlist.item>
                        <flux:navlist.item icon="cog-6-tooth" :href="route('settings.appearance')" :current="request()->routeIs('settings.appearance')" wire:navigate>Appearance</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>
            @endif

        </flux:sidebar>

        {{-- Desktop Header (lg and above) --}}
        <flux:header class="navigation-typeface sticky top-0 z-50 hidden lg:flex border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:spacer />

            <div class="mr-3">
                <x-notification-button />
            </div>

            <flux:dropdown position="bottom" align="end">
                <flux:profile
                    :avatar="auth()->user()->avatar_url"
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    @if (auth()->user()->avatar_url)
                                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    @endif
                                </span>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/appearance" icon="user" wire:navigate>Appearance</flux:menu.item>
                        <flux:menu.item :href="$profileRoute" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Mobile Header --}}
        <flux:header class="navigation-typeface sticky top-0 z-50 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <div class="mr-3">
                <x-notification-button />
            </div>

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :avatar="auth()->user()->avatar_url"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    @if (auth()->user()->avatar_url)
                                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    @endif
                                </span>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/appearance" icon="user" wire:navigate>Appearance</flux:menu.item>
                        <flux:menu.item :href="$profileRoute" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @stack('scripts')
    </body>
</html>
