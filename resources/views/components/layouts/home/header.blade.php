<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white font-sans text-[#1e6031] antialiased dark:bg-zinc-950 dark:text-zinc-100">
    @php
        $navLink = 'border-b-2 border-transparent py-5 transition hover:border-emerald-700 hover:text-emerald-700 dark:hover:border-emerald-300 dark:hover:text-emerald-300';
        $navActive = 'border-emerald-700 text-emerald-700 dark:border-emerald-300 dark:text-emerald-300';
        $mobileNavLink = 'transition hover:text-emerald-700 dark:hover:text-emerald-300';
        $mobileNavActive = 'text-emerald-700 dark:text-emerald-300';
        $isEndUser = auth()->check() && auth()->user()->hasrole('user');
        $profileRoute = $isEndUser ? route('profile.external') : route('settings.profile');
        $homeRoute = $isEndUser ? route('dashboard') : route('home');
        $aboutRoute = $homeRoute;
    @endphp

    <header
        x-data="{
            mobileMenuOpen: false,
            activeSection: window.location.hash.replace('#', '') || 'home',
            setActive(section) {
                this.activeSection = section || 'home';
            }
        }"
        x-init="$nextTick(() => {
            const sectionIds = ['about', 'facilities', 'calendar', 'map', 'help'];
            const observer = new IntersectionObserver((entries) => {
                const visible = entries.filter(entry => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                if (visible) setActive(visible.target.id);
            }, { rootMargin: '-20% 0px -65% 0px', threshold: [0.01, 0.25, 0.5] });
            sectionIds.forEach(id => { const section = document.getElementById(id); if (section) observer.observe(section); });
            window.addEventListener('scroll', () => { if (window.scrollY < 160) setActive('home'); }, { passive: true });
            window.addEventListener('hashchange', () => setActive(window.location.hash.replace('#', '') || 'home'));
        })"
        x-on:keydown.escape.window="mobileMenuOpen = false"
        class="sticky top-0 z-[2000] border-b border-emerald-900/10 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-zinc-950/95"
    >
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-20 sm:px-6 lg:px-8">
            <a href="{{ $homeRoute }}#home" class="flex h-16 shrink-0 items-center justify-center sm:h-20" aria-label="SIEL SPACE home">
                <img src="{{ asset('images/silesyu-space-logo.png') }}" alt="SIEL SPACE" class="h-10 w-auto object-contain sm:h-14">
            </a>

            <nav class="navigation-typeface hidden items-center gap-6 text-sm font-semibold text-emerald-950 dark:text-zinc-100 lg:flex">
                @auth
                    @if ($isEndUser)
                        <a href="{{ $homeRoute }}#home" class="{{ $navLink }}" x-on:click="setActive('home')" x-bind:class="activeSection === 'home' ? @js($navActive) : ''">Home</a>
                        <a href="{{ $aboutRoute }}#about" class="{{ $navLink }}" x-on:click="setActive('about')" x-bind:class="activeSection === 'about' ? @js($navActive) : ''">About</a>
                        <a href="{{ $homeRoute }}#facilities" class="{{ $navLink }}" x-on:click="setActive('facilities')" x-bind:class="activeSection === 'facilities' ? @js($navActive) : ''">Facilities</a>
                        <a href="{{ $homeRoute }}#calendar" class="{{ $navLink }}" x-on:click="setActive('calendar')" x-bind:class="activeSection === 'calendar' ? @js($navActive) : ''">Calendar</a>
                        <a href="{{ $homeRoute }}#map" class="{{ $navLink }}" x-on:click="setActive('map')" x-bind:class="activeSection === 'map' ? @js($navActive) : ''">Map</a>
                        <a href="{{ $homeRoute }}#help" class="{{ $navLink }}" x-on:click="setActive('help')" x-bind:class="activeSection === 'help' ? @js($navActive) : ''">Help</a>
                    @else
                        <a href="{{ route('dashboard') }}" @class([$navLink, $navActive => request()->routeIs('dashboard*')])>Dashboard</a>
                        <a href="{{ route('settings.profile') }}" @class([$navLink, $navActive => request()->routeIs('settings.*')])>Profile</a>
                    @endif
                @else
                    <a href="{{ route('home') }}#home" class="{{ $navLink }}" x-on:click="setActive('home')" x-bind:class="activeSection === 'home' ? @js($navActive) : ''">Home</a>
                    <a href="{{ route('home') }}#about" class="{{ $navLink }}" x-on:click="setActive('about')" x-bind:class="activeSection === 'about' ? @js($navActive) : ''">About</a>
                    <a href="{{ route('home') }}#facilities" class="{{ $navLink }}" x-on:click="setActive('facilities')" x-bind:class="activeSection === 'facilities' ? @js($navActive) : ''">Facilities</a>
                    <a href="{{ route('home') }}#calendar" class="{{ $navLink }}" x-on:click="setActive('calendar')" x-bind:class="activeSection === 'calendar' ? @js($navActive) : ''">Calendar</a>
                    <a href="{{ route('home') }}#map" class="{{ $navLink }}" x-on:click="setActive('map')" x-bind:class="activeSection === 'map' ? @js($navActive) : ''">Map</a>
                    <a href="{{ route('home') }}#help" class="{{ $navLink }}" x-on:click="setActive('help')" x-bind:class="activeSection === 'help' ? @js($navActive) : ''">Help</a>
                @endauth
            </nav>

            <div class="flex items-center gap-3">
                @guest
                    <a href="{{ route('login') }}" class="hidden rounded-xl border border-emerald-700 px-5 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-700 hover:text-white dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-emerald-300 dark:hover:text-emerald-950 lg:inline-flex">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="hidden rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950 dark:hover:bg-emerald-300 lg:inline-flex">
                        Sign Up
                    </a>
                @else
                    <div class="hidden items-center gap-3 lg:flex">
                        <x-notification-button />
                        <a href="{{ $isEndUser ? $homeRoute.'#requests' : route('dashboard') }}" class="rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950">
                            {{ $isEndUser ? 'My Requests' : 'Dashboard' }}
                        </a>
                        <x-ui::dropdown position="bottom" align="end">
                        <x-ui::profile
                            :avatar="auth()->user()->avatar_url"
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                            icon-trailing="chevron-down"
                        />

                        <x-ui::menu class="w-[310px] rounded-2xl! border-[#dce4df]! p-2.5! shadow-[0_18px_50px_rgba(15,52,35,0.16)]!">
                            <x-ui::menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-3 border-b border-zinc-100 px-2 py-2.5 text-left text-sm dark:border-zinc-700">
                                        <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                            @if (auth()->user()->avatar_url)
                                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-100">
                                                    {{ auth()->user()->initials() }}
                                                </span>
                                            @endif
                                        </span>
                                        <div class="grid flex-1 text-left text-sm leading-tight">
                                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                            <span class="truncate text-xs text-slate-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </x-ui::menu.radio.group>

                            <x-ui::menu.radio.group>
                                <x-ui::menu.item :href="$profileRoute" icon="profile" class="min-h-12 rounded-xl font-semibold text-slate-700 dark:text-zinc-100" wire:navigate>Profile settings</x-ui::menu.item>
                                @if ($isEndUser)
                                    <x-ui::menu.item :href="route('profile.external.password')" icon="key" class="min-h-12 rounded-xl font-semibold text-slate-700 dark:text-zinc-100" wire:navigate>Change password</x-ui::menu.item>
                                @endif
                            </x-ui::menu.radio.group>

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <x-ui::menu.item as="button" type="submit" icon="logout" class="min-h-12 w-full rounded-xl font-semibold text-slate-700 dark:text-zinc-100">
                                    {{ __('Log out') }}
                                </x-ui::menu.item>
                            </form>
                        </x-ui::menu>
                        </x-ui::dropdown>
                    </div>
                @endguest

                <button
                    type="button"
                    class="inline-flex size-10 items-center justify-center rounded-xl border border-emerald-900/10 text-emerald-950 transition hover:bg-emerald-50 dark:border-white/10 dark:text-white dark:hover:bg-zinc-800 lg:hidden"
                    x-on:click="mobileMenuOpen = ! mobileMenuOpen"
                    x-bind:aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-navigation"
                    aria-label="Toggle navigation"
                >
                    <svg x-show="! mobileMenuOpen" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-cloak x-show="mobileMenuOpen" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <nav
            id="mobile-navigation"
            x-cloak
            x-show="mobileMenuOpen"
            x-transition.opacity.duration.150ms
            class="navigation-typeface border-t border-emerald-900/10 px-4 py-4 text-sm font-semibold text-emerald-950 shadow-lg dark:border-white/10 dark:text-zinc-100 lg:hidden"
        >
            <div class="grid gap-1">
            @auth
                @if ($isEndUser)
                    <a href="{{ $homeRoute }}#home" x-on:click="mobileMenuOpen = false; setActive('home')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'home' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Home</a>
                    <a href="{{ $aboutRoute }}#about" x-on:click="mobileMenuOpen = false; setActive('about')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'about' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">About</a>
                    <a href="{{ $homeRoute }}#facilities" x-on:click="mobileMenuOpen = false; setActive('facilities')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'facilities' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Facilities</a>
                    <a href="{{ $homeRoute }}#calendar" x-on:click="mobileMenuOpen = false; setActive('calendar')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'calendar' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Calendar</a>
                    <a href="{{ $homeRoute }}#map" x-on:click="mobileMenuOpen = false; setActive('map')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'map' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Map</a>
                    <a href="{{ $homeRoute }}#help" x-on:click="mobileMenuOpen = false; setActive('help')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'help' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Help</a>
                    <a href="{{ $homeRoute }}#requests" x-on:click="mobileMenuOpen = false; setActive('requests')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'requests' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">My Requests / Waiting List</a>

                    <div class="mt-2 flex items-center justify-between border-t border-emerald-900/10 pt-3 dark:border-white/10">
                        <x-notification-button />
                        <x-ui::dropdown position="bottom" align="end">
                            <button type="button" class="flex min-w-0 items-center gap-2 rounded-xl px-2 py-1.5 transition hover:bg-emerald-50 dark:hover:bg-zinc-800" aria-label="Open profile menu">
                                <x-ui::avatar :src="auth()->user()->avatar_url" :name="auth()->user()->name" :initials="auth()->user()->initials()" size="sm" />
                                <span class="max-w-36 truncate">{{ auth()->user()->name }}</span>
                            </button>

                            <x-ui::menu class="w-[min(310px,calc(100vw-2rem))] rounded-2xl! border-[#dce4df]! p-2.5! shadow-[0_18px_50px_rgba(15,52,35,0.16)]!">
                            <x-ui::menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-3 border-b border-zinc-100 px-2 py-2.5 text-left text-sm dark:border-zinc-700">
                                        <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                            @if (auth()->user()->avatar_url)
                                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-100">
                                                    {{ auth()->user()->initials() }}
                                                </span>
                                            @endif
                                        </span>
                                        <div class="grid flex-1 text-left text-sm leading-tight">
                                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                            <span class="truncate text-xs text-slate-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </x-ui::menu.radio.group>

                            <x-ui::menu.radio.group>
                                <x-ui::menu.item :href="$profileRoute" icon="profile" class="min-h-12 rounded-xl font-semibold text-slate-700 dark:text-zinc-100" wire:navigate>Profile settings</x-ui::menu.item>
                                <x-ui::menu.item :href="route('profile.external.password')" icon="key" class="min-h-12 rounded-xl font-semibold text-slate-700 dark:text-zinc-100" wire:navigate>Change password</x-ui::menu.item>
                            </x-ui::menu.radio.group>

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <x-ui::menu.item as="button" type="submit" icon="logout" class="min-h-12 w-full rounded-xl font-semibold text-slate-700 dark:text-zinc-100">
                                    {{ __('Log out') }}
                                </x-ui::menu.item>
                            </form>
                            </x-ui::menu>
                        </x-ui::dropdown>
                    </div>
                @else
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}">Dashboard</a>
                    <a href="{{ route('settings.profile') }}" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="font-semibold transition hover:text-emerald-700 dark:hover:text-emerald-300">Logout</button>
                    </form>
                @endif
            @else
                <a href="{{ route('home') }}#home" x-on:click="mobileMenuOpen = false; setActive('home')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'home' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Home</a>
                <a href="{{ route('home') }}#about" x-on:click="mobileMenuOpen = false; setActive('about')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'about' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">About</a>
                <a href="{{ route('home') }}#facilities" x-on:click="mobileMenuOpen = false; setActive('facilities')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'facilities' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Facilities</a>
                <a href="{{ route('home') }}#calendar" x-on:click="mobileMenuOpen = false; setActive('calendar')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'calendar' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Calendar</a>
                <a href="{{ route('home') }}#map" x-on:click="mobileMenuOpen = false; setActive('map')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'map' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Map</a>
                <a href="{{ route('home') }}#help" x-on:click="mobileMenuOpen = false; setActive('help')" class="rounded-lg px-3 py-2.5 {{ $mobileNavLink }}" x-bind:class="activeSection === 'help' ? @js('bg-emerald-50 '.$mobileNavActive) : ''">Help</a>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <a href="{{ route('login') }}" class="rounded-xl border border-emerald-700 px-4 py-3 text-center font-bold text-emerald-800 transition hover:bg-emerald-700 hover:text-white dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-emerald-300 dark:hover:text-emerald-950">Sign In</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-emerald-700 px-4 py-3 text-center font-bold text-white transition hover:bg-emerald-800 dark:bg-emerald-400 dark:text-emerald-950 dark:hover:bg-emerald-300">Sign Up</a>
                </div>
            @endauth
            </div>
        </nav>
    </header>

    <main class="pt-4">
        {{ $slot }}
    </main>

    <footer class="border-t border-emerald-900/10 bg-white py-8 dark:border-white/10 dark:bg-zinc-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 text-sm text-emerald-900/70 dark:text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} SIEL SPACE. Central Luzon State University.</p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('home') }}#home" class="hover:text-emerald-700 dark:hover:text-emerald-300">Home</a>
                <a href="{{ route('home') }}#about" class="hover:text-emerald-700 dark:hover:text-emerald-300">About</a>
                <a href="{{ route('home') }}#facilities" class="hover:text-emerald-700 dark:hover:text-emerald-300">Facilities</a>
                <a href="{{ route('home') }}#calendar" class="hover:text-emerald-700 dark:hover:text-emerald-300">Calendar</a>
            </div>
        </div>
    </footer>

    @include('partials.confirmation-dialog')

    <script>
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href*="#"]');
            if (!link) return;

            const url = new URL(link.href, window.location.href);
            const isCurrentPage = url.origin === window.location.origin
                && url.pathname === window.location.pathname
                && url.search === window.location.search;
            const target = url.hash ? document.querySelector(url.hash) : null;

            if (!isCurrentPage || !target) return;

            event.preventDefault();
            target.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'start',
            });
            window.history.pushState(null, '', `${url.pathname}${url.search}${url.hash}`);
        });
    </script>

    @stack('scripts')
    @livewireScripts
    @include('partials.site-auto-refresh')
</body>
</html>
