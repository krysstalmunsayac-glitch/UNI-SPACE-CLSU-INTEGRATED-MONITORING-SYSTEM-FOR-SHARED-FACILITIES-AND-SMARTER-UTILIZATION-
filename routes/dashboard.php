<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\FeedbacksController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware([
    'auth',
    'verified',
    PreventBackHistory::class,
])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Main Dashboard Routes
    |--------------------------------------------------------------------------
    */

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')
            ->middleware('role.dashboard')
            ->name('dashboard');

        Route::get('/dashboard/super-admin', 'superAdmin')
            ->middleware('role:super_admin')
            ->name('dashboard.superadmin');

        Route::get('/dashboard/office-admin', 'officeAdmin')
            ->middleware('role:office_admin')
            ->name('dashboard.officeadmin');
    });

    Route::post('/dashboard/requests/{facilityRequest}/feedback', [FeedbacksController::class, 'store'])
        ->name('facility-feedback.store');

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super_admin')->group(function () {
        Volt::route('/user-management', 'user.user-management')
            ->name('UserManagement');

        Volt::route('/facility/super-admin', 'facility.super-admin-facility')
            ->name('Facility.SuperAdmin');
    });

    /*
    |--------------------------------------------------------------------------
    | Office Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:office_admin')->group(function () {
        Volt::route('/facility/office-admin', 'facility.office-admin-facility')
            ->name('Facility.OfficeAdmin');
    });

    /*
    |--------------------------------------------------------------------------
    | Shared Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super_admin,office_admin')->group(function () {
        Route::prefix('exports')->name('exports.')->controller(ReportExportController::class)->group(function () {
            Route::get('/facilities.csv', 'facilitiesCsv')->name('facilities.csv');
            Route::get('/facilities.pdf', 'facilitiesPdf')->name('facilities.pdf');
            Route::get('/requests.csv', 'requestsCsv')->name('requests.csv');
            Route::get('/requests.pdf', 'requestsPdf')->name('requests.pdf');
        });

        Route::get('/facility', [DashboardController::class, 'facilityRedirect'])
            ->name('Facility');

        Volt::route('/amenities', 'amenities.amenities')
            ->name('Amenities');

        Volt::route('/request', 'request.request')
            ->name('Request');

        Volt::route('/schedule', 'schedule.schedule')
            ->name('Schedule');

        Volt::route('/feedback', 'feedback.feedback')
            ->name('Feedback');

        Volt::route('/archived', 'archive.archive-index')
            ->name('Archived');
    });
});
