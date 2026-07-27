<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @stack('scripts')
    </head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    <flux:header container class="navigation-typeface sticky top-0 z-50 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:brand href="#" logo="{{ asset('images/Logo_Black.png') }}" name="Uni Space" class="dark:hidden me-3" />
        <flux:brand href="#" logo="{{ asset('images/Logo_white.png') }}" name="Uni Space" class="hidden dark:flex me-3" />
        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item icon="home" href="{{ route('home') }}">Home</flux:navbar.item>
            <flux:navbar.item icon="document-text" href="#">Facility</flux:navbar.item>
            <flux:navbar.item icon="calendar" >Calendar</flux:navbar.item>
            <flux:navbar.item icon="information-circle" href="{{ route('home') }}#about">About</flux:navbar.item>
            <flux:separator vertical variant="subtle" class="my-2"/>
        </flux:navbar>
        <flux:spacer />
        <flux:navbar class="me-4">
            <flux:input as="button" placeholder="Search..." icon="magnifying-glass" kbd="" input=""/>
        </flux:navbar>

        @guest
            <div class="flex items-center gap-2">
                <flux:button href="{{ route('login') }}" variant="ghost">
                    Login
                </flux:button>

                <flux:button href="{{ route('register') }}" variant="primary">
                    Register
                </flux:button>
            </div>
        @endguest

        @auth
            <div class="me-3">
                <x-notification-button />
            </div>

            <flux:dropdown position="bottom" align="end">
                <flux:profile
                    :avatar="auth()->user()->avatar_url"
                    name="{{ auth()->user()->name }}"
                    icon:trailing="chevron-down"
                    class="cursor-pointer"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="px-2 py-1.5">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</p>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item icon="user" href="{{ route('settings.profile') }}">Profile</flux:menu.item>
                    <flux:menu.item icon="cog-6-tooth" href="{{ route('settings.profile') }}">Settings</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                            Log Out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        @endauth
    </flux:header>

    <flux:sidebar sticky collapsible="mobile" class="navigation-typeface lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.header>
            <flux:brand href="#" logo="{{ asset('images/Logo_Black.png') }}" name="Uni Space" class="dark:hidden me-3" />
            <flux:brand href="#" logo="{{ asset('images/Logo_white.png') }}" name="Uni Space" class="hidden dark:flex me-3" />
            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="{{ route('home') }}" current>Home</flux:sidebar.item>
            <flux:sidebar.item icon="building-office" href="#">Facility</flux:sidebar.item>
            <flux:sidebar.item icon="calendar" >Calendar</flux:sidebar.item>
            <flux:sidebar.group expandable heading="About" class="grid">
                <flux:sidebar.item href="{{ route('home') }}#about">About us</flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        @auth
            <flux:sidebar.spacer />
            <flux:sidebar.nav>
                <flux:sidebar.item icon="user" href="{{ route('settings.profile') }}">Profile</flux:sidebar.item>
                <flux:sidebar.item icon="cog-6-tooth" href="{{ route('settings.profile') }}">Settings</flux:sidebar.item>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:sidebar.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Log Out</flux:sidebar.item>
                </form>
            </flux:sidebar.nav>
        @else
            <flux:sidebar.spacer />
        @endauth
    </flux:sidebar>

    @fluxScripts

    {{ $slot }}
</body>
</html>
