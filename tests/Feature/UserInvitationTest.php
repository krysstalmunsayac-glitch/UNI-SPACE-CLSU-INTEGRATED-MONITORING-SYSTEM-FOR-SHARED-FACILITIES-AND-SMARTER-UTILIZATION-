<?php

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;

function invitedUser(array $attributes = []): array
{
    $user = User::factory()->unverified()->create(array_merge([
        'email' => 'invitee@clsu.edu.ph',
        'is_active' => false,
        'invitation_sent_at' => now(),
        'invitation_expires_at' => now()->addHour(),
        'invitation_revoked_at' => null,
    ], $attributes));

    return [$user, Password::broker()->createToken($user)];
}

it('normalizes an email and sends an invitation instead of creating an admin password', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);

    $this->actingAs($admin);

    Volt::test('user.user-management')
        ->call('create')
        ->set('name', '  Maria Santos  ')
        ->set('email', '  MARIA@EXAMPLE.COM  ')
        ->set('user_type', 'user')
        ->call('save')
        ->assertSet('showCreateConfirmation', true)
        ->call('save', true, false, true)
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

    expect($user->name)->toBe('Maria Santos')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->is_active)->toBeFalse()
        ->and($user->invitationStatus())->toBe('Invitation Pending');
    Notification::assertSentTo($user, UserInvitationNotification::class);
});

it('rejects invalid duplicate archived and non CLSU administrative emails', function () {
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    User::factory()->create(['email' => 'existing@example.com']);
    $archived = User::factory()->create(['email' => 'archived@example.com']);
    $archived->delete();
    $this->actingAs($admin);

    Volt::test('user.user-management')->call('create')
        ->set('name', 'Example User')->set('email', 'not-an-email')->set('user_type', 'user')
        ->call('save')->assertHasErrors(['email']);

    Volt::test('user.user-management')->call('create')
        ->set('name', 'Example User')->set('email', 'EXISTING@EXAMPLE.COM')->set('user_type', 'user')
        ->call('save')->assertHasErrors(['email']);

    Volt::test('user.user-management')->call('create')
        ->set('name', 'Example User')->set('email', 'ARCHIVED@EXAMPLE.COM')->set('user_type', 'user')
        ->call('save')->assertHasErrors(['email']);

    Volt::test('user.user-management')->call('create')
        ->set('name', 'Office Admin')->set('email', 'admin@example.com')->set('office', 'Registrar')->set('user_type', 'admin')
        ->call('save')->assertHasErrors(['email']);
});

it('accepts a valid invitation once and activates the verified user', function () {
    [$user, $token] = invitedUser();

    Volt::withQueryParams(['email' => $user->email])
        ->test('auth.accept-invitation', ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'ValidPassword!123')
        ->set('password_confirmation', 'ValidPassword!123')
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('ValidPassword!123', $user->password))->toBeTrue()
        ->and(Password::broker()->tokenExists($user, $token))->toBeFalse();
});

it('rejects expired revoked invalid and reused invitations', function () {
    [$expired, $expiredToken] = invitedUser([
        'email' => 'expired@example.com',
        'invitation_expires_at' => now()->subMinute(),
    ]);

    Volt::withQueryParams(['email' => $expired->email])
        ->test('auth.accept-invitation', ['token' => $expiredToken])
        ->set('email', $expired->email)
        ->set('password', 'ValidPassword!123')->set('password_confirmation', 'ValidPassword!123')
        ->call('accept')->assertHasErrors(['email']);

    [$revoked, $revokedToken] = invitedUser(['email' => 'revoked@example.com', 'invitation_revoked_at' => now()]);
    Volt::withQueryParams(['email' => $revoked->email])
        ->test('auth.accept-invitation', ['token' => $revokedToken])
        ->set('email', $revoked->email)
        ->set('password', 'ValidPassword!123')->set('password_confirmation', 'ValidPassword!123')
        ->call('accept')->assertHasErrors(['email']);

    [$invalid, $validToken] = invitedUser(['email' => 'invalid@example.com']);
    Volt::withQueryParams(['email' => $invalid->email])
        ->test('auth.accept-invitation', ['token' => 'altered-'.$validToken])
        ->set('email', $invalid->email)
        ->set('password', 'ValidPassword!123')->set('password_confirmation', 'ValidPassword!123')
        ->call('accept')->assertHasErrors(['email']);
});

it('invalidates the previous token when an invitation is regenerated', function () {
    Notification::fake();
    [$user, $oldToken] = invitedUser(['email' => 'replace@example.com']);

    app(UserInvitationService::class)->send($user);

    expect(Password::broker()->tokenExists($user, $oldToken))->toBeFalse();
    Notification::assertSentTo($user, UserInvitationNotification::class);
});

it('sends a fresh invitation after an email change without changing the password', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    $user = User::factory()->create([
        'email' => 'before@example.com',
        'user_type' => 'user',
        'is_active' => true,
        'contact_number' => '09123456789',
        'office' => 'Student Affairs',
        'address' => 'Central Luzon State University',
    ]);
    $passwordHash = $user->password;
    $this->actingAs($admin);

    Volt::test('user.user-management')->call('edit', $user->id)
        ->set('email', '  AFTER@EXAMPLE.COM ')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->email)->toBe('after@example.com')
        ->and($user->password)->toBe($passwordHash)
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->is_active)->toBeFalse();
    Notification::assertSentTo($user, UserInvitationNotification::class);
});

it('blocks unverified users and permits login after password setup', function () {
    [$user, $token] = invitedUser(['email' => 'login@example.com']);

    Volt::test('auth.login')->set('email', $user->email)->set('password', 'password')
        ->call('login')->assertHasErrors(['email']);

    Volt::withQueryParams(['email' => $user->email])
        ->test('auth.accept-invitation', ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'ValidPassword!123')->set('password_confirmation', 'ValidPassword!123')
        ->call('accept');

    Volt::test('auth.login')->set('email', $user->email)->set('password', 'ValidPassword!123')
        ->call('login')->assertHasNoErrors();
    $this->assertAuthenticatedAs($user->fresh());
});

it('protects invitation pages with signed URLs and user management authorization', function () {
    [$user, $token] = invitedUser(['email' => 'signed@example.com']);
    $url = URL::temporarySignedRoute('invitation.accept', now()->addHour(), ['token' => $token, 'email' => $user->email]);

    $this->get($url)->assertOk();
    $this->get(str_replace('email=signed%40example.com', 'email=altered%40example.com', $url))->assertForbidden();

    $ordinaryUser = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $this->actingAs($ordinaryUser)->get(route('UserManagement'))->assertForbidden();
});

it('rate limits invitation resends', function () {
    Notification::fake();
    $admin = User::factory()->create(['user_type' => 'super_admin', 'is_active' => true]);
    [$user] = invitedUser(['email' => 'limited@example.com']);
    $this->actingAs($admin);

    $component = Volt::test('user.user-management');
    $component->call('resendInvitation', $user->id);
    $component->call('resendInvitation', $user->id);
    $component->call('resendInvitation', $user->id);
    $sentAt = $user->fresh()->invitation_sent_at;
    $component->call('resendInvitation', $user->id);

    expect($user->fresh()->invitation_sent_at->equalTo($sentAt))->toBeTrue();
});
