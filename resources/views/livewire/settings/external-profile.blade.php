<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.home')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $contact_number = '';
    public string $address = '';
    public $profile_photo = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->contact_number = $user->contact_number ?? '';
        $this->address = $user->address ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'contact_number' => ['nullable', 'regex:'.User::PH_CONTACT_REGEX],
            'address' => ['nullable', 'string', 'min:5', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ], [
            'contact_number.regex' => 'Enter a valid PH mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        $photo = $validated['profile_photo'] ?? null;
        unset($validated['profile_photo']);

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
    }
}; ?>

<section class="border-t border-emerald-900/10 bg-emerald-50/50 py-14 dark:border-white/10 dark:bg-zinc-950 sm:py-20">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">My account</p>
            <h1 class="mt-3 text-4xl font-black tracking-tight text-emerald-950 dark:text-white sm:text-5xl">Profile settings</h1>
            <p class="mt-4 max-w-2xl text-lg text-emerald-900/70 dark:text-zinc-300">Manage the personal information used for your facility requests.</p>
        </div>

        <form wire:submit="updateProfileInformation" class="grid overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/5 dark:border-white/10 dark:bg-zinc-900 lg:grid-cols-[280px_1fr]">
            <aside class="bg-emerald-950 p-8 text-center text-white">
                <div class="mx-auto size-36 overflow-hidden rounded-full border-4 border-white/20 bg-emerald-800 shadow-lg">
                    @if ($profile_photo)
                        <img src="{{ $profile_photo->temporaryUrl() }}" alt="Profile photo preview" class="h-full w-full object-cover">
                    @elseif (auth()->user()->profile_image_url)
                        <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-4xl font-black">{{ auth()->user()->initials() }}</div>
                    @endif
                </div>

                <h2 class="mt-5 text-xl font-black">{{ auth()->user()->name }}</h2>
                <p class="mt-1 break-all text-sm text-emerald-100/70">{{ auth()->user()->email }}</p>

                <label for="external_profile_photo" class="mt-6 inline-flex cursor-pointer items-center justify-center rounded-xl bg-yellow-400 px-4 py-2.5 text-sm font-black text-emerald-950 transition hover:bg-yellow-300">
                    Change photo
                </label>
                <input id="external_profile_photo" type="file" wire:model="profile_photo" accept="image/*" class="sr-only">
                <p class="mt-3 text-xs text-emerald-100/60">JPG or PNG, up to 2 MB</p>
                <div wire:loading wire:target="profile_photo" class="mt-2 text-sm text-yellow-300">Preparing preview...</div>
                @error('profile_photo') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
            </aside>

            <div class="space-y-6 p-6 sm:p-10">
                <div class="grid gap-6 sm:grid-cols-2">
                    <x-ui::input wire:model="name" label="Full name" type="text" required minlength="2" maxlength="100" autocomplete="name" />
                    <x-ui::input wire:model="contact_number" label="Contact number" type="tel" minlength="11" maxlength="13" pattern="(?:09[0-9]{9}|\+639[0-9]{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX." placeholder="09XXXXXXXXX" autocomplete="tel" />
                </div>

                <x-ui::input wire:model="email" label="Email address" type="email" required maxlength="255" autocomplete="email" />

                <div>
                    <label for="external_address" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Address</label>
                    <textarea id="external_address" wire:model="address" rows="4" minlength="5" maxlength="500" class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" placeholder="Enter your current address"></textarea>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-emerald-900/10 pt-6 dark:border-white/10">
                    <button type="submit" class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-black text-white transition hover:bg-emerald-800 disabled:opacity-60" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="updateProfileInformation">Save changes</span>
                        <span wire:loading wire:target="updateProfileInformation">Saving...</span>
                    </button>
                    <a href="{{ route('dashboard') }}" class="rounded-xl border border-emerald-900/10 px-6 py-3 text-sm font-black text-emerald-800 transition hover:bg-emerald-50 dark:border-white/10 dark:text-emerald-300 dark:hover:bg-zinc-800">Cancel</a>
                    <x-action-message on="profile-updated" class="font-semibold text-emerald-700 dark:text-emerald-300">Profile saved successfully.</x-action-message>
                </div>
            </div>
        </form>
    </div>
</section>
