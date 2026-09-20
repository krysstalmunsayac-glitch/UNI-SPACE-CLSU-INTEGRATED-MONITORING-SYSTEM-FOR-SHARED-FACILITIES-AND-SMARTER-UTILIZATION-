<?php

use App\Http\Controllers\FacilityRequestController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
    'user.pages',
    PreventBackHistory::class,
])->group(function () {
    Route::prefix('requests')
        ->name('requests.')
        ->controller(FacilityRequestController::class)
        ->group(function () {
            Route::get('create/{facility}', 'showRequest')
                ->name('create');

            Route::get('availability/{facility}', 'availability')
                ->middleware('throttle:60,1')
                ->name('availability');

            Route::post('create/{facility}', 'storeRequest')
                ->name('store');

            Route::get('event/create/{event}', 'showEventRequest')
                ->name('event.create');

            Route::post('event/create/{event}', 'storeEventRequest')
                ->name('event.store');
        });

    Route::controller(FacilityRequestController::class)
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
