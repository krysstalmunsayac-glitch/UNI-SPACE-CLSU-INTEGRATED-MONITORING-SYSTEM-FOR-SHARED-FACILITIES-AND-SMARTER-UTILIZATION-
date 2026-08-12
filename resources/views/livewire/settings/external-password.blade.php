<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.home')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $exception;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('password-updated');
    }
}; ?>

<section class="border-t border-emerald-900/10 bg-emerald-50/50 py-14 dark:border-white/10 dark:bg-zinc-950 sm:py-20">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('profile.external') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900 dark:text-emerald-300">← Back to profile</a>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-emerald-950 dark:text-white">Change password</h1>
            <p class="mt-3 text-emerald-900/70 dark:text-zinc-300">Use a strong password that you do not reuse on other accounts.</p>
        </div>

        <form wire:submit="updatePassword" class="space-y-6 rounded-3xl border border-emerald-900/10 bg-white p-6 shadow-xl shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-900 sm:p-8">
            <x-ui::input wire:model="current_password" label="Current password" type="password" required autocomplete="current-password" />
            <x-ui::input wire:model="password" label="New password" type="password" required autocomplete="new-password" />
            <x-ui::input wire:model="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />

            <div class="flex flex-wrap items-center gap-4 border-t border-emerald-900/10 pt-6 dark:border-white/10">
                <x-ui::button variant="primary" type="submit">Update password</x-ui::button>
                <a href="{{ route('profile.external') }}" class="rounded-xl border border-emerald-900/10 px-5 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 dark:border-white/10 dark:text-emerald-300 dark:hover:bg-zinc-800">Cancel</a>
                <x-action-message on="password-updated" class="font-semibold text-emerald-700 dark:text-emerald-300">Password updated successfully.</x-action-message>
            </div>
        </form>
    </div>
</section>
