<?php

use App\Models\User;
use App\Models\Requests;
use Livewire\Volt\Volt;

it('renders request time dropdowns without leftover input markup', function () {
    $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
    $this->actingAs($user);
    Requests::create([
        'User_ID' => $user->id,
        'Proposed_Date' => today()->addWeek()->toDateString(),
        'Proposed_End_Date' => today()->addWeek()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '10:00',
        'Status' => 'Pending',
        'Purpose' => 'Time dropdown rendering test',
    ]);

    Volt::test('facility-request-list')
        ->assertSeeHtml('name="Proposed_Start_Time" value="09:00"')
        ->assertSeeHtml('name="Proposed_End_Time" value="10:00"')
        ->assertSeeHtml('size="6"')
        ->assertDontSee('Proposed_Start_Time?->format', false)
        ->assertDontSee('Proposed_End_Time?->format', false);
});

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
