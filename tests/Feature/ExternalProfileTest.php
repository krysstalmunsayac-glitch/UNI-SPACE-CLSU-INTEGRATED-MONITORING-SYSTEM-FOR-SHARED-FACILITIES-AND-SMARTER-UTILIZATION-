<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('allows an external user to save profile changes', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'email' => 'external@example.com',
    ]);

    $this->actingAs($user);

    Volt::test('settings.external-profile')
        ->set('name', '  Updated User  ')
        ->set('email', 'UPDATED@EXAMPLE.COM ')
        ->set('contact_number', '09123456789')
        ->set('address', '  Updated home address  ')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertDispatched('profile-updated');

    expect($user->fresh())
        ->name->toBe('Updated User')
        ->email->toBe('updated@example.com')
        ->contact_number->toBe('09123456789')
        ->address->toBe('Updated home address');
});

it('routes external users to the external profile component', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('profile.external'))
        ->assertOk()
        ->assertSee('Profile settings')
        ->assertSee('Delete Account');
});

it('allows an external user to delete their account after confirming their password', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'password' => Hash::make('delete-me'),
    ]);

    $this->actingAs($user);

    Volt::test('settings.delete-user-form')
        ->set('password', 'delete-me')
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertSoftDeleted($user);
    $this->assertGuest();
});

it('retains deleted accounts for 90 days before permanent deletion', function () {
    $retainedUser = User::factory()->create();
    $expiredUser = User::factory()->create();

    $retainedUser->delete();
    $expiredUser->delete();

    User::withTrashed()->whereKey($retainedUser->id)->update(['deleted_at' => now()->subDays(89)]);
    User::withTrashed()->whereKey($expiredUser->id)->update(['deleted_at' => now()->subDays(90)]);

    $this->artisan('users:purge-deleted')->assertSuccessful();

    $this->assertSoftDeleted($retainedUser);
    $this->assertDatabaseMissing('users', ['id' => $expiredUser->id]);
});

it('does not show the delete account option to administrative users', function (string $role) {
    $administrativeUser = User::factory()->create([
        'user_type' => $role,
        'is_active' => true,
    ]);

    $this->actingAs($administrativeUser)
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertDontSee('Delete Account');
})->with(['admin', 'super_admin']);
