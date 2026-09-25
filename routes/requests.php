<?php

use App\Http\Controllers\EventRequestController;
use App\Http\Controllers\FacilityRequestController;
use App\Http\Controllers\WaitingListController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
    'user.pages',
    PreventBackHistory::class,
])->group(function () {
    Route::get('reserve/{facilitySlug}', [FacilityRequestController::class, 'showRequest'])
        ->where('facilitySlug', '[A-Za-z0-9-]+')
        ->name('requests.create');

    Route::prefix('requests')
        ->name('requests.')
        ->controller(FacilityRequestController::class)
        ->group(function () {
            Route::get('create/{facility}', 'redirectLegacyRequest')
                ->name('create.legacy');

            Route::get('availability/{facility}', 'availability')
                ->middleware('throttle:60,1')
                ->name('availability');

            Route::post('create/{facility}', 'storeRequest')
                ->name('store');

        });

    Route::get('requests/events/{event}', [EventRequestController::class, 'create'])
        ->name('requests.event.create');

    Route::post('requests/events/{event}', [EventRequestController::class, 'store'])
        ->name('requests.event.store');

    Route::controller(WaitingListController::class)
        ->prefix('requests/waiting-list')
        ->group(function () {
            Route::get('/', 'waitingList')
                ->name('requests.waiting.index');

            Route::post('{requestModel}', 'updateWaitingList')
                ->name('requests.waiting.update');

            Route::post('{requestModel}/cancel', 'cancelWaitingList')
                ->name('requests.waiting.cancel');

            Route::post('{requestModel}/end', 'endWaitingList')
                ->name('requests.waiting.end');
        });
});
