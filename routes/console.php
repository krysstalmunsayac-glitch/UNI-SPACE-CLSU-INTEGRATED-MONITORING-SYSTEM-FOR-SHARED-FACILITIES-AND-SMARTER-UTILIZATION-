<?php

use App\Models\Requests;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('requests:mark-ended', function () {
    $endedCount = Requests::markPastRequestsAsEnded();

    $this->info("Marked and archived {$endedCount} ended request(s).");
})->purpose('Mark requests as ended and archive them after their proposed end time');

Artisan::command('requests:archive-cancelled', function () {
    $archivedCount = Requests::archiveExpiredCancelledRequests();

    $this->info("Archived {$archivedCount} cancelled request(s).");
})->purpose('Archive requests 10 days after cancellation');

Artisan::command('users:purge-deleted', function () {
    $purgedCount = User::onlyTrashed()
        ->where('deleted_at', '<=', now()->subDays(90))
        ->get()
        ->each
        ->forceDelete()
        ->count();

    $this->info("Permanently deleted {$purgedCount} account(s) after the 90-day retention period.");
})->purpose('Permanently delete accounts after the 90-day soft-delete retention period');

Artisan::command('requests:migrate-attachments-private', function () {
    $public = Storage::disk('public');
    $private = Storage::disk('local');
    $migrated = 0;

    foreach ($public->files('request-attachments') as $path) {
        $stream = $public->readStream($path);

        if ($stream === false) {
            $this->error("Could not read {$path}; migration stopped without deleting it.");

            return 1;
        }

        try {
            if (! $private->writeStream($path, $stream)) {
                $this->error("Could not write {$path} to private storage; migration stopped.");

                return 1;
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $private->exists($path) || ! $public->delete($path)) {
            $this->error("Could not verify and remove the public copy of {$path}; migration stopped.");

            return 1;
        }

        $migrated++;
    }

    $this->info("Migrated {$migrated} request attachment(s) to private storage.");

    return 0;
})->purpose('Move legacy request attachments from public to private storage');

Schedule::command('requests:mark-ended')->everyMinute();
Schedule::command('requests:archive-cancelled')->hourly();
Schedule::command('users:purge-deleted')->daily();
