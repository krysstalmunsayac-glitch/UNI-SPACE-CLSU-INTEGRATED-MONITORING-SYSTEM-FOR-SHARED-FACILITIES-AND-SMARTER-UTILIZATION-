<?php

namespace App\Actions\Requests;

use App\Models\FacilityRequest;
use App\Services\BookingRequestValidator;
use App\Services\FacilityAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RescheduleWaitingRequest
{
    public function __construct(private readonly BookingRequestValidator $validator) {}

    /**
     * @param  array<int, array{date:string,start:string,end:string}>  $dailySchedules
     * @param  array<int, int>  $amenityQuantities
     */
    public function handle(
        FacilityRequest $request,
        int $actorId,
        array $validated,
        array $dailySchedules,
        array $amenityQuantities,
        ?string $newAttachmentPath,
        FacilityAvailabilityService $availability,
    ): void {
        $oldAttachmentPath = $request->attachment_path;
        $attachmentPath = $newAttachmentPath ?? $oldAttachmentPath;

        try {
            DB::transaction(function () use ($request, $actorId, $validated, $dailySchedules, $amenityQuantities, $attachmentPath, $availability): void {
                $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($request->RID);

                $this->validator->validateDailyRequestLimit(
                    $actorId,
                    $validated['Proposed_Date'],
                    $validated['Proposed_End_Date'],
                    $lockedRequest->RID,
                    true,
                );

                if ($lockedRequest->Facility_ID) {
                    $availability->validateSchedules(
                        $lockedRequest->Facility_ID,
                        $validated['Proposed_Date'],
                        $validated['Proposed_End_Date'],
                        $dailySchedules,
                        $lockedRequest->RID,
                        true,
                    );

                    foreach ($dailySchedules as $schedule) {
                        $this->validator->validateAmenityAvailability(
                            $amenityQuantities,
                            $schedule['date'],
                            $schedule['date'],
                            $schedule['start'],
                            $schedule['end'],
                            $lockedRequest->RID,
                            true,
                        );
                    }
                }

                $lockedRequest->event?->update([
                    'Event_Title' => $validated['Event_Title'] ?? $lockedRequest->event->Event_Title,
                    'Description' => $validated['Request_Details'],
                    'Type_Event' => $validated['Type_Event'] ?? $lockedRequest->event->Type_Event,
                    'Event_Scope' => $validated['Event_Scope'] ?? $lockedRequest->event->Event_Scope,
                ]);

                $lockedRequest->update([
                    'Proposed_Date' => $validated['Proposed_Date'],
                    'Proposed_End_Date' => $validated['Proposed_End_Date'],
                    'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
                    'Proposed_End_Time' => $validated['Proposed_End_Time'],
                    'Daily_Schedules' => $dailySchedules,
                    'Purpose' => $validated['Purpose'],
                    'Request_Details' => $validated['Request_Details'],
                    'Purpose_Categories' => $validated['Purpose_Categories'],
                    'Other_Purpose' => $validated['Other_Purpose'] ?? null,
                    'Capacity' => $validated['Capacity'],
                    'attachment_path' => $attachmentPath,
                    'Status' => $lockedRequest->Status,
                    'Cancellation_Reason' => $lockedRequest->Cancellation_Reason,
                    'Review_Notes' => null,
                    'Review_Requested_At' => null,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            if ($newAttachmentPath) {
                Storage::disk('local')->delete($newAttachmentPath);
            }

            throw $exception;
        }

        if ($newAttachmentPath && $oldAttachmentPath) {
            Storage::disk('local')->delete($oldAttachmentPath);
            Storage::disk('public')->delete($oldAttachmentPath);
        }
    }
}
