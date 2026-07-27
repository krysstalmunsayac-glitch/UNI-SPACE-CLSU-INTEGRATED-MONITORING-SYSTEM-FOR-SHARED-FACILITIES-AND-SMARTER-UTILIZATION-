<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('marks all notifications as read when the notification popup is opened', function () {
    $user = User::factory()->create();
    $notificationId = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $notificationId,
        'type' => 'test-notification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['message' => 'Test notification']),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read'))
        ->assertNoContent();

    expect(DB::table('notifications')->where('id', $notificationId)->value('read_at'))
        ->not->toBeNull();
});

it('redirects external users from notifications to their facility requests', function () {
    $user = User::factory()->create(['user_type' => 'user']);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertRedirect(route('dashboard').'#requests');
});

it('redirects office admins from notifications to requests', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->get(route('notifications.index'))
        ->assertRedirect(route('Request'));
});

it('redirects super admins from notifications to requests', function () {
    $superAdmin = User::factory()->create(['user_type' => 'super_admin']);

    $this->actingAs($superAdmin)
        ->get(route('notifications.index'))
        ->assertRedirect(route('Request'));
});
