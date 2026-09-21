<?php

use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\FacilityRequestController;
use App\Http\Controllers\FeedbackController;
use App\Http\Middleware\PreventBackHistory;
use App\Livewire\Amenities\AmenityManagement;
use App\Livewire\Archives\ArchiveManagement;
use App\Livewire\Facilities\OfficeAdminFacility;
use App\Livewire\Facilities\SuperAdminFacility;
use App\Livewire\Feedback\FeedbackManagement;
use App\Livewire\Reports\ReportManagement;
use App\Livewire\Requests\RequestManagement;
use App\Livewire\Schedules\ScheduleManagement;
use App\Livewire\Users\UserManagement;
use Illuminate\Support\Facades\Route;

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
            ->name('dashboard.super-admin');

        Route::get('/dashboard/office-admin', 'officeAdmin')
            ->middleware('role:office_admin')
            ->name('dashboard.office-admin');
    });

    Route::middleware('user.pages')->group(function () {
        Route::get('/dashboard/requests/{facilityRequest}/feedback', [FeedbackController::class, 'create'])
            ->name('requests.feedback.create');
        Route::post('/dashboard/requests/{facilityRequest}/feedback', [FeedbackController::class, 'store'])
            ->name('requests.feedback.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/dashboard/analytics.pdf', [DashboardController::class, 'analyticsPdf'])
            ->name('dashboard.analytics.pdf');

        Route::get('/users', UserManagement::class)
            ->name('users.index');

        Route::get('/facilities/super-admin', SuperAdminFacility::class)
            ->name('facilities.super-admin.index');

        Route::get('/reports', ReportManagement::class)
            ->name('reports.index');

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
            Route::get('/audits.csv', 'auditsCsv')->name('audits.csv');
            Route::get('/audits.xlsx', 'auditsXlsx')->name('audits.xlsx');
            Route::get('/audits.pdf', 'auditsPdf')->name('audits.pdf');
        });

    });

    /*
    |--------------------------------------------------------------------------
    | Office Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:office_admin')->group(function () {
        Route::get('/facilities/office-admin', OfficeAdminFacility::class)
            ->name('facilities.office-admin.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Shared Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super_admin,office_admin')->group(function () {
        Route::prefix('facilities/{facility}/guest-request')
            ->name('admin.requests.')
            ->controller(FacilityRequestController::class)
            ->group(function () {
                Route::get('/', 'showGuestRequest')->name('create');
                Route::get('/availability', 'guestAvailability')->middleware('throttle:60,1')->name('availability');
                Route::post('/', 'storeGuestRequest')->name('store');
            });

        Route::get('/facilities', [DashboardController::class, 'facilityRedirect'])
            ->name('facilities.index');

        Route::get('/amenities', AmenityManagement::class)
            ->name('amenities.index');

        Route::get('/requests', RequestManagement::class)
            ->name('requests.index');

        Route::get('/schedules', ScheduleManagement::class)
            ->name('schedules.index');

        Route::get('/feedback', FeedbackManagement::class)
            ->name('feedback.index');

        Route::get('/archives', ArchiveManagement::class)
            ->name('archives.index');
    });
});
