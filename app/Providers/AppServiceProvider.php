<?php

namespace App\Providers;

use App\Livewire\Amenities\AmenityManagement;
use App\Livewire\Archives\ArchiveManagement;
use App\Livewire\Facilities\OfficeAdminFacility;
use App\Livewire\Facilities\SuperAdminFacility;
use App\Livewire\Feedback\FeedbackManagement;
use App\Livewire\Reports\ReportManagement;
use App\Livewire\Schedules\ScheduleManagement;
use App\Livewire\Users\UserManagement;
use App\Models\Amenity;
use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Observers\AdminContentChangeObserver;
use App\Support\Ui;
use App\Support\UiManager;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UiManager::class);
        $this->app->alias(UiManager::class, 'ui');

        AliasLoader::getInstance()->alias('Ui', Ui::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());

        Blade::anonymousComponentPath(resource_path('views/ui'), 'ui');
        Livewire::component('user.user-management', UserManagement::class);
        Livewire::component('schedule.schedule', ScheduleManagement::class);
        Livewire::component('facility.super-admin-facility', SuperAdminFacility::class);
        Livewire::component('facility.office-admin-facility', OfficeAdminFacility::class);
        Livewire::component('amenities.amenities', AmenityManagement::class);
        Livewire::component('feedback.feedback', FeedbackManagement::class);
        Livewire::component('report.report-management', ReportManagement::class);
        Livewire::component('archive.archive-index', ArchiveManagement::class);

        // The stylesheet link is rendered immediately after Laravel's preload
        // tag. Livewire navigation can leave the duplicate preload unused and
        // trigger repeated browser warnings, so preload scripts only.
        Vite::usePreloadTagAttributes(
            fn (string $src, string $url): array|false => str_ends_with(parse_url($url, PHP_URL_PATH) ?: $url, '.css')
                ? false
                : []
        );

        Component::macro('modal', function (string $name) {
            return new class($name)
            {
                public function __construct(private string $name) {}

                public function show(): void
                {
                    app('livewire')->current()?->dispatch('ui-modal-show', name: $this->name);
                }

                public function close(): void
                {
                    app('livewire')->current()?->dispatch('ui-modal-close', name: $this->name);
                }
            };
        });

        Amenity::observe(AdminContentChangeObserver::class);
        Event::observe(AdminContentChangeObserver::class);
        Facility::observe(AdminContentChangeObserver::class);
        FacilityRequest::observe(AdminContentChangeObserver::class);
        Schedule::observe(AdminContentChangeObserver::class);
        User::observe(AdminContentChangeObserver::class);
    }
}
