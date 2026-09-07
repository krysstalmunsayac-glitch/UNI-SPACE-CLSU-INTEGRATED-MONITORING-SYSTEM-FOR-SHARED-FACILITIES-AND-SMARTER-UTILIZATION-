<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingPolicy
{
    public function noticeDays(): int
    {
        return (int) (DB::table('booking_settings')->where('id', 1)->value('notice_days') ?? 3);
    }

    public function saveNoticeDays(User $user, mixed $days): void
    {
        abort_unless($user->isSuperAdmin(), 403);
        $validated = Validator::make(['noticeDays' => $days], [
            'noticeDays' => ['required', 'integer', 'between:0,365'],
        ])->validate();

        DB::table('booking_settings')->updateOrInsert(['id' => 1], ['notice_days' => $validated['noticeDays']]);
    }

    public function earliestDate(?User $user = null): string
    {
        return today()->addDays($user?->isSuperAdmin() ? 0 : $this->noticeDays())->toDateString();
    }

    public function noticeMessage(?User $user = null): string
    {
        if ($user?->isSuperAdmin()) {
            return 'Super admins may schedule inside the notice period, including today. The start time must be in the future.';
        }

        $days = $this->noticeDays();

        return $days === 0
            ? 'Same-day bookings are allowed. The start time must be in the future.'
            : 'Bookings require at least '.$days.' '.($days === 1 ? 'day' : 'days').' of advance notice.';
    }

    public function validateFutureStart(string $date, string $time, string $field): void
    {
        if (Carbon::parse($date.' '.$time)->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([$field => 'The booking start time must be in the future.']);
        }
    }
}
