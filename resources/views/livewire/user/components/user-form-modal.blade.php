    {{-- Add/edit user modal --}}
    <x-ui::modal
        wire:model.self="showModal"
        class="md:w-[28rem]"
    >
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    {{ $editingId ? 'Edit User' : 'Add User' }}
                </x-ui::heading>

                <x-ui::subheading>
                    {{ $editingId
                        ? 'Update this user\'s details.'
                        : 'Create a new account.' }}
                </x-ui::subheading>
            </div>

            <div class="flex items-center gap-4">
                <div class="size-20 shrink-0 overflow-hidden rounded-full border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                    @if ($profile_photo)
                        <img src="{{ $profile_photo->temporaryUrl() }}" alt="Profile photo preview" class="h-full w-full object-cover">
                    @elseif ($existingProfilePhotoUrl)
                        <img src="{{ $existingProfilePhotoUrl }}" alt="{{ $name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xl font-bold text-zinc-600 dark:text-zinc-200">
                            {{ $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?' }}
                        </div>
                    @endif
                </div>

                <div class="min-w-0 flex-1 space-y-1.5">
                    <label for="managed_profile_photo" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                        Profile picture
                    </label>
                    <input
                        id="managed_profile_photo"
                        type="file"
                        wire:model="profile_photo"
                        accept="image/png,image/jpeg,image/webp"
                        class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100 dark:text-zinc-300 dark:file:bg-emerald-950/40 dark:file:text-emerald-300"
                    >
                    <p class="text-xs text-zinc-500">JPG, PNG, or WebP up to 2 MB.</p>
                    <div wire:loading wire:target="profile_photo" class="text-xs font-medium text-emerald-700">
                        Uploading preview…
                    </div>
                    @error('profile_photo')
                        <span class="block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div>
                <x-ui::input
                    wire:model="name"
                    label="Full Name"
                    placeholder="Juan Dela Cruz"
                    required
                    minlength="2"
                    maxlength="100"
                />

            </div>

            <div>
                <x-ui::input
                    wire:model="email"
                    type="email"
                    label="Email"
                    placeholder="juan@clsu.edu.ph"
                    required
                    maxlength="255"
                />

            </div>

            <div>
                <x-ui::input
                    wire:model="password"
                    type="password"
                    label="Password"
                    :placeholder="$editingId
                        ? 'Leave blank to keep current password'
                        : 'Minimum 8 characters'"
                />

            </div>

            <div>
                <x-ui::input
                    wire:model="contact_number"
                    label="Contact Number"
                    type="tel"
                    placeholder="09XXXXXXXXX"
                    minlength="11"
                    maxlength="13"
                    pattern="(?:09[0-9]{9}|\+639[0-9]{9})"
                    title="Use 09XXXXXXXXX or +639XXXXXXXXX."
                />

            </div>

            <div>
                <x-ui::input
                    wire:model="office"
                    label="Office"
                    placeholder="Enter office/department"
                    minlength="2"
                    maxlength="150"
                />

            </div>

            <div>
                <x-ui::input
                    wire:model="address"
                    label="Address"
                    placeholder="Enter address"
                    minlength="5"
                    maxlength="500"
                />

            </div>

            <div>
                <x-ui::select
                    wire:model="user_type"
                    label="Role"
                >
                    <x-ui::select.option value="admin">
                        Office Admin
                    </x-ui::select.option>

                    <x-ui::select.option value="user">
                        End User
                    </x-ui::select.option>
                </x-ui::select>

            </div>

            <x-ui::switch
                wire:model="is_active"
                label="Active account"
            />

            <div class="flex gap-2">
                @if($editingId)
                    <x-ui::button
                        wire:click="save"
                        variant="primary"
                        class="flex-1"
                        data-ui-confirm="Are you sure you want to save these changes to {{ $name }}?"
                        data-ui-confirm-title="Confirm user changes"
                        data-ui-confirm-label="Save changes"
                    >
                        Update
                    </x-ui::button>
                @else
                    <x-ui::button wire:click="save" variant="primary" class="flex-1">
                        Create
                    </x-ui::button>
                @endif

                <x-ui::button
                    wire:click="$set('showModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>

    <x-ui::modal wire:model.self="showCreateConfirmation" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">Confirm new user</x-ui::heading>
                <x-ui::subheading>
                    Are you sure you want to add <span class="font-semibold">{{ $name }}</span> as a new user?
                </x-ui::subheading>

            </div>
            <div class="flex gap-2">
                <x-ui::button wire:click="save(true, false, true)" variant="primary" class="flex-1">Add user</x-ui::button>
                <x-ui::button wire:click="$set('showCreateConfirmation', false)" variant="ghost" class="flex-1">Cancel</x-ui::button>
            </div>
        </div>
    </x-ui::modal>

    <x-ui::modal
        wire:model.self="showRoleChangeConfirmation"
        class="md:w-[28rem]"
    >
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">{{ $editingId ? 'Confirm role change' : 'Confirm Office Admin role' }}</x-ui::heading>

                <x-ui::subheading>
                    @if ($editingId)
                        You are about to change this user's role from
                        <span class="font-semibold">{{ match ($originalUserType) {
                            'super_admin' => 'Super Admin',
                            'admin' => 'Office Admin',
                            default => 'End User',
                        } }}</span>
                        to
                        <span class="font-semibold">{{ match ($user_type) {
                            'super_admin' => 'Super Admin',
                            'admin' => 'Office Admin',
                            default => 'End User',
                        } }}</span>.
                        This will change the user's system access and permissions.
                    @else
                        You are creating <span class="font-semibold">{{ $name }}</span> as an Office Admin.
                        Office Admins can manage assigned facilities, requests, schedules, amenities, and feedback.
                    @endif
                </x-ui::subheading>
            </div>

            <div class="flex gap-2">
                <x-ui::button
                    wire:click="save(true)"
                    variant="primary"
                    class="flex-1"
                >
                    {{ $editingId ? 'Confirm role change' : 'Confirm Office Admin role' }}
                </x-ui::button>

                <x-ui::button
                    wire:click="$set('showRoleChangeConfirmation', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>

    <x-ui::modal
        wire:model.self="showAccountStatusConfirmation"
        class="md:w-[28rem]"
    >
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    Confirm account {{ $is_active ? 'activation' : 'deactivation' }}
                </x-ui::heading>

                <x-ui::subheading>
                    Are you sure you want to {{ $is_active ? 'activate' : 'deactivate' }}
                    <span class="font-semibold">{{ $name }}</span>'s account?
                    @if ($is_active)
                        The user will regain access to the system.
                    @else
                        The user will not be able to access the system until the account is reactivated.
                    @endif
                </x-ui::subheading>

                @if (! $is_active)
                    <div class="mt-4">
                        <x-ui::input
                            wire:model="deactivationConfirmation"
                            label="Type DEACTIVATE to confirm"
                            placeholder="DEACTIVATE"
                            autocomplete="off"
                        />
                    </div>
                @endif
            </div>

            <div class="flex gap-2">
                <x-ui::button
                    wire:click="save(true, true)"
                    :variant="$is_active ? 'primary' : 'danger'"
                    class="flex-1"
                >
                    {{ $is_active ? 'Activate account' : 'Deactivate account' }}
                </x-ui::button>

                <x-ui::button
                    wire:click="$set('showAccountStatusConfirmation', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>
