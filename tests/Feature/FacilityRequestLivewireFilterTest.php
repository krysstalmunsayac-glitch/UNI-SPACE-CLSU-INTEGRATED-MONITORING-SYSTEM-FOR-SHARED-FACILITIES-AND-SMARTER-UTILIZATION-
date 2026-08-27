<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('updates facility request filters reactively without an apply button', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Volt::test('facility-request-list')
        ->assertSeeHtml('wire:model.live="requestSort"')
        ->assertSeeHtml('wire:model.live="requestStatus"')
        ->assertDontSee('Apply filters')
        ->set('requestSort', 'oldest')
        ->set('requestStatus', 'Pending')
        ->assertSet('requestSort', 'oldest')
        ->assertSet('requestStatus', 'Pending')
        ->assertHasNoErrors();
});

it('rejects unsupported facility request filter values', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Volt::test('facility-request-list')
        ->set('requestSort', 'unsupported')
        ->assertSet('requestSort', 'latest')
        ->set('requestStatus', 'unsupported')
        ->assertSet('requestStatus', '');
});
