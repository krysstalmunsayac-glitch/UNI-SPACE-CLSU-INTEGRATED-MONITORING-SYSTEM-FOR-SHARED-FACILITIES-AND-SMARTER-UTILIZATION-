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
        <flux:sidebar
            sticky
            collapsible
            class="navigation-typeface w-64 overflow-x-hidden border-r border-zinc-300 bg-zinc-50/95 shadow-[0_0_0_1px_rgba(0,0,0,0.03)] dark:border-zinc-700 dark:bg-zinc-900 lg:w-72"
        >
            <div class="admin-sidebar-brand-row flex h-20 w-full items-center gap-2 px-2 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-0">
                <flux:sidebar.toggle
                    class="admin-sidebar-toggle hidden lg:inline-flex"
                    icon="bars-2"
                    aria-label="Open or close navigation menu"
                    title="Open or close navigation"
                />
                <flux:sidebar.toggle class="admin-sidebar-toggle lg:hidden" icon="x-mark" />

                <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center justify-center overflow-hidden in-data-flux-sidebar-collapsed-desktop:hidden" wire:navigate>
                    <x-app-logo />
                </a>
            </div>

            <flux:sidebar.nav class="px-2 in-data-flux-sidebar-collapsed-desktop:items-center in-data-flux-sidebar-collapsed-desktop:px-0">
                <flux:sidebar.group
                    heading="Platform"
                    icon="squares-2x2"
                    expandable
                    @class(['sidebar-section-current' => request()->routeIs('dashboard.superadmin', 'dashboard.officeadmin', 'dashboard', 'UserManagement*', 'ReportManagement')])
                >
                    @if (auth()->user()->hasrole('super_admin'))
                        <flux:sidebar.item icon="home" :href="route('dashboard.superadmin')" :current="request()->routeIs('dashboard.superadmin')" >Dashboard</flux:sidebar.item>
                        <flux:sidebar.item icon="user" :href="route('UserManagement')" :current="request()->routeIs('UserManagement*')" wire:navigate>User Management</flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('ReportManagement')" :current="request()->routeIs('ReportManagement')" wire:navigate>Report Management</flux:sidebar.item>
                    @elseif (auth()->user()->hasrole('admin'))
                        <flux:sidebar.item icon="home" :href="route('dashboard.officeadmin')" :current="request()->routeIs('dashboard.officeadmin')" wire:navigate>Dashboard</flux:sidebar.item>
                    @else
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Dashboard</flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if (auth()->user()->hasrole('super_admin') || auth()->user()->hasrole('admin'))
                    <flux:sidebar.group
                        heading="Service"
                        icon="wrench-screwdriver"
                        expandable
                        @class(['sidebar-section-current' =>
                            (request()->routeIs('Facility*', 'Amenities', 'Request', 'Schedule*', 'Feedback*'))
                            && ! request()->boolean('archive')
                        ])
                    >
                        @if (auth()->user()->hasrole('super_admin'))
                        <flux:sidebar.item icon="building-office" :href="route('Facility')" :current="request()->routeIs('Facility*') && ! request()->boolean('archive')" wire:navigate>Facility</flux:sidebar.item>
                        <flux:sidebar.item icon="rectangle-stack" :href="route('Amenities')" :current="request()->routeIs('Amenities*') && ! request()->boolean('archive')" wire:navigate>Amenities</flux:sidebar.item>
                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('Request')"
                            :current="request()->routeIs('Request') && ! request()->boolean('archive')"
                            @class(['sidebar-active-link' => request()->routeIs('Request') && ! request()->boolean('archive')])
                            wire:navigate
                        >Request</flux:sidebar.item>
                        <flux:sidebar.item icon="calendar" :href="route('Schedule')" :current="request()->routeIs('Schedule*')">Schedule</flux:sidebar.item>
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('Feedback')" :current="request()->routeIs('Feedback*')" wire:navigate>Feedback</flux:sidebar.item>
                    @elseif (auth()->user()->hasrole('admin'))
                        <flux:sidebar.item icon="building-office" :href="route('Facility')" :current="request()->routeIs('Facility*') && ! request()->boolean('archive')" wire:navigate>Facility</flux:sidebar.item>
                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('Request')"
                            :current="request()->routeIs('Request') && ! request()->boolean('archive')"
                            @class(['sidebar-active-link' => request()->routeIs('Request') && ! request()->boolean('archive')])
                            wire:navigate
                        >Request</flux:sidebar.item>
                        <flux:sidebar.item icon="calendar" :href="route('Schedule')" :current="request()->routeIs('Schedule*')" >Schedule</flux:sidebar.item>
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('Feedback')" :current="request()->routeIs('Feedback*')" wire:navigate>Feedback</flux:sidebar.item>
                    @endif
                    </flux:sidebar.group>

                    <flux:sidebar.group
                        heading="Archives"
                        icon="archive-box"
                        expandable
                        @class(['sidebar-section-current' => request()->boolean('archive') || request()->routeIs('Archived')])
                    >
                        @if (auth()->user()->hasrole('super_admin'))
                            <flux:sidebar.item icon="building-office" :href="route('Facility.SuperAdmin', ['archive' => 1])" :current="request()->routeIs('Facility.SuperAdmin') && request()->boolean('archive')" wire:navigate>Facilities</flux:sidebar.item>
                        @endif
                        <flux:sidebar.item icon="rectangle-stack" :href="route('Amenities', ['archive' => 1])" :current="request()->routeIs('Amenities') && request()->boolean('archive')" wire:navigate>Amenities</flux:sidebar.item>
                        <flux:sidebar.item icon="archive-box" :href="route('Request', ['archive' => 1])" :current="request()->routeIs('Request') && request()->boolean('archive')" wire:navigate>Requests</flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

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
            <flux:sidebar.toggle class="admin-sidebar-toggle lg:hidden" icon="bars-2" inset="left" />

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
        @include('partials.site-auto-refresh')
    </body>
</html>
