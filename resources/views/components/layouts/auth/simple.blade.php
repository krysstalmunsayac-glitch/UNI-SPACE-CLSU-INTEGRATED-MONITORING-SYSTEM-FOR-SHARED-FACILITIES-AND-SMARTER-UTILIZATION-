<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            .auth-card [data-flux-label],
            .auth-card label {
                font-size: 12px !important;
                font-weight: 700 !important;
                color: #14532d !important;
            }

            .auth-card input:not([type="checkbox"]) {
                min-height: 42px;
                border: 0 !important;
                border-radius: 0 !important;
                background: #f1f5f3 !important;
                color: #14532d !important;
                box-shadow: none !important;
            }

            .auth-card input:not([type="checkbox"]):focus {
                box-shadow: 0 0 0 2px rgba(20, 83, 45, 0.16) !important;
            }

            .dark .auth-card [data-flux-label],
            .dark .auth-card label {
                color: #bbf7d0 !important;
            }

            .dark .auth-card input:not([type="checkbox"]) {
                background: #27272a !important;
                color: #f4f4f5 !important;
            }

            .dark .auth-card input:not([type="checkbox"])::placeholder {
                color: #a1a1aa !important;
            }

            .dark .auth-card input:not([type="checkbox"]):focus {
                box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.22) !important;
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-100 font-sans text-emerald-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        @php
            $isRegisterPage = request()->routeIs('register');
            $panelTitle = $isRegisterPage ? 'Welcome Back!' : 'Hello, Friend!';
            $panelText = $isRegisterPage
                ? 'To keep connected with UNI Space, please log in with your account.'
                : 'Enter your details and start your facility reservation journey with us.';
            $panelRoute = $isRegisterPage ? route('login') : route('register');
            $panelButton = $isRegisterPage ? 'Sign In' : 'Sign Up';
        @endphp

        <main class="flex min-h-svh items-center justify-center px-4 py-10">
            <div class="auth-card grid w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl shadow-zinc-900/20 dark:bg-zinc-900 dark:shadow-black/35 lg:grid-cols-2">
                @if ($isRegisterPage)
                    @include('components.layouts.auth.simple-panel', [
                        'panelTitle' => $panelTitle,
                        'panelText' => $panelText,
                        'panelRoute' => $panelRoute,
                        'panelButton' => $panelButton,
                    ])
                @endif

                <section class="flex min-h-[640px] items-center justify-center px-8 py-12 sm:px-14">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </section>

                @unless ($isRegisterPage)
                    @include('components.layouts.auth.simple-panel', [
                        'panelTitle' => $panelTitle,
                        'panelText' => $panelText,
                        'panelRoute' => $panelRoute,
                        'panelButton' => $panelButton,
                    ])
                @endunless
            </div>
        </main>

        @fluxScripts

        <script>
            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
            });
        </script>
    </body>
</html>
