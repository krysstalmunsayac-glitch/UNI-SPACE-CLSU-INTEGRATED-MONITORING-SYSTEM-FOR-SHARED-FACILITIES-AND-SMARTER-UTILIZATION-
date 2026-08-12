<?php

use App\Models\User;
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
        ->assertSee('Profile settings');
});
