<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

function activeUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'is_active' => true,
        'password' => Hash::make('password'),
    ], $attributes));
}

test('an active user can log in with a normalized email', function () {
    $user = activeUser(['email' => 'person@example.com']);

    Volt::test('auth.login')
        ->set('email', '  PERSON@EXAMPLE.COM  ')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    expect(Auth::id())->toBe($user->id);
});

test('login uses a full redirect instead of caching the form in Livewire navigation', function () {
    activeUser(['email' => 'redirect@example.com']);

    $component = Volt::test('auth.login')
        ->set('email', 'redirect@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard', absolute: false));

    expect($component->effects['redirectUsingNavigate'] ?? false)->toBeFalse();
});

test('incorrect credentials are rejected', function () {
    activeUser(['email' => 'person@example.com']);

    Volt::test('auth.login')
        ->set('email', 'person@example.com')
        ->set('password', 'incorrect-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

test('inactive accounts cannot log in', function () {
    activeUser([
        'email' => 'inactive@example.com',
        'is_active' => false,
    ]);

    Volt::test('auth.login')
        ->set('email', 'inactive@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

test('authenticated users are routed to the dashboard for their role', function (string $role, string $route) {
    $user = activeUser(['user_type' => $role]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($route));
})->with([
    'super admin' => ['super_admin', 'dashboard.superadmin'],
    'office admin' => ['admin', 'dashboard.officeadmin'],
]);

test('remember me rotates and stores a remember token', function () {
    $user = activeUser([
        'email' => 'remember@example.com',
        'remember_token' => null,
    ]);

    Volt::test('auth.login')
        ->set('email', 'remember@example.com')
        ->set('password', 'password')
        ->set('remember', true)
        ->call('login')
        ->assertHasNoErrors();

    expect($user->fresh()->remember_token)->not->toBeNull();
});

test('repeated failed logins are rate limited', function () {
    $email = 'limited@example.com';
    activeUser(['email' => $email]);

    foreach (range(1, 5) as $attempt) {
        Volt::test('auth.login')
            ->set('email', $email)
            ->set('password', 'incorrect-password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    $key = Str::transliterate(Str::lower($email).'|127.0.0.1');

    expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();
});
