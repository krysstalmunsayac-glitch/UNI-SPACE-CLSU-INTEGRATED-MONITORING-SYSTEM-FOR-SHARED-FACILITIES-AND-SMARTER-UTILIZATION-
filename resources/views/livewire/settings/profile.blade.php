<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $contact_number = '';
    public string $office = '';
    public string $address = '';
    public $profile_photo = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->office = Auth::user()->office ?? '';
        $this->address = Auth::user()->address ?? '';
        $this->contact_number = Auth::user()->contact_number ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'contact_number' => ['nullable', 'regex:'.User::PH_CONTACT_REGEX],
            'office' => ['nullable', 'string', 'min:2', 'max:150'],
            'address' => ['nullable', 'string', 'min:5', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ], [
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        $photo = $validated['profile_photo'] ?? null;
        unset($validated['profile_photo']);

        if ($user->hasrole('user')) {
            unset($validated['office']);
        } else {
            unset($validated['address']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($photo) {
            if ($user->ImageID && ! filter_var($user->ImageID, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->ImageID);
            }

            $user->ImageID = $photo->store('profile-photos', 'public');
            $this->profile_photo = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
        $this->dispatch('swal', [
            'title' => 'Profile updated',
            'text' => 'Your profile information was saved successfully.',
            'icon' => 'success',
        ]);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your name and email address">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="size-24 overflow-hidden rounded-full border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                    @if ($profile_photo)
                        <img src="{{ $profile_photo->temporaryUrl() }}" alt="Profile photo preview" class="h-full w-full object-cover">
                    @elseif (auth()->user()->profile_image_url)
                        <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xl font-bold text-zinc-600 dark:text-zinc-200">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                </div>

                <div class="space-y-2">
                    <label for="profile_photo" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">Profile picture</label>
                    <input
                        id="profile_photo"
                        type="file"
                        wire:model="profile_photo"
                        accept="image/*"
                        class="block w-full max-w-sm rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-[#009639] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                    <div wire:loading wire:target="profile_photo" class="text-sm text-zinc-500">Uploading preview...</div>
                    @error('profile_photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <x-ui::input wire:model="name" label="{{ __('Name') }}" type="text" name="name" required minlength="2" maxlength="100" autofocus autocomplete="name" />    
            <x-ui::input wire:model="contact_number" label="Contact Number" type="tel" name="contact_number" minlength="11" maxlength="13" pattern="(?:09[0-9]{9}|\+639[0-9]{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX." placeholder="09123456789" autocomplete="tel" />
            @if (auth()->user()->hasrole('user'))
                <x-ui::textarea wire:model="address" label="Address" name="address" rows="3" />
            @else
                <x-ui::input wire:model="office" label="Office" type="text" name="office" minlength="2" maxlength="150"/>
            @endif

            <div>
                <x-ui::input wire:model="email" label="{{ __('Email') }}" type="email" name="email" required maxlength="255" autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button
                                wire:click.prevent="resendVerificationNotification"
                                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-ui::button
                        variant="primary"
                        type="submit"
                        class="w-full"
                        wire:loading.attr="disabled"
                        wire:target="updateProfileInformation"
                        data-ui-confirm="Save these changes to your profile information?"
                        data-ui-confirm-title="Confirm profile update"
                        data-ui-confirm-label="Save changes"
                    >
                        <span wire:loading.remove wire:target="updateProfileInformation">{{ __('Save') }}</span>
                        <span wire:loading wire:target="updateProfileInformation">Saving...</span>
                    </x-ui::button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if (auth()->user()->hasrole('user'))
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
