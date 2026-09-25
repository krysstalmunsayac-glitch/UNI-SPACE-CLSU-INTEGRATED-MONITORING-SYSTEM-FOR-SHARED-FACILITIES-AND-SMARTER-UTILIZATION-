<?php

namespace App\Actions\Requests;

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Notifications\RequestCancelledByUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class CancelWaitingRequest
{
    public function handle(FacilityRequest $request, string $reason): FacilityRequest
    {
        $request = DB::transaction(function () use ($request, $reason): FacilityRequest {
            $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($request->RID);

            if (! in_array($lockedRequest->Status, ['Pending', 'Awaiting Payment', 'Approved'], true)) {
                throw ValidationException::withMessages([
                    'Cancellation_Reason' => 'This request has already been cancelled or can no longer be cancelled.',
                ]);
            }

            $lockedRequest->schedules()->delete();
            $lockedRequest->update([
                'Status' => 'Cancelled',
                'Cancellation_Reason' => $reason,
            ]);

            return $lockedRequest;
        }, 3);

        $request->refresh()->load(['facility', 'user']);

        try {
            Notification::send($this->recipients($request->facility), new RequestCancelledByUser($request));
        } catch (Throwable $exception) {
            Log::warning('Request was cancelled by its user, but administrators could not be notified.', [
                'request_id' => $request->RID,
                'exception' => $exception,
            ]);
        }

        return $request;
    }

    private function recipients(?Facility $facility)
    {
        $superAdmins = User::query()->where('user_type', 'super_admin')->get();

        return $facility
            ? $superAdmins->merge($facility->assignedAdmins()->where('users.user_type', 'admin')->get())->unique('id')->values()
            : $superAdmins;
    }
}
