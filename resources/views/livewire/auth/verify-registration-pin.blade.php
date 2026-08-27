<?php

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyPendingRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $token = '';

    public string $pin = '';

    public string $email = '';

    public string $status = '';

    public int $cooldownSeconds = 0;

    public function mount(string $token): void
    {
        $this->token = $token;
        $pending = $this->pendingRegistration();
        $this->email = $pending->email;
        $this->refreshCooldown($pending);
    }

    public function verifyPin(): void
    {
        $this->validate([
            'pin' => ['required', 'digits:6'],
        ], [
            'pin.digits' => 'Enter the 6-digit PIN sent to your email.',
        ]);

        $rateLimitKey = 'registration-pin-verify|'.$this->token.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            throw ValidationException::withMessages([
                'pin' => 'Too many attempts. Please wait before trying again.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $result = DB::transaction(function (): User|string|null {
            $pending = PendingRegistration::query()
                ->where('token', $this->token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pending->pin_expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'pin' => 'This PIN has expired. Request a new PIN to continue.',
                ]);
            }

            if ($pending->failed_attempts >= 5) {
                throw ValidationException::withMessages([
                    'pin' => 'Too many incorrect attempts. Request a new PIN to continue.',
                ]);
            }

            if (! Hash::check($this->pin, $pending->pin_hash)) {
                $pending->increment('failed_attempts');

                return 'The PIN you entered is incorrect.';
            }

            if (User::query()->where('email', $pending->email)->exists()) {
                $pending->delete();

                return null;
            }

            $registration = $pending->registration_data;
            $user = User::query()->create([
                'name' => $registration['name'],
                'email' => $registration['email'],
                'password' => $registration['password'],
                'contact_number' => $registration['contact_number'],
                'address' => $registration['address'],
                'email_verified_at' => now(),
            ]);

            $pending->delete();

            return $user;
        });

        if (is_string($result)) {
            throw ValidationException::withMessages(['pin' => $result]);
        }

        if (! $result) {
            $this->redirect(route('login', absolute: false), navigate: true);

            return;
        }

        event(new Registered($result));
        event(new Verified($result));
        RateLimiter::clear($rateLimitKey);
        Auth::login($result);
        Session::regenerate();

        session()->flash('status', 'Your email was verified and your account is ready.');
        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function resendPin(): void
    {
        $rateLimitKey = 'registration-pin-resend|'.$this->token.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            throw ValidationException::withMessages([
                'resend' => 'Too many resend requests. Please try again later.',
            ]);
        }

        $pin = (string) random_int(100000, 999999);

        $pending = DB::transaction(function () use ($pin): PendingRegistration {
            $pending = PendingRegistration::query()
                ->where('token', $this->token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pending->resend_available_at->isFuture()) {
                $this->refreshCooldown($pending);

                throw ValidationException::withMessages([
                    'resend' => "Please wait {$this->cooldownSeconds} seconds before requesting another PIN.",
                ]);
            }

            $pending->update([
                'pin_hash' => Hash::make($pin),
                'pin_expires_at' => now()->addMinutes(10),
                'resend_available_at' => now()->addMinute(),
                'failed_attempts' => 0,
            ]);

            return $pending;
        });

        RateLimiter::hit($rateLimitKey, 600);
        Notification::route('mail', $pending->email)
            ->notify(new VerifyPendingRegistration($pin));

        $this->pin = '';
        $this->resetValidation();
        $this->status = 'A new PIN was sent. Your previous PIN is no longer valid.';
        $this->refreshCooldown($pending);
    }

    private function pendingRegistration(): PendingRegistration
    {
        return PendingRegistration::query()->where('token', $this->token)->firstOrFail();
    }

    private function refreshCooldown(PendingRegistration $pending): void
    {
        $this->cooldownSeconds = $pending->resend_available_at->isFuture()
            ? (int) ceil(now()->diffInSeconds($pending->resend_available_at))
            : 0;
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Verify your email" description="Enter the one-time PIN sent to your inbox" />

    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center dark:border-emerald-500/30 dark:bg-emerald-950/30">
        <p class="text-sm font-bold text-emerald-950 dark:text-emerald-100">PIN sent to</p>
        <p class="mt-1 break-all text-sm text-emerald-700 dark:text-emerald-300">{{ $email }}</p>
        <p class="mt-2 text-xs text-emerald-800/70 dark:text-emerald-300/70">The PIN expires in 10 minutes.</p>
    </div>

    @if ($status)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="verifyPin" class="flex flex-col gap-5">
        <div>
            <label for="pin" class="mb-2 block text-center text-xs font-black uppercase tracking-[0.2em] text-emerald-800 dark:text-emerald-300">6-digit verification PIN</label>
            <input
                wire:model="pin"
                id="pin"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                pattern="[0-9]{6}"
                autofocus
                class="h-16 w-full rounded-xl border border-emerald-900/10 bg-emerald-50 text-center text-3xl font-black tracking-[0.45em] text-emerald-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-800 dark:text-white"
                placeholder="000000"
            >
            @error('pin') <p class="mt-2 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-ui::button type="submit" variant="primary" class="w-full rounded-full bg-emerald-700 py-3 text-xs font-black uppercase tracking-wide text-white hover:bg-emerald-800">
            Verify and continue
        </x-ui::button>
    </form>

    <div
        x-data="{ remaining: $wire.entangle('cooldownSeconds') }"
        x-init="setInterval(() => { if (remaining > 0) remaining-- }, 1000)"
        class="text-center"
    >
        <button
            type="button"
            wire:click="resendPin"
            x-bind:disabled="remaining > 0"
            class="text-sm font-black text-emerald-700 underline underline-offset-4 transition hover:text-emerald-900 disabled:cursor-not-allowed disabled:text-zinc-400 disabled:no-underline dark:text-emerald-300"
        >
            <span x-show="remaining === 0">Resend PIN</span>
            <span x-show="remaining > 0">Resend available in <span x-text="remaining"></span>s</span>
        </button>
        @error('resend') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
    </div>

    <a href="{{ route('register') }}" class="text-center text-sm font-bold text-zinc-500 transition hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-300">
        Use a different email address
    </a>
</div>
