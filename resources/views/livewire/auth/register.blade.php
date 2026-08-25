<?php

use App\Models\User;
use App\Notifications\VerifyPendingRegistration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $contact_number = '';

    public string $address = '';

    public bool $terms = false;

    public string $website = '';

    public int $step = 1;

    public function nextStep(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'contact_number' => ['required', 'string', 'regex:'.User::PH_CONTACT_REGEX],
            'address' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $throttleKey = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => [
                'required',
                'string',
                'confirmed',
                Rules\Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'contact_number' => ['required', 'string', 'regex:'.User::PH_CONTACT_REGEX],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'terms' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        unset($validated['terms']);
        unset($validated['website']);
        $validated['password'] = Hash::make($validated['password']);

        $verificationUrl = URL::temporarySignedRoute(
            'registration.verify',
            now()->addMinutes(60),
            ['payload' => Crypt::encryptString(json_encode($validated, JSON_THROW_ON_ERROR))],
        );

        Notification::route('mail', $validated['email'])
            ->notify(new VerifyPendingRegistration($verificationUrl));

        session()->flash(
            'status',
            'We sent you a verification email. Your account will only be created after you verify your email address.',
        );

        $this->redirect(route('login', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Create Account" description="or use your email for registration" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-4">
        <div class="pointer-events-none absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input wire:model="website" id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
            <div class="flex flex-col items-center gap-2 text-center">
                <span @class([
                    'flex size-9 items-center justify-center rounded-full text-sm font-black',
                    'bg-emerald-700 text-white' => $step === 1,
                    'bg-emerald-100 text-emerald-800 dark:bg-zinc-800 dark:text-zinc-300' => $step !== 1,
                ])>1</span>
                <span @class([
                    'text-xs font-black uppercase tracking-wide',
                    'text-emerald-800 dark:text-emerald-300' => $step === 1,
                    'text-emerald-900/45 dark:text-zinc-500' => $step !== 1,
                ])>Step 1: Details</span>
            </div>

            <span class="h-px w-12 bg-emerald-900/15 dark:bg-white/15"></span>

            <div class="flex flex-col items-center gap-2 text-center">
                <span @class([
                    'flex size-9 items-center justify-center rounded-full text-sm font-black',
                    'bg-emerald-700 text-white' => $step === 2,
                    'bg-emerald-100 text-emerald-800 dark:bg-zinc-800 dark:text-zinc-300' => $step !== 2,
                ])>2</span>
                <span @class([
                    'text-xs font-black uppercase tracking-wide',
                    'text-emerald-800 dark:text-emerald-300' => $step === 2,
                    'text-emerald-900/45 dark:text-zinc-500' => $step !== 2,
                ])>Step 2: Security</span>
            </div>
        </div>

        @if ($step === 1)
            <div class="grid gap-2">
                <x-ui::input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required minlength="2" maxlength="100" autofocus autocomplete="name" placeholder="Full name" />
            </div>

            <div class="grid gap-2">
                <x-ui::input wire:model="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required maxlength="255" autocomplete="email" placeholder="email@example.com" />
            </div>

            <div class="grid gap-2">
                <x-ui::input wire:model="contact_number" id="contact_number" label="{{ __('Contact Number') }}" type="tel" name="contact_number" required minlength="11" maxlength="13" pattern="(?:09[0-9]{9}|\+639[0-9]{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX." autocomplete="tel" placeholder="09123456789" />
            </div>

            <div class="grid gap-2">
                <x-ui::input wire:model="address" id="address" label="{{ __('Address') }}" type="text" name="address" required minlength="5" maxlength="500" autocomplete="street-address" placeholder="123 Main St, City" />
            </div>

            <x-ui::button type="button" variant="primary" wire:click="nextStep" class="mx-auto w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
                Next
            </x-ui::button>
        @else
            <div class="grid gap-2">
                <x-ui::input
                    wire:model="password"
                    id="password"
                    label="{{ __('Password') }}"
                    type="password"
                    revealable
                    name="password"
                    required
                    minlength="8"
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}"
                    title="Use at least 8 characters with uppercase, lowercase, number, and special character."
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <p class="text-xs font-semibold text-emerald-900/60 dark:text-zinc-400">
                    Use at least 8 characters with uppercase, lowercase, a number, and a special character (such as !, @, #, or $).
                </p>
            </div>

            <div class="grid gap-2">
                <x-ui::input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    label="{{ __('Confirm password') }}"
                    type="password"
                    revealable
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />
            </div>

            <div class="space-y-2">
                <label class="flex items-start gap-3 text-xs font-semibold leading-5 text-emerald-900/70 dark:text-zinc-300">
                    <input
                        wire:model="terms"
                        type="checkbox"
                        class="mt-1 size-4 rounded border-emerald-900/20 text-emerald-700 focus:ring-emerald-700"
                    >
                    <span>
                        I agree to the
                        <a href="{{ route('terms') }}" target="_blank" class="font-black text-emerald-800 underline underline-offset-2 dark:text-emerald-300">
                            Terms and Conditions
                        </a>.
                    </span>
                </label>
                @error('terms')
                    <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-center gap-3">
                <x-ui::button type="button" variant="ghost" wire:click="previousStep" class="w-36 rounded-full border border-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-zinc-800">
                    Back
                </x-ui::button>
                <x-ui::button type="submit" variant="primary" class="w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
                    {{ __('Sign up') }}
                </x-ui::button>
            </div>
        @endif
    </form>

    <a
        href="{{ route('login') }}"
        data-auth-switch="login"
        class="mx-auto inline-flex items-center gap-2 rounded-full border border-emerald-700 px-5 py-2 text-sm font-black text-emerald-700 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:border-emerald-400 dark:text-emerald-400 dark:hover:bg-zinc-800 dark:focus:ring-offset-zinc-900 lg:hidden"
    >
        <span aria-hidden="true">&larr;</span>
        {{ __('Back to Sign In') }}
    </a>

</div>
