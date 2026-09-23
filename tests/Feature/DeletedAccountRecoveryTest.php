<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

function recoverableExternalUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'account_type' => 'external',
        'user_type' => 'user',
        'is_active' => true,
        'password' => Hash::make('password'),
    ], $attributes));
}

it('restores a self-deleted external account when it signs in within 90 days', function () {
    $user = recoverableExternalUser(['self_deleted_at' => now()->subDays(10)]);
    $user->delete();

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id)
        ->and($user->fresh()->trashed())->toBeFalse()
        ->and($user->fresh()->self_deleted_at)->toBeNull();
});

it('does not restore an account archived by an administrator', function () {
    $user = recoverableExternalUser(['self_deleted_at' => null]);
    $user->delete();

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse()
        ->and($user->fresh()->trashed())->toBeTrue();
});

it('does not restore a self-deleted account after 90 days', function () {
    $user = recoverableExternalUser(['self_deleted_at' => now()->subDays(91)]);
    $user->delete();
    $user->forceFill(['deleted_at' => now()->subDays(91)])->save();

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse()
        ->and($user->fresh()->trashed())->toBeTrue();
});
