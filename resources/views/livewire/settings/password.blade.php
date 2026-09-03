<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => [
                    'required',
                    'string',
                    'different:current_password',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols(),
                ],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
        $this->dispatch('swal', [
            'title' => 'Password updated',
            'text' => 'Your new password is now active.',
            'icon' => 'success',
        ]);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Update password" subheading="Ensure your account is using a long, random password to stay secure">
        <form wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-ui::input
                wire:model="current_password"
                id="update_password_current_password"
                label="{{ __('Current password') }}"
                type="password"
                revealable
                name="current_password"
                required
                autocomplete="current-password"
            />
            <x-ui::input
                wire:model="password"
                id="update_password_password"
                label="{{ __('New password') }}"
                type="password"
                revealable
                name="password"
                required
                minlength="8"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}"
                title="Use at least 8 characters with uppercase, lowercase, a number, and a special character."
                autocomplete="new-password"
            />
            <p class="-mt-3 text-xs font-semibold leading-5 text-emerald-900/60 dark:text-zinc-400">
                Use at least 8 characters with uppercase, lowercase, a number, and a special character (such as !, @, #, or $). The new password must be different from your current password.
            </p>
            <x-ui::input
                wire:model="password_confirmation"
                id="update_password_password_confirmation"
                label="{{ __('Confirm Password') }}"
                type="password"
                revealable
                name="password_confirmation"
                required
                autocomplete="new-password"
            />

            <div class="flex flex-col items-center gap-4 text-center">
                <div class="flex items-center justify-end">
                    <x-ui::button variant="primary" type="submit" class="w-full" wire:loading.attr="disabled" wire:target="updatePassword" data-ui-confirm="Change your account password now?" data-ui-confirm-title="Confirm password update" data-ui-confirm-label="Update password">{{ __('Update password') }}</x-ui::button>
                </div>

                <x-action-message on="password-updated" class="font-semibold text-emerald-700 dark:text-emerald-300">
                    {{ __('Password updated successfully.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
