<?php

namespace App\Providers;

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Observers\AdminContentChangeObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Amenities::observe(AdminContentChangeObserver::class);
        Events::observe(AdminContentChangeObserver::class);
        Facilities::observe(AdminContentChangeObserver::class);
        Requests::observe(AdminContentChangeObserver::class);
        Schedule::observe(AdminContentChangeObserver::class);
        User::observe(AdminContentChangeObserver::class);
    }
}
