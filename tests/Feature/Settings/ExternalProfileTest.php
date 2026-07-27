<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('external users can view their dedicated profile page', function () {
    $user = User::factory()->create(['user_type' => 'user']);

    $this->actingAs($user)
        ->get(route('profile.external'))
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Profile');
});

test('external users can update their personal profile information', function () {
    $user = User::factory()->create(['user_type' => 'user']);

    $this->actingAs($user);

    Volt::test('settings.profile')
        ->set('name', 'External User')
        ->set('email', 'external@example.com')
        ->set('contact_number', '09123456789')
        ->set('address', 'Science City of Munoz')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh())
        ->name->toBe('External User')
        ->email->toBe('external@example.com')
        ->contact_number->toBe('09123456789')
        ->address->toBe('Science City of Munoz');
});

test('administrators cannot access the external user profile page', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->get(route('profile.external'))
        ->assertForbidden();
});
