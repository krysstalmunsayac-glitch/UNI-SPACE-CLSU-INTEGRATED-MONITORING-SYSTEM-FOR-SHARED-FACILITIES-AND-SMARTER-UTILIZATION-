<?php

use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Middleware\PreventBackHistory;
use App\Support\SiteVersion;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->middleware(['user.pages', PreventBackHistory::class])
    ->name('home');

Route::redirect('/about', '/#about')
    ->name('about');

Route::view('/terms-and-conditions', 'pages.terms')
    ->name('terms');

Route::get('/site-version', fn () => response()
    ->json(['version' => SiteVersion::current()])
    ->header('Cache-Control', 'no-store, no-cache, must-revalidate'))
    ->name('site.version');
