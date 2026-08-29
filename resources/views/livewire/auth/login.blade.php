<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => true,
        ], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        Session::forget('url.intended');

        $dashboard = match (Auth::user()->user_type) {
            'super_admin' => route('dashboard.superadmin', absolute: false),
            'admin' => route('dashboard.officeadmin', absolute: false),
            default => route('dashboard', absolute: false),
        };

        Session::flash('sweet_alert', [
            'title' => 'Welcome back!',
            'text' => 'You have logged in successfully.',
            'icon' => 'success',
            'position' => 'center',
        ]);

        // Use a full-page redirect so account switches do not reuse stale
        // Livewire navigation state from the previously authenticated user.
        $this->redirect($dashboard);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <a
        href="{{ route('home') }}"
        class="fixed left-4 top-4 z-10 inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-600/50 dark:text-emerald-300 dark:hover:bg-emerald-950/60 dark:hover:text-emerald-200 sm:left-6 sm:top-6"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m15 18-6-6 6-6" />
        </svg>
        {{ __('Back to Home') }}
    </a>

    <x-auth-header title="Sign in" description="or use your account" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <x-ui::input wire:model="email" label="{{ __('Email address') }}" type="email" name="email" required autofocus autocomplete="email" placeholder="email@example.com" />

        <!-- Password -->
        <div class="relative">
            <x-ui::input
                wire:model="password"
                label="{{ __('Password') }}"
                type="password"
                revealable
                name="password"
                required
                autocomplete="current-password"
                placeholder="Password"
            />

            @if (Route::has('password.request'))
                <x-text-link class="absolute right-0 top-0 text-xs" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </x-text-link>
            @endif
        </div>

        <!-- Remember Me -->
        <x-ui::checkbox wire:model="remember" label="{{ __('Remember me') }}" />

        <div class="flex items-center justify-center">
            <x-ui::button variant="primary" type="submit" class="w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">{{ __('Sign in') }}</x-ui::button>
        </div>
    </form>

    <div class="flex items-center justify-center gap-2 text-sm font-semibold lg:hidden">
        <span class="text-zinc-600 dark:text-zinc-300">{{ __("Don't have an account?") }}</span>
        <a
            href="{{ route('register') }}"
            data-auth-switch="register"
            class="font-black text-emerald-700 hover:text-emerald-800 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:text-emerald-400 dark:hover:text-emerald-300 dark:focus:ring-offset-zinc-900"
        >
            {{ __('Sign Up') }}
        </a>
    </div>

</div>
