<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = Str::lower(trim((string) request()->query('email')));
    }

    public function accept(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || $user->email_verified_at || $user->invitation_revoked_at || ! $user->invitation_expires_at || $user->invitation_expires_at->isPast()) {
            $this->addError('email', 'This invitation is invalid, expired, or has already been used. Ask an administrator to resend it.');
            return;
        }

        $status = Password::reset(
            ['email' => $validated['email'], 'password' => $validated['password'], 'password_confirmation' => $this->password_confirmation, 'token' => $this->token],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'invitation_expires_at' => null,
                    'invitation_revoked_at' => null,
                    'remember_token' => Str::random(60),
                ])->save();
                event(new Verified($user));
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'This invitation is invalid, expired, or has already been used. Ask an administrator to resend it.');
            return;
        }

        session()->flash('status', 'Your email is verified and your password has been set. You may now sign in.');
        $this->redirectRoute('login');
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Set up your account" description="Verify your email by choosing a secure password" />
    <form wire:submit="accept" class="flex flex-col gap-6">
        <x-ui::input wire:model="email" label="Email address" type="email" readonly />
        <x-ui::input wire:model="password" label="Password" type="password" revealable required autocomplete="new-password" />
        <x-ui::input wire:model="password_confirmation" label="Confirm password" type="password" revealable required autocomplete="new-password" />
        <x-ui::button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="accept" class="w-full">
            <span wire:loading.remove wire:target="accept">Verify and activate account</span>
            <span wire:loading wire:target="accept">Activating…</span>
        </x-ui::button>
        <p class="text-center text-sm text-zinc-500">If this link is expired, ask your administrator to resend the invitation.</p>
    </form>
</div>
