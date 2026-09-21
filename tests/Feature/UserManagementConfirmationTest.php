<?php

use App\Livewire\Users\UserManagement;
use App\Models\User;
use Livewire\Livewire;

it('normalizes a legacy international contact number and updates the user', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'name' => 'Legacy Contact',
        'email' => 'legacy-contact@clsu2.edu.ph',
        'contact_number' => '+639661778432',
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $this->actingAs($superAdmin);

    Livewire::test(UserManagement::class)
        ->call('edit', $user->id)
        ->assertSet('form.contact_number', '09661778432')
        ->set('form.name', 'Updated Contact')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('swal');

    expect($user->fresh())
        ->name->toBe('Updated Contact')
        ->contact_number->toBe('09661778432');
});

it('shows an error notification when an edited user is invalid', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $this->actingAs($superAdmin);

    Livewire::test(UserManagement::class)
        ->call('edit', $user->id)
        ->set('form.contact_number', '123')
        ->call('save')
        ->assertHasErrors(['form.contact_number'])
        ->assertDispatched('swal');
});

it('changes an end user to office admin only after role confirmation', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'user_type' => 'user',
        'email' => 'role-change@clsu.edu.ph',
        'is_active' => true,
        'contact_number' => '09123456789',
        'office' => 'Testing Office',
        'address' => 'Central Luzon State University',
    ]);
    $this->actingAs($superAdmin);

    $component = Livewire::test(UserManagement::class)
        ->call('edit', $user->id)
        ->set('form.user_type', 'admin')
        ->call('save')
        ->assertSet('showRoleChangeConfirmation', true);

    expect($user->fresh()->user_type)->toBe('user');

    $component
        ->call('save', true)
        ->assertHasNoErrors()
        ->assertDispatched('swal');

    expect($user->fresh()->user_type)->toBe('admin');
});

it('shows centered success feedback after activating an account', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => false,
    ]);
    $this->actingAs($superAdmin);

    Livewire::test(UserManagement::class)
        ->call('requestToggleActive', $user->id)
        ->assertSet('showQuickStatusConfirmation', true)
        ->call('confirmToggleActive')
        ->assertDispatched('swal')
        ->assertSet('showQuickStatusConfirmation', false);

    expect($user->fresh()->is_active)->toBeTrue();
});

it('identifies students and staff in the user directory', function () {
    $superAdmin = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);
    User::factory()->create([
        'name' => 'Directory Student',
        'user_type' => 'user',
        'account_type' => 'student',
    ]);
    User::factory()->create([
        'name' => 'Directory Staff',
        'user_type' => 'user',
        'account_type' => 'staff',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(UserManagement::class)
        ->assertSee('Directory Student')
        ->assertSee('Student')
        ->assertSee('Directory Staff')
        ->assertSee('Staff');
});
