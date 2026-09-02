<?php

use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Dashboard\DashboardController;
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

    Route::middleware('user.pages')->group(function () {
        Route::get('/dashboard/requests/{facilityRequest}/feedback', [FeedbacksController::class, 'create'])
            ->name('facility-feedback.create');
        Route::post('/dashboard/requests/{facilityRequest}/feedback', [FeedbacksController::class, 'store'])
            ->name('facility-feedback.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/dashboard/analytics.pdf', [DashboardController::class, 'analyticsPdf'])
            ->name('dashboard.analytics.pdf');

        Volt::route('/user-management', 'user.user-management')
            ->name('UserManagement');

        Volt::route('/facility/super-admin', 'facility.super-admin-facility')
            ->name('Facility.SuperAdmin');

        Volt::route('/report-management', 'report.report-management')
            ->name('ReportManagement');

        Route::prefix('exports')->name('exports.')->controller(ReportExportController::class)->group(function () {
            Route::get('/facilities.csv', 'facilitiesCsv')->name('facilities.csv');
            Route::get('/facilities.xlsx', 'facilitiesXlsx')->name('facilities.xlsx');
            Route::get('/facilities.pdf', 'facilitiesPdf')->name('facilities.pdf');
            Route::get('/requests.csv', 'requestsCsv')->name('requests.csv');
            Route::get('/requests.xlsx', 'requestsXlsx')->name('requests.xlsx');
            Route::get('/requests.pdf', 'requestsPdf')->name('requests.pdf');
            Route::get('/users.csv', 'usersCsv')->name('users.csv');
            Route::get('/users.xlsx', 'usersXlsx')->name('users.xlsx');
            Route::get('/users.pdf', 'usersPdf')->name('users.pdf');
            Route::get('/amenities.csv', 'amenitiesCsv')->name('amenities.csv');
            Route::get('/amenities.xlsx', 'amenitiesXlsx')->name('amenities.xlsx');
            Route::get('/amenities.pdf', 'amenitiesPdf')->name('amenities.pdf');
        });

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
