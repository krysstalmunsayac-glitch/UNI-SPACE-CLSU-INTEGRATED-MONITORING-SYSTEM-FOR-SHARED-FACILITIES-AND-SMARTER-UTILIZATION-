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
                    ['label' => 'Dashboard', 'href' => route('dashboard.superadmin'), 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard.superadmin')],
                    ['label' => 'User Management', 'href' => route('UserManagement'), 'icon' => 'users', 'active' => request()->routeIs('UserManagement*') && ! request()->boolean('archive')],
                    ['label' => 'Report Management', 'href' => route('ReportManagement'), 'icon' => 'report', 'active' => request()->routeIs('ReportManagement')],
                ]
                : [[
                    'label' => 'Dashboard',
                    'href' => $isAdmin ? route('dashboard.officeadmin') : route('dashboard'),
                    'icon' => 'dashboard',
                    'active' => request()->routeIs($isAdmin ? 'dashboard.officeadmin' : 'dashboard'),
                ]];

            $navigation = [['label' => 'Platform', 'items' => $platformItems]];

            if ($isSuperAdmin || $isAdmin) {
                $serviceItems = [
                    [
                        'label' => 'Facility',
                        'href' => $isSuperAdmin ? route('Facility.SuperAdmin') : route('Facility.OfficeAdmin'),
                        'icon' => 'facility',
                        'active' => request()->routeIs('Facility*') && ! request()->boolean('archive'),
                    ],
                ];

                $serviceItems[] = ['label' => 'Amenities', 'href' => route('Amenities'), 'icon' => 'amenities', 'active' => request()->routeIs('Amenities*') && ! request()->boolean('archive')];

                $serviceItems = array_merge($serviceItems, [
                    ['label' => 'Request', 'href' => route('Request'), 'icon' => 'request', 'active' => request()->routeIs('Request') && ! request()->boolean('archive')],
                    ['label' => 'Schedule', 'href' => route('Schedule'), 'icon' => 'schedule', 'active' => request()->routeIs('Schedule*')],
                    ['label' => 'Feedback', 'href' => route('Feedback'), 'icon' => 'feedback', 'active' => request()->routeIs('Feedback*')],
                ]);

                $archiveItems = [];
                if ($isSuperAdmin) {
                    $archiveItems[] = ['label' => 'Archived Facilities', 'href' => route('Facility.SuperAdmin', ['archive' => 1]), 'icon' => 'facility', 'active' => request()->routeIs('Facility.SuperAdmin') && request()->boolean('archive')];
                }
                $archiveItems[] = ['label' => 'Archived Requests', 'href' => route('Request', ['archive' => 1]), 'icon' => 'archive', 'active' => request()->routeIs('Request') && request()->boolean('archive')];

                if ($isSuperAdmin) {
                    $archiveItems[] = ['label' => 'Archived Users', 'href' => route('UserManagement', ['archive' => 1]), 'icon' => 'users', 'active' => request()->routeIs('UserManagement') && request()->boolean('archive')];
                }
                $archiveItems[] = ['label' => 'Archived Amenities', 'href' => route('Amenities', ['archive' => 1]), 'icon' => 'amenities', 'active' => request()->routeIs('Amenities') && request()->boolean('archive')];

                $navigation[] = ['label' => 'Service', 'items' => $serviceItems];
                $navigation[] = ['label' => 'Archives', 'items' => $archiveItems];
            }

            $notificationDestination = match ($currentUser->user_type) {
                'user' => route('dashboard').'#requests',
                'admin', 'super_admin' => route('Request'),
                default => route('dashboard'),
            };

            $shellProps = [
                'brandUrl' => route('dashboard'),
                'logoUrl' => asset('images/silesyu-space-logo.png'),
                'collapsedLogoUrl' => ($isSuperAdmin || $isAdmin)
                    ? asset('images/admin-collapsed-logo.png')
                    : asset('images/silesyu-space-logo.png'),
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
        @include('partials.site-auto-refresh')
    </body>
</html>
