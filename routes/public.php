<?php

use App\Http\Controllers\PublicSite\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->name('home');

Route::redirect('/about', '/#about')
    ->name('about');

Route::view('/terms-and-conditions', 'pages.terms')
    ->name('terms');
