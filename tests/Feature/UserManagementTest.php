<?php

use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

it('requires confirmation before changing an existing user role', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $user = User::factory()->create(['user_type' => 'user']);

    actingAs($superAdmin);

    $component = Volt::test('user.user-management')
        ->call('edit', $user->id)
        ->set('user_type', 'admin')
        ->call('save')
        ->assertSet('showRoleChangeConfirmation', true);

    expect($user->fresh()->user_type)->toBe('user');

    $component
        ->call('save', true)
        ->assertSet('showRoleChangeConfirmation', false);

    expect($user->fresh()->user_type)->toBe('admin');
});

it('does not request role confirmation when the role is unchanged', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $user = User::factory()->create(['user_type' => 'user']);

    actingAs($superAdmin);

    Volt::test('user.user-management')
        ->call('edit', $user->id)
        ->set('name', 'Updated User Name')
        ->call('save')
        ->assertSet('showRoleChangeConfirmation', false);

    expect($user->fresh()->name)->toBe('Updated User Name');
});

it('requires confirmation before deactivating an active account', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $user = User::factory()->create(['is_active' => true]);

    actingAs($superAdmin);

    $component = Volt::test('user.user-management')
        ->call('edit', $user->id)
        ->set('is_active', false)
        ->call('save')
        ->assertSet('showAccountStatusConfirmation', true);

    expect((bool) $user->fresh()->is_active)->toBeTrue();

    $component
        ->call('save', true, true)
        ->assertSet('showAccountStatusConfirmation', false);

    expect((bool) $user->fresh()->is_active)->toBeFalse();
});

it('requires confirmation before reactivating an inactive account', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
    $user = User::factory()->create(['is_active' => false]);

    actingAs($superAdmin);

    $component = Volt::test('user.user-management')
        ->call('edit', $user->id)
        ->set('is_active', true)
        ->call('save')
        ->assertSet('showAccountStatusConfirmation', true);

    expect((bool) $user->fresh()->is_active)->toBeFalse();

    $component
        ->call('save', true, true)
        ->assertSet('showAccountStatusConfirmation', false);

    expect((bool) $user->fresh()->is_active)->toBeTrue();
});
