<?php

namespace App\Providers;

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Observers\AdminContentChangeObserver;
use App\Support\Ui;
use App\Support\UiManager;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        config()->set('livewire.inject_assets', false);

        $this->app->singleton(UiManager::class);
        $this->app->alias(UiManager::class, 'ui');

        AliasLoader::getInstance()->alias('Ui', Ui::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/ui'), 'ui');

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

        Amenities::observe(AdminContentChangeObserver::class);
        Events::observe(AdminContentChangeObserver::class);
        Facilities::observe(AdminContentChangeObserver::class);
        Requests::observe(AdminContentChangeObserver::class);
        Schedule::observe(AdminContentChangeObserver::class);
        User::observe(AdminContentChangeObserver::class);
    }
}
