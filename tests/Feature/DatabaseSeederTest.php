<?php

use App\Models\User;
use App\Models\Facilities;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restores a matching archived seed user instead of violating the unique email constraint', function (): void {
    $archivedUser = User::factory()->create([
        'email' => 'ana@clsu.edu.ph',
    ]);
    $archivedUser->delete();

    $this->seed(DatabaseSeeder::class);

    $restoredUser = User::where('email', 'ana@clsu.edu.ph')->first();

    expect(User::withTrashed()->where('email', 'ana@clsu.edu.ph')->count())->toBe(1)
        ->and($restoredUser)->not->toBeNull()
        ->and($restoredUser->name)->toBe('Ana Garcia');
});

it('does not seed facilities with a known capacity below seventy', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Facilities::query()
        ->whereNotNull('Capacity')
        ->where('Capacity', '<', 70)
        ->exists())->toBeFalse();
});
