<?php

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyPendingRegistration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $name = '';

    public string $account_type = '';

    public string $clsu_id = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $contact_number = '';

    public string $address = '';

    public bool $terms = false;

    public bool $privacy_consent = false;

    public string $website = '';

    public int $step = 1;

    public function usesClsuEmail(): bool
    {
        return User::usesClsuEmail($this->email);
    }

    public function isInstitutionalAccount(): bool
    {
        return in_array($this->account_type, ['staff', 'student'], true);
    }

    public function updatedAccountType(): void
    {
        $this->resetValidation(['account_type', 'email', 'clsu_id']);

        if ($this->account_type === 'external') {
            $this->clsu_id = '';
        }
    }

    private function clsuIdRules(): array
    {
        return [
            Rule::excludeIf(fn (): bool => ! $this->isInstitutionalAccount()),
            Rule::requiredIf(fn (): bool => $this->isInstitutionalAccount()),
            'string',
            'regex:'.User::CLSU_ID_REGEX,
            'unique:users,clsu_id',
            Rule::unique('pending_registrations', 'clsu_id')->ignore(
                PendingRegistration::query()
                    ->where('email', Str::lower(trim($this->email)))
                    ->value('id')
            ),
        ];
    }

    private function clearExpiredPendingRegistrations(): void
    {
        PendingRegistration::query()
            ->where('pin_expires_at', '<', now())
            ->delete();
    }

    public function nextStep(): void
    {
        $this->clearExpiredPendingRegistrations();

        if ($this->step === 1) {
            $this->validate([
                'account_type' => ['required', Rule::in(['staff', 'student', 'external'])],
                'privacy_consent' => ['accepted'],
            ], [
                'account_type.required' => 'Select whether you are staff, a CLSU student, or an external user.',
                'privacy_consent.accepted' => 'You must read and consent to the Data Privacy Notice before continuing.',
            ]);

            $this->step = 2;

            return;
        }

        $this->validate([
            'account_type' => ['required', Rule::in(['staff', 'student', 'external'])],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'clsu_id' => $this->clsuIdRules(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'contact_number' => ['required', 'string', 'regex:'.User::PH_CONTACT_REGEX],
            'address' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'account_type.required' => 'Select whether you are staff, a CLSU student, or an external user.',
            'email.clsu_email' => 'Staff and CLSU students must use an @clsu2.edu.ph email address.',
            'email.external_email' => 'External users must use a non-CLSU email address.',
            'clsu_id.required' => 'Enter your unique CLSU ID.',
            'clsu_id.regex' => 'Enter a valid CLSU ID in the format 22-1773.',
            'clsu_id.unique' => 'This CLSU ID is already associated with an account or pending registration.',
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        $this->validateAccountEmailType();

        $this->step = 3;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $this->clearExpiredPendingRegistrations();

        $throttleKey = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $validated = $this->validate([
            'account_type' => ['required', Rule::in(['staff', 'student', 'external'])],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'clsu_id' => $this->clsuIdRules(),
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
            'privacy_consent' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'account_type.required' => 'Select whether you are staff, a CLSU student, or an external user.',
            'clsu_id.required' => 'Enter your unique CLSU ID.',
            'clsu_id.regex' => 'Enter a valid CLSU ID in the format 22-1773.',
            'clsu_id.unique' => 'This CLSU ID is already associated with an account or pending registration.',
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            'privacy_consent.accepted' => 'You must consent to the Data Privacy Notice to register.',
        ]);

        $this->validateAccountEmailType();

        unset($validated['terms']);
        unset($validated['website']);
        unset($validated['privacy_consent']);
        $validated['privacy_consent'] = true;
        $validated['privacy_consented_at'] = now()->toIso8601String();
        $validated['privacy_notice_version'] = User::PRIVACY_NOTICE_VERSION;
        $validated['clsu_id'] = $validated['clsu_id'] ?? null;
        $validated['password'] = Hash::make($validated['password']);

        $pin = (string) random_int(100000, 999999);
        $token = (string) Str::uuid();

        PendingRegistration::query()->updateOrCreate(
            ['email' => $validated['email']],
            [
                'token' => $token,
                'clsu_id' => $validated['clsu_id'],
                'registration_data' => $validated,
                'pin_hash' => Hash::make($pin),
                'pin_expires_at' => now()->addMinutes(10),
                'resend_available_at' => now()->addMinute(),
                'failed_attempts' => 0,
            ],
        );

        Notification::route('mail', $validated['email'])
            ->notify(new VerifyPendingRegistration($pin));

        $this->redirect(route('registration.pin', $token, absolute: false), navigate: true);
    }

    private function validateAccountEmailType(): void
    {
        if ($this->isInstitutionalAccount() && ! $this->usesClsuEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Staff and CLSU students must use an @clsu2.edu.ph email address.',
            ]);
        }

        if ($this->account_type === 'external' && $this->usesClsuEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Choose Staff or CLSU Student when using an @clsu2.edu.ph email address.',
            ]);
        }
    }
}; ?>

