<?php

use App\Models\Requests;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('requests:archive-approved', function () {
    $archivedCount = Requests::query()
        ->where('Status', 'Approved')
        ->where('Created_at', '<=', now()->subDays(10))
        ->delete();

    $this->info("Archived {$archivedCount} approved request(s).");
})->purpose('Archive approved requests after 10 days');

Artisan::command('requests:mark-ended', function () {
    $endedCount = Requests::markPastRequestsAsEnded();

    $this->info("Marked {$endedCount} request(s) as ended.");
})->purpose('Mark requests as ended after their proposed end time');

Schedule::command('requests:mark-ended')->everyMinute();
Schedule::command('requests:archive-approved')->dailyAt('00:00');
