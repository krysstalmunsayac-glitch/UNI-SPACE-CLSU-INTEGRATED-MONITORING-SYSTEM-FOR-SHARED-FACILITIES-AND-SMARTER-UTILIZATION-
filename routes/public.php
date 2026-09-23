<?php

use App\Http\Controllers\PublicSite\FacilityController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->middleware([RedirectIfAuthenticated::class, 'user.pages'])
    ->name('home');

Route::get('/facilities/{facility}', [FacilityController::class, 'show'])
    ->whereNumber('facility')
    ->name('facilities.show');

Route::redirect('/about', '/#about')
    ->name('about');

Route::view('/terms-and-conditions', 'pages.terms')
    ->name('terms');
