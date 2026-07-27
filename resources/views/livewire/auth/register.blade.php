<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $contact_number = '';
    public string $address = '';
    public bool $terms = false;
    public int $step = 1;

    public function nextStep(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'contact_number' => ['required', 'string', 'min:11', 'max:12', 'regex:/^[0-9]+$/'],
            'address' => ['required', 'string', 'max:255'],
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
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => [
                'required',
                'string',
                'confirmed',
                Rules\Password::min(8)->numbers(),
                'regex:/[A-Z]/',
            ],
            'contact_number' => ['required', 'string', 'min:11', 'max:12', 'regex:/^[0-9]+$/'],
            'address' => ['required', 'string', 'max:255'],
            'terms' => ['accepted'],
        ], [
            'password.regex' => 'The password must contain at least one capital letter.',
        ]);

        unset($validated['terms']);
        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        $user->update([
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Create Account" description="or use your email for registration" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-4">
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
                <flux:input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" placeholder="Full name" />
            </div>

            <div class="grid gap-2">
                <flux:input wire:model="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required autocomplete="email" placeholder="email@example.com" />
            </div>

            <div class="grid gap-2">
                <flux:input wire:model="contact_number" id="contact_number" label="{{ __('Contact Number') }}" type="tel" name="contact_number" required autocomplete="tel" placeholder="09123456789" />
            </div>

            <div class="grid gap-2">
                <flux:input wire:model="address" id="address" label="{{ __('Address') }}" type="text" name="address" required autocomplete="street-address" placeholder="123 Main St, City" />
            </div>

            <flux:button type="button" variant="primary" wire:click="nextStep" class="mx-auto w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
                Next
            </flux:button>
        @else
            <div class="grid gap-2">
                <flux:input
                    wire:model="password"
                    id="password"
                    label="{{ __('Password') }}"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    pattern="(?=.*[A-Z])(?=.*[0-9]).{8,}"
                    title="Use at least 8 characters, including a number and one capital letter."
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <p class="text-xs font-semibold text-emerald-900/60 dark:text-zinc-400">
                    Use at least 8 characters, including a number and one capital letter.
                </p>
            </div>

            <div class="grid gap-2">
                <flux:input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    label="{{ __('Confirm password') }}"
                    type="password"
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
                <flux:button type="button" variant="ghost" wire:click="previousStep" class="w-36 rounded-full border border-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-zinc-800">
                    Back
                </flux:button>
                <flux:button type="submit" variant="primary" class="w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
                    {{ __('Sign up') }}
                </flux:button>
            </div>
        @endif
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <x-text-link href="{{ route('login') }}">Log in</x-text-link>
    </div>
</div>
