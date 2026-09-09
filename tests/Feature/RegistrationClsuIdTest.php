<?php

use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('accepts a CLSU ID separately from the users email address', function () {
    Volt::test('auth.register')
        ->set('name', 'Mark User')
        ->set('clsu_id', '22-1773')
        ->set('email', 'mark@clsu.edu.ph')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('rejects an invalid CLSU ID format', function () {
    Volt::test('auth.register')
        ->set('name', 'Mark User')
        ->set('clsu_id', '221773')
        ->set('email', 'mark@clsu.edu.ph')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->assertHasErrors(['clsu_id' => 'regex'])
        ->assertSet('step', 1);
});

it('does not require a CLSU ID for a non-CLSU email address', function () {
    Volt::test('auth.register')
        ->set('name', 'External User')
        ->set('email', 'external@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', 'Science City of Muñoz')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('shows the CLSU ID field for clsu2 email addresses', function () {
    Volt::test('auth.register')
        ->set('email', 'student@clsu2.edu.ph')
        ->assertSee('CLSU ID');
});

it('does not allow two pending registrations to reserve the same CLSU ID', function () {
    PendingRegistration::query()->create([
        'token' => fake()->uuid(),
        'email' => 'first@clsu.edu.ph',
        'clsu_id' => '22-1773',
        'registration_data' => [],
        'pin_hash' => Hash::make('123456'),
        'pin_expires_at' => now()->addMinutes(10),
        'resend_available_at' => now()->addMinute(),
        'failed_attempts' => 0,
    ]);

    Volt::test('auth.register')
        ->set('name', 'Second User')
        ->set('clsu_id', '22-1773')
        ->set('email', 'second@clsu.edu.ph')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->assertHasErrors(['clsu_id' => 'unique'])
        ->assertSet('step', 1);
});

it('logs each email into its own account and retrieves its own identifier', function () {
    $institutionalUser = User::factory()->create([
        'email' => 'mark@clsu.edu.ph',
        'clsu_id' => '22-1773',
        'is_active' => true,
    ]);

    $personalUser = User::factory()->create([
        'email' => 'mark.personal@gmail.com',
        'clsu_id' => null,
        'is_active' => true,
    ]);

    Volt::test('auth.login')
        ->set('email', ' MARK@CLSU.EDU.PH ')
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticatedAs($institutionalUser);
    expect(auth()->user()->clsu_id)->toBe('22-1773')
        ->and(auth()->user()->accountIdentifier())->toBe('22-1773');

    auth()->logout();

    Volt::test('auth.login')
        ->set('email', $personalUser->email)
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticatedAs($personalUser);
    expect(auth()->user()->clsu_id)->toBeNull()
        ->and(auth()->user()->accountIdentifier())->toBe('USR-'.str_pad((string) $personalUser->id, 5, '0', STR_PAD_LEFT));
});

it('allows an institutional user to log in with their CLSU ID', function () {
    $institutionalUser = User::factory()->create([
        'email' => 'student@clsu.edu.ph',
        'clsu_id' => '22-1773',
        'is_active' => true,
    ]);

    Volt::test('auth.login')
        ->set('email', ' 22-1773 ')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($institutionalUser);
});

it('does not use another accounts CLSU ID to log in a personal email account', function () {
    User::factory()->create([
        'email' => 'student@clsu.edu.ph',
        'clsu_id' => '22-1773',
        'is_active' => true,
    ]);

    $personalUser = User::factory()->create([
        'email' => 'student@gmail.com',
        'clsu_id' => null,
        'is_active' => true,
    ]);

    Volt::test('auth.login')
        ->set('email', '22-1773')
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticated();
    expect(auth()->id())->not->toBe($personalUser->id)
        ->and(auth()->user()->email)->toBe('student@clsu.edu.ph');
});
