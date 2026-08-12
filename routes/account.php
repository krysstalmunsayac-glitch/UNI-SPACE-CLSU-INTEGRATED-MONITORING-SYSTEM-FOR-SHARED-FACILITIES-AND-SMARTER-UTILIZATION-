<?php

use App\Http\Controllers\EventsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FacilitiesController;
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

    Route::get('/notifications/recent', [NotificationController::class, 'recent'])
        ->name('notifications.recent');

    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::post('/notifications/read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read');

    Route::get('/requests/{requestModel}/attachment', [FacilitiesController::class, 'downloadAttachment'])
        ->middleware('throttle:30,1')
        ->name('requests.attachment.download');

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

        });

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    Route::controller(EventsController::class)
        ->prefix('event')
        ->name('events.')
        ->middleware(['verified', 'user.pages'])
        ->group(function () {
            Route::get('/create', 'create')
                ->name('create');

            Route::post('/', 'store')
                ->name('store');
        });
});
