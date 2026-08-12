<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            .auth-card [data-ui-label],
            .auth-card label {
                font-size: 12px !important;
                font-weight: 700 !important;
                color: #007a2f !important;
            }

            .auth-card input:not([type="checkbox"]) {
                min-height: 42px;
                border: 0 !important;
                border-radius: 0 !important;
                background: #f1f5f3 !important;
                color: #007a2f !important;
                box-shadow: none !important;
            }

            .auth-card input:not([type="checkbox"]):focus {
                box-shadow: 0 0 0 2px rgba(20, 83, 45, 0.16) !important;
            }

            .dark .auth-card [data-ui-label],
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

            .auth-card {
                isolation: isolate;
            }

            .auth-card > section {
                backface-visibility: hidden;
                transform: translateZ(0);
                will-change: transform, opacity;
                transition:
                    transform 560ms cubic-bezier(.76, 0, .24, 1),
                    opacity 280ms ease;
            }

            @media (min-width: 1024px) {
                .auth-card.auth-switching-to-register > section:first-child {
                    transform: translateX(8%);
                    opacity: 0;
                }

                .auth-card.auth-switching-to-register > section:nth-child(2) {
                    transform: translateX(-100%);
                    z-index: 2;
                }

                .auth-card.auth-switching-to-login > section:first-child {
                    transform: translateX(100%);
                    z-index: 2;
                }

                .auth-card.auth-switching-to-login > section:nth-child(2) {
                    transform: translateX(-8%);
                    opacity: 0;
                }

                .auth-card.auth-enter-from-login > section:nth-child(2) {
                    animation: auth-form-enter 420ms cubic-bezier(.16, 1, .3, 1) both;
                }

                .auth-card.auth-enter-from-register > section:first-child {
                    animation: auth-form-enter 420ms cubic-bezier(.16, 1, .3, 1) both;
                }
            }

            @keyframes auth-form-enter {
                from { opacity: 0; transform: translateY(.75rem); }
                to { opacity: 1; transform: translateY(0); }
            }

            @media (max-width: 1023px) {
                .auth-card.auth-switching-to-register > section,
                .auth-card.auth-switching-to-login > section {
                    opacity: 0;
                    transform: translateY(-1rem) scale(.98);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .auth-card > section {
                    animation: none !important;
                    transition-duration: 1ms !important;
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-100 font-sans text-emerald-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        @php
            $isRegisterPage = request()->routeIs('register');
            $panelTitle = $isRegisterPage ? 'Welcome Back!' : 'Hello, Friend!';
            $panelText = $isRegisterPage
                ? 'To keep connected with SIEL SPACE, please log in with your account.'
                : 'Enter your details and start your facility reservation journey with us.';
            $panelRoute = $isRegisterPage ? route('login') : route('register');
            $panelButton = $isRegisterPage ? 'Sign In' : 'Sign Up';
        @endphp

        <main class="flex min-h-svh items-center justify-center px-4 py-10">
            <div class="auth-card grid w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl shadow-zinc-900/20 dark:bg-zinc-900 dark:shadow-black/35 lg:grid-cols-2" data-auth-page="{{ $isRegisterPage ? 'register' : 'login' }}">
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


        <script>
            (() => {
                const card = document.querySelector('.auth-card');
                if (!card) return;

                const arrival = sessionStorage.getItem('auth-transition');
                sessionStorage.removeItem('auth-transition');

                if (arrival === 'register' && card.dataset.authPage === 'register') {
                    card.classList.add('auth-enter-from-login');
                } else if (arrival === 'login' && card.dataset.authPage === 'login') {
                    card.classList.add('auth-enter-from-register');
                }

                document.querySelectorAll('[data-auth-switch]').forEach(link => {
                    link.addEventListener('click', event => {
                        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                        event.preventDefault();
                        const destination = link.dataset.authSwitch;
                        card.classList.remove('auth-enter-from-login', 'auth-enter-from-register');
                        card.classList.add(`auth-switching-to-${destination}`);
                        sessionStorage.setItem('auth-transition', destination);

                        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 540;
                        window.setTimeout(() => {
                            if (window.Livewire?.navigate) {
                                window.Livewire.navigate(link.href);
                            } else {
                                window.location.assign(link.href);
                            }
                        }, delay);
                    }, { once: true });
                });
            })();

            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
            });
        </script>
    </body>
</html>
