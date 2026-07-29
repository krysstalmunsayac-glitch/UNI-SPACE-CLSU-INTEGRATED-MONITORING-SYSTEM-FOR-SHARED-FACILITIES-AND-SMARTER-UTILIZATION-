    {{-- Add/edit user modal --}}
    <flux:modal
        wire:model.self="showModal"
        class="md:w-[28rem]"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit User' : 'Add User' }}
                </flux:heading>

                <flux:subheading>
                    {{ $editingId
                        ? 'Update this user\'s details.'
                        : 'Create a new account.' }}
                </flux:subheading>
            </div>

            <div>
                <flux:input
                    wire:model="name"
                    label="Full Name"
                    placeholder="Juan Dela Cruz"
                    required
                    minlength="2"
                    maxlength="100"
                />

                @error('name')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div>
                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email"
                    placeholder="juan@clsu.edu.ph"
                    required
                    maxlength="255"
                />

                @error('email')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div>
                <flux:input
                    wire:model="password"
                    type="password"
                    label="Password"
                    :placeholder="$editingId
                        ? 'Leave blank to keep current password'
                        : 'Minimum 8 characters'"
                />

                @error('password')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div>
                <flux:input
                    wire:model="contact_number"
                    label="Contact Number"
                    type="tel"
                    placeholder="09XXXXXXXXX"
                    minlength="11"
                    maxlength="13"
                    pattern="(?:09[0-9]{9}|\+639[0-9]{9})"
                    title="Use 09XXXXXXXXX or +639XXXXXXXXX."
                />

                @error('contact_number')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div>
                <flux:input
                    wire:model="office"
                    label="Office"
                    placeholder="Enter office/department"
                    minlength="2"
                    maxlength="150"
                />

                @error('office')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div>
                <flux:input
                    wire:model="address"
                    label="Address"
                    placeholder="Enter address"
                    minlength="5"
                    maxlength="500"
                />

                @error('address')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div>
                <flux:select
                    wire:model="user_type"
                    label="Role"
                >
                    <flux:select.option value="admin">
                        Office Admin
                    </flux:select.option>

                    <flux:select.option value="user">
                        End User
                    </flux:select.option>
                </flux:select>

                @error('user_type')
                    <span class="text-sm text-red-600">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <flux:switch
                wire:model="is_active"
                label="Active account"
            />

            <div class="flex gap-2">
                <flux:button
                    wire:click="save"
                    variant="primary"
                    class="flex-1"
                >
                    {{ $editingId ? 'Update' : 'Create' }}
                </flux:button>

                <flux:button
                    wire:click="$set('showModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        wire:model.self="showRoleChangeConfirmation"
        class="md:w-[28rem]"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirm role change</flux:heading>

                <flux:subheading>
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
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button
                    wire:click="save(true)"
                    variant="primary"
                    class="flex-1"
                >
                    Confirm role change
                </flux:button>

                <flux:button
                    wire:click="$set('showRoleChangeConfirmation', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        wire:model.self="showAccountStatusConfirmation"
        class="md:w-[28rem]"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Confirm account {{ $is_active ? 'activation' : 'deactivation' }}
                </flux:heading>

                <flux:subheading>
                    Are you sure you want to {{ $is_active ? 'activate' : 'deactivate' }}
                    <span class="font-semibold">{{ $name }}</span>'s account?
                    @if ($is_active)
                        The user will regain access to the system.
                    @else
                        The user will not be able to access the system until the account is reactivated.
                    @endif
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button
                    wire:click="save(true, true)"
                    :variant="$is_active ? 'primary' : 'danger'"
                    class="flex-1"
                >
                    {{ $is_active ? 'Activate account' : 'Deactivate account' }}
                </flux:button>

                <flux:button
                    wire:click="$set('showAccountStatusConfirmation', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
