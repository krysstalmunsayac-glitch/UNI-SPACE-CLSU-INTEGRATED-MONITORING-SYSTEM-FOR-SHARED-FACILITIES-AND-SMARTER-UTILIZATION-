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
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
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
                id="update_password_current_passwordpassword"
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
                autocomplete="new-password"
            />
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