<div class="flex flex-col gap-6">


    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-4">
        <div class="pointer-events-none absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input wire:model="website" id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <div class="flex w-full items-start">
            @foreach ([1 => 'Account', 2 => 'Details', 3 => 'Security'] as $number => $label)
                <div class="flex min-w-0 flex-1 flex-col items-center gap-2 text-center">
                    <span @class([
                        'flex size-9 items-center justify-center rounded-full text-sm font-black',
                        'bg-emerald-700 text-white' => $step === $number,
                        'bg-emerald-100 text-emerald-800 dark:bg-zinc-800 dark:text-zinc-300' => $step !== $number,
                    ])>{{ $number }}</span>
                    <span @class([
                        'text-[10px] font-black uppercase tracking-wide sm:text-xs',
                        'text-emerald-800 dark:text-emerald-300' => $step === $number,
                        'text-emerald-900/45 dark:text-zinc-500' => $step !== $number,
                    ])>{{ $label }}</span>
                </div>
                @if ($number < 3)
                    <span class="mt-4 h-px w-8 shrink-0 bg-emerald-900/15 dark:bg-white/15 sm:w-12"></span>
                @endif
            @endforeach
        </div>

        @if ($step === 1)
            <fieldset class="grid gap-4 py-2">
                <div class="text-center">
                    <legend class="text-base font-black text-emerald-800 dark:text-emerald-300">Choose your account type</legend>
                    <p class="mt-1 text-xs font-semibold text-emerald-900/60 dark:text-zinc-400">Select the option that best describes you.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach (['staff' => 'Staff', 'student' => 'CLSU Student', 'external' => 'External User'] as $value => $label)
                        <label @class([
                            'flex min-h-14 cursor-pointer items-center justify-center rounded-xl border px-3 py-3 text-center text-sm font-black transition',
                            'border-emerald-700 bg-emerald-700 text-white' => $account_type === $value,
                            'border-emerald-900/15 bg-emerald-50 text-emerald-900 hover:border-emerald-500 dark:bg-zinc-800 dark:text-zinc-200' => $account_type !== $value,
                        ])>
                            <input wire:model.live="account_type" type="radio" name="account_type" value="{{ $value }}" class="sr-only">
                            <span @class([
                                'relative z-10',
                                'text-white!' => $account_type === $value,
                            ])>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('account_type') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            <div class="rounded-xl border border-emerald-900/10 p-4 dark:border-white/10">
                <h3 class="text-sm font-black text-emerald-900 dark:text-emerald-200">Data Privacy Notice and Consent</h3>
                <p class="mt-2 text-xs leading-5 text-emerald-900/70 dark:text-zinc-300">
                    SIEL SPACE collects your name, email address, CLSU ID when applicable, contact number, address, account credentials, and reservation activity to create and secure your account, process facility requests, communicate decisions, manage schedules, and maintain operational and audit records. Data is stored in authorized systems, protected through access controls and security safeguards, retained only as necessary, and accessed only by authorized CLSU personnel or parties permitted or required by law.
                </p>
                <a href="{{ route('terms') }}#privacy-notice" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs font-black text-emerald-800 underline underline-offset-2 dark:text-emerald-300">
                    View Full Data Privacy Notice<span class="sr-only"> (opens in a new tab)</span>
                </a>

                <label class="mt-3 flex cursor-pointer items-start gap-3 text-xs font-semibold leading-5 text-emerald-900/80 dark:text-zinc-200">
                    <input wire:model.live="privacy_consent" type="checkbox" required class="mt-1 size-4 shrink-0 rounded border-emerald-900/20 text-emerald-700 focus:ring-emerald-700">
                    <span>I have read and agree to the Data Privacy Notice and consent to the collection and processing of my personal information.</span>
                </label>
                @error('privacy_consent')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui::button type="button" variant="primary" wire:click="nextStep" :disabled="! $privacy_consent" class="mx-auto w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
                Continue
            </x-ui::button>
        @elseif ($step === 2)

            <div class="grid gap-2">
                <x-ui::input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required minlength="2" maxlength="100" autofocus autocomplete="name" placeholder="Full name" />
            </div>

            <div class="grid gap-2">
                <x-ui::input wire:model.live.debounce.400ms="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required maxlength="255" autocomplete="email" placeholder="name@clsu2.edu.ph" />
            </div>

            @if ($this->isInstitutionalAccount())
                <div class="grid gap-2" wire:key="clsu-id-field">
                    <x-ui::input wire:model="clsu_id" id="clsu_id" label="{{ __('CLSU ID') }}" type="text" name="clsu_id" required maxlength="7" inputmode="numeric" pattern="[0-9]{2}-[0-9]{4}" title="Use the format 25-1234." placeholder="25-1234" />
                    <p class="text-xs font-semibold text-emerald-900/60 dark:text-zinc-400">This unique CLSU ID will be linked to your institutional email.</p>
                </div>
            @endif

            <div class="grid gap-2">
                <x-ui::input wire:model="contact_number" id="contact_number" label="{{ __('Contact Number') }}" type="tel" name="contact_number" required minlength="11" maxlength="13" pattern="(?:09[0-9]{9}|\+639[0-9]{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX." autocomplete="tel" placeholder="09123456789" />
            </div>

            <div class="grid gap-2">
                <x-ui::input wire:model="address" id="address" label="{{ __('Address') }}" type="text" name="address" required minlength="5" maxlength="500" autocomplete="street-address" placeholder="123 Main St, City" />
            </div>

            <div class="flex items-center justify-center gap-3">
                <x-ui::button type="button" variant="ghost" wire:click="previousStep" class="w-36 rounded-full border border-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-300 dark:text-emerald-200 dark:hover:bg-zinc-800">Back</x-ui::button>
                <x-ui::button type="button" variant="primary" wire:click="nextStep" class="w-36 rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">Next</x-ui::button>
            </div>
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
                <x-ui::button type="submit" variant="primary" class="w-36 rounded-xl! bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
                    {{ __('Sign up') }}
                </x-ui::button>
            </div>
        @endif
    </form>

</div>
