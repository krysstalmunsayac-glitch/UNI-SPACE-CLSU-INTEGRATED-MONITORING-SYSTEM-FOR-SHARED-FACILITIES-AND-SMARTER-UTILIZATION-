<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php
            $currentUser = auth()->user();
            $isSuperAdmin = $currentUser->hasrole('super_admin');
            $isAdmin = $currentUser->hasrole('admin');
            $profileRoute = $currentUser->hasrole('user')
                ? route('profile.external')
                : route('settings.profile');

            $platformItems = $isSuperAdmin
                ? [
                    ['label' => 'Dashboard', 'href' => route('dashboard.super-admin'), 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard.super-admin')],
                    ['label' => 'User Management', 'href' => route('users.index'), 'icon' => 'users', 'active' => request()->routeIs('users.*') && ! request()->boolean('archive')],
                    ['label' => 'Report Management', 'href' => route('reports.index'), 'icon' => 'report', 'active' => request()->routeIs('reports.index')],
                ]
                : [[
                    'label' => 'Dashboard',
                    'href' => $isAdmin ? route('dashboard.office-admin') : route('dashboard'),
                    'icon' => 'dashboard',
                    'active' => request()->routeIs($isAdmin ? 'dashboard.office-admin' : 'dashboard'),
                ]];

            $navigation = [['label' => 'Platform', 'items' => $platformItems]];

            if ($isSuperAdmin || $isAdmin) {
                $serviceItems = [
                    [
                        'label' => 'Facility',
                        'href' => $isSuperAdmin ? route('facilities.super-admin.index') : route('facilities.office-admin.index'),
                        'icon' => 'facility',
                        'active' => request()->routeIs('facilities.*') && ! request()->boolean('archive'),
                    ],
                ];

                $serviceItems[] = ['label' => 'Amenities', 'href' => route('amenities.index'), 'icon' => 'amenities', 'active' => request()->routeIs('amenities.*') && ! request()->boolean('archive')];

                $serviceItems = array_merge($serviceItems, [
                    ['label' => 'Request', 'href' => route('requests.index'), 'icon' => 'request', 'active' => request()->routeIs('requests.index') && ! request()->boolean('archive')],
                    ['label' => 'Schedule', 'href' => route('schedules.index'), 'icon' => 'schedule', 'active' => request()->routeIs('Schedule*')],
                    ['label' => 'Feedback', 'href' => route('feedback.index'), 'icon' => 'feedback', 'active' => request()->routeIs('feedback.*')],
                ]);

                $archiveItems = [];
                if ($isSuperAdmin) {
                    $archiveItems[] = ['label' => 'Archived Facilities', 'href' => route('facilities.super-admin.index', ['archive' => 1]), 'icon' => 'facility', 'active' => request()->routeIs('facilities.super-admin.index') && request()->boolean('archive')];
                    $archiveItems[] = ['label' => 'Archived Requests', 'href' => route('requests.index', ['archive' => 1]), 'icon' => 'archive', 'active' => request()->routeIs('requests.index') && request()->boolean('archive')];
                    $archiveItems[] = ['label' => 'Archived Users', 'href' => route('users.index', ['archive' => 1]), 'icon' => 'users', 'active' => request()->routeIs('users.index') && request()->boolean('archive')];
                    $archiveItems[] = ['label' => 'Archived Amenity', 'href' => route('amenities.index', ['archive' => 1]), 'icon' => 'amenities', 'active' => request()->routeIs('amenities.index') && request()->boolean('archive')];
                }

                $navigation[] = ['label' => 'Service', 'items' => $serviceItems];
                if ($archiveItems !== []) {
                    $navigation[] = ['label' => 'Archives', 'items' => $archiveItems];
                }
            }

            $notificationDestination = match ($currentUser->user_type) {
                'user' => route('dashboard').'#requests',
                'admin', 'super_admin' => route('requests.index'),
                default => route('dashboard'),
            };

            $shellProps = [
                'brandUrl' => route('dashboard'),
                'logoUrl' => asset('images/Logo_white.png'),
                'collapsedLogoUrl' => asset('images/Logo_white.png'),
                'navigation' => $navigation,
                'user' => [
                    'name' => $currentUser->name,
                    'email' => $currentUser->email,
                    'initials' => $currentUser->initials(),
                    'avatar' => $currentUser->avatar_url,
                    'profileUrl' => $profileRoute,
                    'logoutUrl' => route('logout'),
                ],
                'notifications' => [
                    'unread' => $currentUser->unreadNotifications()->count(),
                    'markReadUrl' => route('notifications.read'),
                    'recentUrl' => route('notifications.recent'),
                    'destination' => $notificationDestination,
                ],
                'csrfToken' => csrf_token(),
            ];
        @endphp

        <script id="react-app-shell-props" type="application/json">@json($shellProps)</script>
        <div id="react-app-shell" wire:ignore></div>

        <div class="admin-shell-content react-shell-content">
            {{ $slot }}
        </div>

        @include('partials.confirmation-dialog')
        @stack('scripts')
        @livewireScripts
    </body>
</html>
