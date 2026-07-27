<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('a user can register with a valid password', function () {
    Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', '123 Main Street')
        ->set('password', 'Password1')
        ->set('password_confirmation', 'Password1')
        ->set('terms', true)
        ->call('register')
        ->assertHasNoErrors();

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('signup rejects passwords that do not meet the password requirements', function (string $password) {
    Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', '123 Main Street')
        ->set('password', $password)
        ->set('password_confirmation', $password)
        ->set('terms', true)
        ->call('register')
        ->assertHasErrors(['password']);

    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
})->with([
    'fewer than eight characters' => 'Pass1',
    'no number' => 'Password',
    'no capital letter' => 'password1',
]);
