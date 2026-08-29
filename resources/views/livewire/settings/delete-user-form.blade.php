<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <x-ui::heading>{{ __('Delete Account') }}</x-ui::heading>
        <x-ui::subheading>{{ __('Deactivate your account for 90 days before permanent deletion') }}</x-ui::subheading>
    </div>

    <x-ui::modal.trigger name="confirm-user-deletion">
        <x-ui::button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
            {{ __('Delete Account') }}
        </x-ui::button>
    </x-ui::modal.trigger>

    <x-ui::modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form wire:submit="deleteUser" class="space-y-6">
            <div>
                <x-ui::heading size="lg">{{ __('Are you sure you want to delete your account?') }}</x-ui::heading>

                <x-ui::subheading>
                    {{ __('Your account will be deactivated immediately and retained for 90 days before it is permanently deleted. Please enter your password to confirm account deletion.') }}
                </x-ui::subheading>
            </div>

            <x-ui::input wire:model="password" id="password" label="{{ __('Password') }}" type="password" name="password" />

            <div class="flex justify-end space-x-2">
                <x-ui::modal.close>
                    <x-ui::button variant="filled">{{ __('Cancel') }}</x-ui::button>
                </x-ui::modal.close>

                <x-ui::button variant="danger" type="submit">{{ __('Delete Account') }}</x-ui::button>
            </div>
        </form>
    </x-ui::modal>
</section>
