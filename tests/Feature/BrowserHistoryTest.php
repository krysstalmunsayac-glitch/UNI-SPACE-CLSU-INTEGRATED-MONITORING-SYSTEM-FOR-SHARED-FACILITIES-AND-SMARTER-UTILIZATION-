<?php

use App\Models\User;

it('redirects authenticated users away from public entry pages', function (string $path) {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get($path)
        ->assertRedirect(route('dashboard'));
})->with(['/', '/login']);

it('prevents web pages from being cached in browser history', function () {
    $response = $this->get('/login')->assertOk();

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('must-revalidate');
});
