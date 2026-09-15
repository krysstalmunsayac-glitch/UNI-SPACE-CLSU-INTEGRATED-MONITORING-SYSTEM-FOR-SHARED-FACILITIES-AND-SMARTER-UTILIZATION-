<?php

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyPendingRegistration;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;

function validRegistration(string $type, string $id, string $email): Testable
{
    return Volt::test('auth.register')
        ->set('account_type', $type)
        ->set('privacy_consent', true)
        ->set('name', 'Registration Tester')
        ->set('clsu_id', $id)
        ->set('email', $email)
        ->set('password', 'Secure1!Password')
        ->set('password_confirmation', 'Secure1!Password')
        ->set('contact_number', '09123456789')
        ->set('address', 'Science City of Munoz, Nueva Ecija')
        ->set('terms', true);
}

it('accepts the staff ID format and sends a PIN to a clsu.edu.ph address', function () {
    Notification::fake();

    validRegistration('staff', '0987654316', 'faculty@clsu.edu.ph')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(PendingRegistration::query()->where('email', 'faculty@clsu.edu.ph')->value('clsu_id'))
        ->toBe('09876543-16');
    Notification::assertSentOnDemand(VerifyPendingRegistration::class);
});

it('accepts the six-digit ID format for staff', function () {
    Notification::fake();

    validRegistration('staff', '221234', 'staff@clsu.edu.ph')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(PendingRegistration::query()->where('email', 'staff@clsu.edu.ph')->value('clsu_id'))
        ->toBe('22-1234');
});

it('accepts a clsu2.edu.ph email address for staff', function () {
    Notification::fake();

    validRegistration('staff', '0987654316', 'staff@clsu2.edu.ph')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();
});

it('keeps the student ID format separate from staff IDs', function () {
    Notification::fake();

    validRegistration('student', '221234', 'student@clsu2.edu.ph')
        ->call('register')
        ->assertHasNoErrors();

    expect(PendingRegistration::query()->where('email', 'student@clsu2.edu.ph')->value('clsu_id'))
        ->toBe('22-1234');
});

it('recognizes both official CLSU email domains', function () {
    expect(User::usesClsuEmail('faculty@clsu.edu.ph'))->toBeTrue()
        ->and(User::usesClsuEmail('student@clsu2.edu.ph'))->toBeTrue()
        ->and(User::usesClsuEmail('person@example.com'))->toBeFalse();
});

it('blocks CLSU email addresses from external user registration', function (string $email) {
    Notification::fake();

    validRegistration('external', '', $email)
        ->call('register')
        ->assertHasErrors(['email']);
})->with([
    'primary CLSU domain' => 'person@clsu.edu.ph',
    'secondary CLSU domain' => 'person@clsu2.edu.ph',
]);

it('allows a non-CLSU email for external user registration', function () {
    Notification::fake();

    validRegistration('external', '', 'person@gmail.com')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();
});
