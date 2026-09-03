<?php

use App\Models\User;
use Livewire\Volt\Volt;

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

    $component = Volt::test('user.user-management')
        ->call('edit', $user->id)
        ->set('user_type', 'admin')
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

    Volt::test('user.user-management')
        ->call('requestToggleActive', $user->id)
        ->assertSet('showQuickStatusConfirmation', true)
        ->call('confirmToggleActive')
        ->assertDispatched('swal')
        ->assertSet('showQuickStatusConfirmation', false);

    expect($user->fresh()->is_active)->toBeTrue();
});
