<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white font-sans text-[#14532d] antialiased dark:bg-zinc-950 dark:text-zinc-100">
    @php
        $navLink = 'border-b-2 border-transparent py-7 transition hover:border-emerald-700 hover:text-emerald-700 dark:hover:border-emerald-300 dark:hover:text-emerald-300';
        $navActive = 'border-emerald-700 text-emerald-700 dark:border-emerald-300 dark:text-emerald-300';
        $mobileNavLink = 'transition hover:text-emerald-700 dark:hover:text-emerald-300';
        $mobileNavActive = 'text-emerald-700 dark:text-emerald-300';
        $isEndUser = auth()->check() && auth()->user()->hasrole('user');
        $profileRoute = $isEndUser ? route('profile.external') : route('settings.profile');
    @endphp

    <header class="sticky top-0 z-[2000] border-b border-emerald-900/10 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-zinc-950/95">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="UNI Space home">
                <img src="{{ asset('images/Logo_Black.png') }}" alt="CLSU" class="h-12 w-auto object-contain dark:hidden">
                <img src="{{ asset('images/Logo_white.png') }}" alt="CLSU" class="hidden h-12 w-auto object-contain dark:block">
                <img src="{{ asset('images/uni-space-logo.png') }}" alt="UNI Space" class="h-20 w-auto rounded-md object-contain">
            </a>

            <nav class="navigation-typeface hidden items-center gap-8 text-base font-semibold text-emerald-950 dark:text-zinc-100 lg:flex">
                @auth
                    @if ($isEndUser)
                        <a href="{{ route('home') }}" @class([$navLink, $navActive => request()->routeIs('home')])>Home</a>
                        <a href="{{ route('home') }}#about" class="{{ $navLink }}">About</a>
                        <a href="{{ route('home') }}#facilities" class="{{ $navLink }}">Facilities</a>
                        <a href="{{ route('home') }}#calendar" class="{{ $navLink }}">Calendar</a>
                        <a href="{{ route('home') }}#map" class="{{ $navLink }}">Map</a>
                        <a href="{{ route('home') }}#help" class="{{ $navLink }}">Help</a>
                    @else
                        <a href="{{ route('dashboard') }}" @class([$navLink, $navActive => request()->routeIs('dashboard*')])>Dashboard</a>
                        <a href="{{ route('settings.profile') }}" @class([$navLink, $navActive => request()->routeIs('settings.*')])>Profile</a>
                    @endif
                @else
                    <a href="{{ route('home') }}" @class([$navLink, $navActive => request()->routeIs('home')])>Home</a>
                    <a href="{{ route('home') }}#about" class="{{ $navLink }}">About</a>
                    <a href="{{ route('home') }}#facilities" class="{{ $navLink }}">Facilities</a>
                    <a href="{{ route('home') }}#calendar" class="{{ $navLink }}">Calendar</a>
                    <a href="{{ route('home') }}#map" class="{{ $navLink }}">Map</a>
                    <a href="{{ route('home') }}#help" class="{{ $navLink }}">Help</a>
                @endauth
            </nav>

            <div class="flex items-center gap-3">
                @guest
                    <a href="{{ route('login') }}" class="rounded-xl border border-emerald-700 px-5 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-700 hover:text-white dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-emerald-300 dark:hover:text-emerald-950">
                        Sign In
                    </a>
                @else
                    <x-notification-button />
                    <a href="{{ $isEndUser ? route('waiting.list') : route('dashboard') }}" class="rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950">
                        Requests
                    </a>
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
                @endguest
            </div>
        </div>

        <nav class="navigation-typeface flex gap-5 overflow-x-auto border-t border-emerald-900/10 px-4 py-3 text-sm font-semibold text-emerald-950 dark:border-white/10 dark:text-zinc-100 lg:hidden">
            @auth
                @if ($isEndUser)
                    <a href="{{ route('home') }}" @class([$mobileNavLink, $mobileNavActive => request()->routeIs('home')])>Home</a>
                    <a href="{{ route('home') }}#about" class="{{ $mobileNavLink }}">About</a>
                    <a href="{{ route('home') }}#facilities" class="{{ $mobileNavLink }}">Facilities</a>
                    <a href="{{ route('home') }}#calendar" class="{{ $mobileNavLink }}">Calendar</a>
                    <a href="{{ route('home') }}#map" class="{{ $mobileNavLink }}">Map</a>
                    <a href="{{ route('home') }}#help" class="{{ $mobileNavLink }}">Help</a>
                    <a href="{{ route('waiting.list') }}" @class([$mobileNavLink, $mobileNavActive => request()->routeIs('waiting.list')])>Requests</a>
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
                @else
                    <a href="{{ route('dashboard') }}" @class([$mobileNavLink, $mobileNavActive => request()->routeIs('dashboard*')])>Dashboard</a>
                    <a href="{{ route('settings.profile') }}" @class([$mobileNavLink, $mobileNavActive => request()->routeIs('settings.*')])>Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="font-semibold transition hover:text-emerald-700 dark:hover:text-emerald-300">Logout</button>
                    </form>
                @endif
            @else
                <a href="{{ route('home') }}" @class([$mobileNavLink, $mobileNavActive => request()->routeIs('home')])>Home</a>
                <a href="{{ route('home') }}#about" class="{{ $mobileNavLink }}">About</a>
                <a href="{{ route('home') }}#facilities" class="{{ $mobileNavLink }}">Facilities</a>
                <a href="{{ route('home') }}#calendar" class="{{ $mobileNavLink }}">Calendar</a>
                <a href="{{ route('home') }}#map" class="{{ $mobileNavLink }}">Map</a>
                <a href="{{ route('home') }}#help" class="{{ $mobileNavLink }}">Help</a>
            @endauth
        </nav>
    </header>

    <main class="pt-4">
        {{ $slot }}
    </main>

    <footer class="border-t border-emerald-900/10 bg-white py-8 dark:border-white/10 dark:bg-zinc-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 text-sm text-emerald-900/70 dark:text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} UNI Space. Central Luzon State University.</p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('home') }}" class="hover:text-emerald-700 dark:hover:text-emerald-300">Home</a>
                <a href="{{ route('home') }}#about" class="hover:text-emerald-700 dark:hover:text-emerald-300">About</a>
                <a href="{{ route('home') }}#facilities" class="hover:text-emerald-700 dark:hover:text-emerald-300">Facilities</a>
                <a href="{{ route('home') }}#calendar" class="hover:text-emerald-700 dark:hover:text-emerald-300">Calendar</a>
            </div>
        </div>
    </footer>

    @fluxScripts
    @stack('scripts')
</body>
</html>
