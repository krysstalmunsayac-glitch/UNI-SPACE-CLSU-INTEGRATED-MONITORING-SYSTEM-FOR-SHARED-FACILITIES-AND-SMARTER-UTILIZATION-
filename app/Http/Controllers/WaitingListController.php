<?php

namespace App\Http\Controllers;

use App\Actions\Requests\CancelWaitingRequest;
use App\Actions\Requests\EndWaitingRequest;
use App\Actions\Requests\RescheduleWaitingRequest;
use App\Models\Amenity;
use App\Models\FacilityRequest;
use App\Services\BookingPolicy;
use App\Services\BookingRequestValidator;
use App\Services\FacilityAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaitingListController extends Controller
{
    public function __construct(private readonly BookingRequestValidator $validator) {}

    public function waitingList()
    {
        return redirect(route('dashboard').'#requests');
    }

    public function updateWaitingList(
        Request $request,
        FacilityRequest $requestModel,
        FacilityAvailabilityService $availability,
        RescheduleWaitingRequest $action,
    ) {
        if ($requestModel->User_ID !== auth()->id()) {
            abort(403);
        }

        if (in_array($requestModel->Status, ['Approved', 'Rejected', 'Cancelled', 'Ended'], true)) {
            $status = strtolower($requestModel->Status);

            return redirect()
                ->route('dashboard', ['request' => $requestModel->RID])
                ->with('warning', "This request is {$status}. Its submitted information is read-only.");
        }

        $earliestReservationDate = app(BookingPolicy::class)->earliestDate(auth()->user());

        $validated = $request->validate([
            'Event_Title' => ['nullable', 'string', 'min:3', 'max:255', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Request_Details' => ['required', 'string', 'min:5', 'max:2000', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
            'Event_Scope' => ['nullable', Rule::in(['Internal', 'External'])],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Proposed_Start_Time' => ['required', 'regex:/^(?:0[5-9]|1\d|2[0-3]):(?:00|30)$/'],
            'Proposed_End_Time' => ['required', 'regex:/^(?:(?:0[6-9]|1\d|2[0-3]):(?:00|30)|24:00)$/', 'after:Proposed_Start_Time'],
            'Purpose_Categories' => ['required', 'array', 'min:1'],
            'Purpose_Categories.*' => ['string', 'distinct', Rule::in(FacilityRequest::PURPOSE_OPTIONS)],
            'Other_Purpose' => [
                'nullable',
                Rule::requiredIf(fn () => in_array('Other', $request->input('Purpose_Categories', []), true)),
                'string',
                'min:3',
                'max:150',
            ],
            'Capacity' => ['required', 'integer', 'min:1', 'max:'.($requestModel->facility?->Capacity ?? 100000)],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => app(BookingPolicy::class)->noticeMessage(auth()->user()),
            'Event_Title.regex' => 'The event name must contain at least one letter or number.',
            'Request_Details.regex' => 'The event description must contain at least one letter or number.',
            'Capacity.required' => 'Enter the expected number of attendees.',
            'Proposed_Start_Time.regex' => 'Choose a start time between 5:00 AM and 11:30 PM in 30-minute intervals.',
            'Proposed_End_Time.regex' => 'Choose an end time between 6:00 AM and 12:00 AM in 30-minute intervals.',
            'Purpose_Categories.required' => 'Select at least one purpose of request.',
            'Other_Purpose.required' => 'Describe the other purpose.',
        ]);

        $validated['Purpose'] = collect($validated['Purpose_Categories'])
            ->map(fn (string $category): string => $category === 'Other'
                ? trim($validated['Other_Purpose'])
                : $category)
            ->implode(', ');

        app(BookingPolicy::class)->validateFutureStart($validated['Proposed_Date'], $validated['Proposed_Start_Time'], 'Proposed_Start_Time');

        $this->validator->validateBookingDuration(
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
        );

        $this->validator->validateRequestDateRange($validated['Proposed_Date'], $validated['Proposed_End_Date']);

        $dailySchedules = collect(CarbonPeriod::create($validated['Proposed_Date'], $validated['Proposed_End_Date']))
            ->map(fn ($date) => ['date' => $date->format('Y-m-d'), 'start' => $validated['Proposed_Start_Time'], 'end' => $validated['Proposed_End_Time']])
            ->all();

        if ($requestModel->Facility_ID) {
            $availability->validateSchedules(
                $requestModel->Facility_ID,
                $validated['Proposed_Date'],
                $validated['Proposed_End_Date'],
                $dailySchedules,
                $requestModel->RID,
            );
        }

        $this->validator->validateDailyRequestLimit((int) auth()->id(), $validated['Proposed_Date'], $validated['Proposed_End_Date'], $requestModel->RID);

        if ($requestModel->Facility_ID) {
            $this->validator->validateFacilityAvailability(
                $requestModel->Facility_ID,
                $validated['Proposed_Date'],
                $validated['Proposed_End_Date'],
                $validated['Proposed_Start_Time'],
                $validated['Proposed_End_Time'],
                $requestModel->RID,
            );
        }

        $amenityQuantities = $requestModel->amenities->mapWithKeys(
            fn (Amenity $amenity) => [(int) $amenity->AID => (int) $amenity->pivot->quantity]
        )->all();

        $this->validator->validateAmenityAvailability(
            $amenityQuantities,
            $validated['Proposed_Date'],
            $validated['Proposed_End_Date'],
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
            $requestModel->RID,
        );

        $newAttachmentPath = null;

        if ($request->hasFile('attachment')) {
            $newAttachmentPath = $request->file('attachment')->store('request-attachments', 'local');
        }

        $action->handle(
            $requestModel,
            (int) auth()->id(),
            $validated,
            $dailySchedules,
            $amenityQuantities,
            $newAttachmentPath,
            $availability,
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request details were updated and resubmitted successfully.')
            ->with('sweet_alert', [
                'title' => 'Request updated',
                'text' => 'Your request details were updated and resubmitted successfully.',
                'icon' => 'success',
            ]);
    }

    public function cancelWaitingList(Request $request, FacilityRequest $requestModel, CancelWaitingRequest $action)
    {
        if ($requestModel->User_ID !== auth()->id()) {
            abort(403);
        }

        if (! in_array($requestModel->Status, ['Pending', 'Approved'], true)) {
            return redirect()
                ->route('dashboard')
                ->with('warning', 'This request can no longer be cancelled.');
        }

        $reasons = [
            'Change of plans',
            'Schedule conflict',
            'Event postponed',
            'Event cancelled',
            'Facility no longer needed',
            'Other',
        ];

        $validated = $request->validate([
            'Cancellation_Reason' => ['required', Rule::in($reasons)],
            'Other_Cancellation_Reason' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('Cancellation_Reason') === 'Other'),
                'string',
                'min:5',
                'max:1000',
            ],
        ], [
            'Cancellation_Reason.required' => 'Select a reason for cancelling this request.',
            'Cancellation_Reason.in' => 'Select a valid cancellation reason.',
            'Other_Cancellation_Reason.required' => 'Enter a specific cancellation reason.',
        ]);

        $cancellationReason = $validated['Cancellation_Reason'] === 'Other'
            ? trim($validated['Other_Cancellation_Reason'])
            : $validated['Cancellation_Reason'];

        $action->handle($requestModel, $cancellationReason);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request has been cancelled. It will be archived automatically after 10 days.')
            ->with('sweet_alert', [
                'title' => 'Request cancelled',
                'text' => 'Your request has been cancelled successfully. It will be archived automatically after 10 days.',
                'icon' => 'success',
            ]);
    }

    public function endWaitingList(Request $request, FacilityRequest $requestModel, EndWaitingRequest $action)
    {
        if ($requestModel->User_ID !== auth()->id()) {
            abort(403);
        }

        $eventStart = Carbon::parse(
            $requestModel->Proposed_Date->toDateString().' '.$requestModel->Proposed_Start_Time->format('H:i:s')
        );

        if ($requestModel->Status !== 'Approved' || $eventStart->isFuture()) {
            return redirect()
                ->route('dashboard', ['request' => $requestModel->RID])
                ->with('warning', $eventStart->isFuture()
                    ? 'You can end this event after its scheduled start time. Cancel the booking instead if it will not proceed.'
                    : 'This event can no longer be ended.');
        }

        $action->handle($requestModel);

        return redirect()
            ->route('dashboard', ['request' => $requestModel->RID])
            ->with('success', 'Your event has ended. The booking is now read-only and you may leave optional feedback.')
            ->with('sweet_alert', [
                'title' => 'Event ended',
                'text' => 'The booking was marked as ended. Thank you for using the facility.',
                'icon' => 'success',
            ]);
    }
}
