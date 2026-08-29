<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('can switch between superadmin and external accounts without a 404', function () {
    $superAdmin = User::factory()->create([
        'email' => 'superadmin@example.com',
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);

    $externalUser = User::factory()->create([
        'email' => 'external@example.com',
        'user_type' => 'user',
        'is_active' => true,
    ]);

    Volt::test('auth.login')
        ->set('email', $superAdmin->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard.superadmin', absolute: false))
        ->assertSessionHas('sweet_alert.icon', 'success');

    $this->get(route('dashboard.superadmin'))->assertOk();

    $this->post(route('logout'))
        ->assertRedirect('/')
        ->assertSessionHas('sweet_alert.title', 'Logged out');
    $this->assertGuest();

    Volt::test('auth.login')
        ->set('email', $externalUser->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))->assertOk();

    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();

    Volt::test('auth.login')
        ->set('email', $superAdmin->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard.superadmin', absolute: false));

    $this->get(route('dashboard.superadmin'))->assertOk();
});
