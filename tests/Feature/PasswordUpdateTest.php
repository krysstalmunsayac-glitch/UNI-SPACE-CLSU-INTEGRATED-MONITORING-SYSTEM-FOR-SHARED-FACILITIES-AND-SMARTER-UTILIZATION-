<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('allows an administrator to change their password', function () {
    $user = User::factory()->create([
        'user_type' => 'super_admin',
        'password' => Hash::make('Current1!Password'),
    ]);

    Volt::actingAs($user)
        ->test('settings.password')
        ->set('current_password', 'Current1!Password')
        ->set('password', 'Updated1!Password')
        ->set('password_confirmation', 'Updated1!Password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertDispatched('password-updated');

    expect(Hash::check('Updated1!Password', $user->fresh()->password))->toBeTrue();
});

it('allows an external user to change their password', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'password' => Hash::make('Current1!Password'),
    ]);

    Volt::actingAs($user)
        ->test('settings.external-password')
        ->set('current_password', 'Current1!Password')
        ->set('password', 'Updated1!Password')
        ->set('password_confirmation', 'Updated1!Password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertDispatched('password-updated');

    expect(Hash::check('Updated1!Password', $user->fresh()->password))->toBeTrue();
});
