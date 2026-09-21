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

            if ($lockedRequest->Status !== 'Approved') {
                throw ValidationException::withMessages([
                    'request' => 'This event has already ended or its status has changed.',
                ]);
            }

            $lockedRequest->update(['Status' => 'Ended']);
            $lockedRequest->delete();
        }, 3);
    }
}
