<?php

use App\Http\Controllers\EventsController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware([
    'auth',
    PreventBackHistory::class,
])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read');

    Volt::route('/profile', 'settings.profile')
        ->middleware('role:user')
        ->name('profile.external');

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    Route::redirect('/settings', '/settings/profile');

    Route::prefix('settings')
        ->name('settings.')
        ->group(function () {
            Volt::route('/profile', 'settings.profile')
                ->name('profile');

            Volt::route('/password', 'settings.password')
                ->name('password');

            Volt::route('/appearance', 'settings.appearance')
                ->name('appearance');
        });

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    Route::controller(EventsController::class)
        ->prefix('event')
        ->name('events.')
        ->group(function () {
            Route::get('/create', 'create')
                ->name('create');

            Route::post('/', 'store')
                ->name('store');
        });
});
