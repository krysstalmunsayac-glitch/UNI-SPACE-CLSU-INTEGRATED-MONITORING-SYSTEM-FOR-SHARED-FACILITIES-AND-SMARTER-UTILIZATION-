<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('securely changes an administrator password', function (string $role) {
    $administrator = User::factory()->create([
        'user_type' => $role,
        'is_active' => true,
        'password' => Hash::make('Current1!'),
    ]);
    $this->actingAs($administrator);

    Volt::test('settings.password')
        ->set('current_password', 'Current1!')
        ->set('password', 'NewSecure2@')
        ->set('password_confirmation', 'NewSecure2@')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertDispatched('password-updated')
        ->assertDispatched('swal');

    expect(Hash::check('NewSecure2@', $administrator->fresh()->password))->toBeTrue();
})->with(['super_admin', 'admin']);

it('rejects incorrect current passwords and weak or mismatched new passwords', function () {
    $administrator = User::factory()->create([
        'user_type' => 'admin',
        'is_active' => true,
        'password' => Hash::make('Current1!'),
    ]);
    $this->actingAs($administrator);

    Volt::test('settings.password')
        ->set('current_password', 'Wrong1!')
        ->set('password', 'weak')
        ->set('password_confirmation', 'different')
        ->call('updatePassword')
        ->assertHasErrors(['current_password', 'password']);

    expect(Hash::check('Current1!', $administrator->fresh()->password))->toBeTrue();
});
