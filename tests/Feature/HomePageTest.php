<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('home page loads even when the schedules table is missing', function () {
    Schema::dropIfExists('schedules');

    $this->get('/')
        ->assertOk();
});

test('authenticated users can open the homepage and its about section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('id="about"', false)
        ->assertSee('About UNI Space');
});
