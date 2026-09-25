<?php

namespace App\Actions\Requests;

use App\Models\FacilityRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndWaitingRequest
{
    public function handle(FacilityRequest $request): void
    {
        DB::transaction(function () use ($request): void {
            $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($request->RID);

            if ($lockedRequest->Status !== 'Approved' || ! $lockedRequest->scheduledEndAt()?->lte(now())) {
                throw ValidationException::withMessages([
                    'request' => $lockedRequest->Status === 'Approved'
                        ? 'This event can only be completed after its scheduled end time.'
                        : 'This event is already completed or its status has changed.',
                ]);
            }

            $lockedRequest->update(['Status' => 'Ended']);
            $lockedRequest->delete();
        }, 3);
    }
}
