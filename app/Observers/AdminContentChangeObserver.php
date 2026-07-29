<?php

namespace App\Observers;

use App\Support\SiteVersion;
use Illuminate\Database\Eloquent\Model;

class AdminContentChangeObserver
{
    public function saved(Model $model): void
    {
        $this->notifyBrowsers();
    }

    public function deleted(Model $model): void
    {
        $this->notifyBrowsers();
    }

    public function restored(Model $model): void
    {
        $this->notifyBrowsers();
    }

    private function notifyBrowsers(): void
    {
        if (auth()->user()?->isSuperAdminOrAdmin()) {
            SiteVersion::bump();
        }
    }
}
