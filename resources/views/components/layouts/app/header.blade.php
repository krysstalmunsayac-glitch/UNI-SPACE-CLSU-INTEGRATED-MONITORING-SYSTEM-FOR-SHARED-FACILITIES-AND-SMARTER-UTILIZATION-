<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @stack('scripts')
    </head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    <x-ui::header container class="navigation-typeface sticky top-0 z-50 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <x-ui::sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <x-ui::brand href="{{ route('home') }}" logo="{{ asset('images/silesyu-space-logo.png') }}" name="SIEL SPACE" class="me-3" />
        <x-ui::navbar class="-mb-px max-lg:hidden">
            <x-ui::navbar.item icon="home" href="{{ route('home') }}">Home</x-ui::navbar.item>
            <x-ui::navbar.item icon="document-text" href="#">Facility</x-ui::navbar.item>
            <x-ui::navbar.item icon="calendar" >Calendar</x-ui::navbar.item>
            <x-ui::navbar.item icon="information-circle" href="{{ route('home') }}#about">About</x-ui::navbar.item>
            <x-ui::separator vertical variant="subtle" class="my-2"/>
        </x-ui::navbar>
        <x-ui::spacer />
        <x-ui::navbar class="me-4">
            <x-ui::input as="button" placeholder="Search..." icon="magnifying-glass" kbd="" input=""/>
        </x-ui::navbar>

        @guest
            <div class="flex items-center gap-2">
                <x-ui::button href="{{ route('login') }}" variant="ghost">
                    Login
                </x-ui::button>

                <x-ui::button href="{{ route('register') }}" variant="primary">
                    Register
                </x-ui::button>
            </div>
        @endguest

        @auth
            <div class="me-3">
                <x-notification-button />
            </div>

            <x-ui::dropdown position="bottom" align="end">
                <x-ui::profile
                    :avatar="auth()->user()->avatar_url"
                    name="{{ auth()->user()->name }}"
                    icon:trailing="chevron-down"
                    class="cursor-pointer"
                />

                <x-ui::menu>
                    <x-ui::menu.radio.group>
                        <div class="px-2 py-1.5">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</p>
                        </div>
                    </x-ui::menu.radio.group>

                    <x-ui::menu.separator />

                    <x-ui::menu.item icon="user" href="{{ route('settings.profile') }}">Profile</x-ui::menu.item>
                    <x-ui::menu.item icon="cog-6-tooth" href="{{ route('settings.profile') }}">Settings</x-ui::menu.item>

                    <x-ui::menu.separator />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui::menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                            Log Out
                        </x-ui::menu.item>
                    </form>
                </x-ui::menu>
            </x-ui::dropdown>
        @endauth
    </x-ui::header>

    <x-ui::sidebar sticky collapsible="mobile" class="navigation-typeface lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <x-ui::sidebar.header>
            <x-ui::brand href="{{ route('home') }}" logo="{{ asset('images/silesyu-space-logo.png') }}" name="SIEL SPACE" class="me-3" />
            <x-ui::sidebar.collapse class="in-data-ui-sidebar-on-desktop:not-in-data-ui-sidebar-collapsed-desktop:-mr-2" />
        </x-ui::sidebar.header>
        <x-ui::sidebar.nav>
            <x-ui::sidebar.item icon="home" href="{{ route('home') }}" current>Home</x-ui::sidebar.item>
            <x-ui::sidebar.item icon="building-office" href="#">Facility</x-ui::sidebar.item>
            <x-ui::sidebar.item icon="calendar" >Calendar</x-ui::sidebar.item>
            <x-ui::sidebar.group expandable heading="About" class="grid">
                <x-ui::sidebar.item href="{{ route('home') }}#about">About us</x-ui::sidebar.item>
            </x-ui::sidebar.group>
        </x-ui::sidebar.nav>

        @auth
            <x-ui::sidebar.spacer />
            <x-ui::sidebar.nav>
                <x-ui::sidebar.item icon="user" href="{{ route('settings.profile') }}">Profile</x-ui::sidebar.item>
                <x-ui::sidebar.item icon="cog-6-tooth" href="{{ route('settings.profile') }}">Settings</x-ui::sidebar.item>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui::sidebar.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Log Out</x-ui::sidebar.item>
                </form>
            </x-ui::sidebar.nav>
        @else
            <x-ui::sidebar.spacer />
        @endauth
    </x-ui::sidebar>


    {{ $slot }}
    @livewireScripts
    @include('partials.site-auto-refresh')
</body>
</html>
