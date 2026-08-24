<?php

use App\Models\User;

test('the login page prefills a valid email from the query string', function () {
    $response = $this->get(route('login', ['email' => 'superadmin@clsu.edu.ph']));

    $response
        ->assertOk()
        ->assertSee('superadmin@clsu.edu.ph');
});

test('the login page redirects credential-bearing URLs to a clean URL', function () {
    $this->get(route('login', [
        'email' => 'superadmin@clsu.edu.ph',
        'password' => 'query-secret-must-not-be-loaded',
    ]))->assertRedirect(route('login', ['email' => 'superadmin@clsu.edu.ph']));
});

test('the login page ignores an invalid email query value', function () {
    $this->get(route('login', ['email' => 'not-an-email']))
        ->assertOk()
        ->assertDontSee('not-an-email');
});

test('the login page uses the bundled Livewire client', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('[wire\\:loading]', false)
        ->assertSee('window.livewireScriptConfig', false)
        ->assertDontSee('/livewire.min.js', false)
        ->assertDontSee('/livewire.js', false);
});

test('an authenticated user cannot return to the login page', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

test('the login page links back to the welcome page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('href="'.route('home').'"', false);
});
