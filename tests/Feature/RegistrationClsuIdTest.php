<?php

use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

it('accepts a CLSU ID separately from the users email address', function () {
    Volt::test('auth.register')
        ->set('account_type', 'student')
        ->set('privacy_consent', true)
        ->set('name', 'Mark User')
        ->set('clsu_id', '22-1773')
        ->set('email', 'mark@clsu2.edu.ph')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 3);
});

it('rejects an invalid CLSU ID format', function () {
    Volt::test('auth.register')
        ->set('account_type', 'student')
        ->set('privacy_consent', true)
        ->set('name', 'Mark User')
        ->set('clsu_id', '221773')
        ->set('email', 'mark@clsu2.edu.ph')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasErrors(['clsu_id' => 'regex'])
        ->assertSet('step', 2);
});

it('does not require a CLSU ID for a non-CLSU email address', function () {
    Volt::test('auth.register')
        ->set('account_type', 'external')
        ->set('privacy_consent', true)
        ->set('name', 'External User')
        ->set('email', 'external@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', 'Science City of Muñoz')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 3);
});

it('shows the CLSU ID field for clsu2 email addresses', function () {
    Volt::test('auth.register')
        ->set('account_type', 'student')
        ->set('privacy_consent', true)
        ->call('nextStep')
        ->set('email', 'student@clsu2.edu.ph')
        ->assertSee('CLSU ID');
});

it('does not allow two pending registrations to reserve the same CLSU ID', function () {
    PendingRegistration::query()->create([
        'token' => fake()->uuid(),
        'email' => 'first@clsu2.edu.ph',
        'clsu_id' => '22-1773',
        'registration_data' => [],
        'pin_hash' => Hash::make('123456'),
        'pin_expires_at' => now()->addMinutes(10),
        'resend_available_at' => now()->addMinute(),
        'failed_attempts' => 0,
    ]);

    Volt::test('auth.register')
        ->set('account_type', 'student')
        ->set('privacy_consent', true)
        ->set('name', 'Second User')
        ->set('clsu_id', '22-1773')
        ->set('email', 'second@clsu2.edu.ph')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasErrors(['clsu_id' => 'unique'])
        ->assertSet('step', 2);
});

it('requires an account classification', function () {
    Volt::test('auth.register')
        ->set('name', 'Unclassified User')
        ->set('email', 'user@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', 'Science City of Muñoz')
        ->call('nextStep')
        ->assertHasErrors(['account_type' => 'required']);
});

it('requires privacy consent before collecting registration details', function () {
    Volt::test('auth.register')
        ->set('account_type', 'external')
        ->assertSee('View Full Data Privacy Notice')
        ->call('nextStep')
        ->assertHasErrors(['privacy_consent' => 'accepted'])
        ->assertSet('step', 1);
});

it('records privacy consent metadata with the pending registration', function () {
    Notification::fake();

    Volt::test('auth.register')
        ->set('account_type', 'external')
        ->set('privacy_consent', true)
        ->call('nextStep')
        ->set('name', 'Privacy Test User')
        ->set('email', 'privacy@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', 'Science City of Muñoz')
        ->call('nextStep')
        ->set('password', 'Secure!Pass1')
        ->set('password_confirmation', 'Secure!Pass1')
        ->set('terms', true)
        ->call('register')
        ->assertHasNoErrors();

    $registration = PendingRegistration::query()
        ->where('email', 'privacy@example.com')
        ->firstOrFail()
        ->registration_data;

    expect($registration['privacy_consent'])->toBeTrue()
        ->and($registration['privacy_notice_version'])->toBe(User::PRIVACY_NOTICE_VERSION)
        ->and($registration['privacy_consented_at'])->not->toBeEmpty();
});

it('requires staff and students to use a CLSU email', function (string $accountType) {
    Volt::test('auth.register')
        ->set('account_type', $accountType)
        ->set('privacy_consent', true)
        ->set('name', 'Institutional User')
        ->set('clsu_id', '22-1773')
        ->set('email', 'user@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', 'Central Luzon State University')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasErrors(['email']);
})->with(['staff', 'student']);

it('logs each email into its own account and retrieves its own identifier', function () {
    $institutionalUser = User::factory()->create([
        'email' => 'mark@clsu2.edu.ph',
        'clsu_id' => '22-1773',
        'is_active' => true,
    ]);

    $personalUser = User::factory()->create([
        'email' => 'mark.personal@gmail.com',
        'clsu_id' => null,
        'is_active' => true,
    ]);

    Volt::test('auth.login')
        ->set('email', ' MARK@CLSU2.EDU.PH ')
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
        'email' => 'student@clsu2.edu.ph',
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
        'email' => 'student@clsu2.edu.ph',
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
        ->and(auth()->user()->email)->toBe('student@clsu2.edu.ph');
});
