<?php

namespace App\Services;

use App\Actions\Schedules\CreateSchedule;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Notifications\RequestAwaitingPayment;
use App\Notifications\RequestNeedsRevision;
use App\Notifications\RequestStatusUpdated;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RequestWorkflowService
{
    public function __construct(
        private readonly FacilityAvailabilityService $availability,
        private readonly CreateSchedule $createSchedule,
    ) {}

    public function approve(FacilityRequest $request): ?array
    {
        $dailySchedules = $request->Daily_Schedules ?? [[
            'date' => $request->Proposed_Date->toDateString(),
            'start' => $request->Proposed_Start_Time->format('H:i'),
            'end' => $request->Proposed_End_Time->format('H:i'),
        ]];

        $result = DB::transaction(function () use ($request, $dailySchedules): ?array {
            if ($request->Facility_ID) {
                Facility::query()->whereKey($request->Facility_ID)->lockForUpdate()->firstOrFail();
            }

            $request = FacilityRequest::query()->whereKey($request->RID)->lockForUpdate()->firstOrFail();

            if (! $request->canTransitionTo('Approved') || ! $request->Proposed_Date->isAfter(today())) {
                return null;
            }

            if ($request->Facility_ID && $this->availability->conflicts(
                $request->Facility_ID, $dailySchedules, $request->RID, true, ['Approved']
            )->isNotEmpty()) {
                return null;
            }

            $rejectedRequests = $request->Facility_ID
                ? $this->availability->conflicts($request->Facility_ID, $dailySchedules, $request->RID, true, ['Pending'])
                    ->pluck('request_id')->unique()->map(fn ($id) => FacilityRequest::query()->find($id))->filter()->values()
                : collect();

            $request->update([
                'Status' => 'Approved',
                'Rejection_Reason' => null,
                'Review_Notes' => null,
                'Review_Requested_At' => null,
            ]);
            $this->syncSchedule($request);

            foreach ($rejectedRequests as $conflictingRequest) {
                $conflictingRequest->schedules()->delete();
                $conflictingRequest->update([
                    'Status' => 'Rejected',
                    'Rejection_Reason' => "Schedule conflict: request #{$request->RID} was approved for the same facility and time.",
                    'Review_Notes' => null,
                    'Review_Requested_At' => null,
                ]);
            }

            return [
                'approved' => $request->load(['facility', 'user']),
                'rejected' => $rejectedRequests->each->load(['facility', 'user']),
            ];
        }, 3);

        if ($result) {
            $this->notifyStatusChange($result['approved'], 'Pending');
            foreach ($result['rejected'] as $rejectedRequest) {
                $this->notifyStatusChange($rejectedRequest, 'Pending');
            }
        }

        return $result;
    }

    public function requestPayment(FacilityRequest $request, array $payment): void
    {
        abort_unless($request->canTransitionTo('Awaiting Payment'), 409);
        $request->update([
            'Status' => 'Awaiting Payment',
            'Payment_Amount' => $payment['paymentAmount'],
            'Payment_Deadline' => $payment['paymentDeadline'],
            'Payment_Proof_Path' => null,
            'Payment_Proof_Uploaded_At' => null,
        ]);

        if ($request->user) {
            Notification::send($request->user, new RequestAwaitingPayment($request));
        } elseif ($request->Is_Guest_Booking && $request->Guest_Email) {
            Notification::route('mail', $request->Guest_Email)->notify(new RequestAwaitingPayment($request));
        }
    }

    public function reject(FacilityRequest $request, string $reasons): void
    {
        abort_unless($request->canTransitionTo('Rejected'), 409);
        $previousStatus = $request->Status;
        $request->schedules()->delete();
        $request->update([
            'Status' => 'Rejected',
            'Rejection_Reason' => $reasons,
            'Review_Notes' => null,
            'Review_Requested_At' => null,
        ]);
        $this->notifyStatusChange($request, $previousStatus);
    }

    public function cancel(FacilityRequest $request, string $reason, bool $notify): bool
    {
        if ($request->Status !== 'Approved') {
            return false;
        }

        $previousStatus = $request->Status;
        $request->schedules()->delete();
        $request->update(['Status' => 'Cancelled', 'Cancellation_Reason' => trim($reason)]);

        if ($notify) {
            $this->notifyStatusChange($request, $previousStatus, true);
        }

        return true;
    }

    public function requestRevision(FacilityRequest $request, string $notes): void
    {
        abort_unless($request->canBeReviewed(), 409);
        abort_if($request->Is_Guest_Booking, 409, 'Guest requests cannot be returned to an End User for revision.');
        $request->schedules()->delete();
        $request->update([
            'Status' => 'Pending',
            'Review_Notes' => trim($notes),
            'Review_Requested_At' => now(),
            'Rejection_Reason' => null,
        ]);

        if ($request->user) {
            Notification::send($request->user, new RequestNeedsRevision($request));
        }
    }

    public function update(FacilityRequest $request, array $data): bool
    {
        $previousStatus = $request->Status;
        $wasApproved = $previousStatus === 'Approved';
        $facilityId = $request->facility?->FID;
        abort_if($data['Status'] !== $previousStatus && ! $request->canTransitionTo($data['Status']), 409, 'This request status transition is not allowed.');

        $forcedRejection = $data['Status'] === 'Approved' && $facilityId
            && FacilityRequest::activeFacilityConflicts(
                $facilityId, $data['Proposed_Date'], $data['Proposed_End_Date'],
                $data['Proposed_Start_Time'], $data['Proposed_End_Time'], $request->RID
            )->where('RID', '<', $request->RID)->exists();

        if ($forcedRejection) {
            $data['Status'] = 'Rejected';
        }

        $data['Daily_Schedules'] = collect(CarbonPeriod::create($data['Proposed_Date'], $data['Proposed_End_Date']))
            ->map(fn ($date) => [
                'date' => $date->toDateString(),
                'start' => $data['Proposed_Start_Time'],
                'end' => $data['Proposed_End_Time'],
            ])->values()->all();
        $request->update($data);

        if ($data['Status'] !== $previousStatus) {
            $this->notifyStatusChange($request, $previousStatus);
        }
        if ($data['Status'] === 'Cancelled' && $previousStatus !== 'Cancelled') {
            $request->schedules()->delete();
        }
        if ($data['Status'] === 'Approved' && ! $wasApproved) {
            $this->syncSchedule($request);
        }

        return $forcedRejection;
    }

    private function syncSchedule(FacilityRequest $request): void
    {
        $facilityId = $request->facility?->FID;
        $request->schedules()->delete();
        $dailySchedules = $request->Daily_Schedules ?: collect(CarbonPeriod::create($request->Proposed_Date, $request->Proposed_End_Date ?? $request->Proposed_Date))
            ->map(fn ($date) => ['date' => $date->toDateString(), 'start' => $request->Proposed_Start_Time->format('H:i'), 'end' => $request->Proposed_End_Time->format('H:i')])->all();

        foreach ($dailySchedules as $dailySchedule) {
            $overlaps = $facilityId && Schedule::whereDate('Date', $dailySchedule['date'])
                ->whereHas('request.facility', fn ($query) => $query->where('FID', $facilityId))
                ->where(fn ($query) => $query->where('Start_Time', '<', $dailySchedule['end'])->where('End_Time', '>', $dailySchedule['start']))
                ->exists();
            if (! $overlaps) {
                $this->createSchedule->handle([
                    'Request_ID' => $request->RID,
                    'Date' => $dailySchedule['date'],
                    'Start_Time' => $dailySchedule['start'],
                    'End_Time' => $dailySchedule['end'],
                    'Status' => 'Booked',
                ]);
            }
        }
    }

    private function notifyStatusChange(FacilityRequest $request, string $previousStatus, bool $includeGuest = false): void
    {
        if ($request->user) {
            Notification::send($request->user, new RequestStatusUpdated($request, $previousStatus));
        } elseif ($includeGuest && $request->Is_Guest_Booking && $request->Guest_Email) {
            Notification::route('mail', $request->Guest_Email)->notify(new RequestStatusUpdated($request, $previousStatus));
        }
    }
}
