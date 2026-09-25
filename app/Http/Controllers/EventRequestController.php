<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Event;
use App\Models\FacilityRequest;
use App\Services\BookingPolicy;
use App\Services\BookingRequestValidator;
use App\Services\RequestSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EventRequestController extends Controller
{
    public function __construct(
        private readonly BookingRequestValidator $validator,
        private readonly RequestSubmissionService $submissions,
    ) {}

    public function create(Event $event)
    {
        $amenities = Amenity::where('Status', 'Available')->orderBy('name')->orderBy('AID')->get();

        return view('requests.event-request', compact('event', 'amenities'));
    }

    public function store(Request $request, Event $event)
    {
        $bookingPolicy = app(BookingPolicy::class);
        $earliestReservationDate = $bookingPolicy->earliestDate(auth()->user());

        $validated = $request->validate([
            'Amenity_ID' => ['nullable', 'array'],
            'Amenity_ID.*' => ['integer', 'distinct', Rule::exists('amenities', 'AID')->where('Status', 'Available')],
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
            'Request_Details' => ['required', 'string', 'min:5', 'max:2000', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Capacity' => ['required', 'integer', 'min:1', 'max:100000'],
        ], [
            'Proposed_Date.after_or_equal' => $bookingPolicy->noticeMessage(auth()->user()),
            'Proposed_Start_Time.regex' => 'Choose a start time between 5:00 AM and 11:30 PM in 30-minute intervals.',
            'Proposed_End_Time.regex' => 'Choose an end time between 6:00 AM and 12:00 AM in 30-minute intervals.',
            'Purpose_Categories.required' => 'Select at least one purpose of request.',
            'Other_Purpose.required' => 'Describe the other purpose.',
            'Request_Details.regex' => 'The event description must contain at least one letter or number.',
            'Capacity.required' => 'Enter the expected number of attendees.',
        ]);

        $bookingPolicy->validateFutureStart($validated['Proposed_Date'], $validated['Proposed_Start_Time'], 'Proposed_Start_Time');
        $this->validator->validateBookingDuration($validated['Proposed_Start_Time'], $validated['Proposed_End_Time']);
        $this->validator->validateRequestDateRange($validated['Proposed_Date'], $validated['Proposed_End_Date']);

        $validated['Purpose'] = collect($validated['Purpose_Categories'])
            ->map(fn (string $category): string => $category === 'Other'
                ? trim($validated['Other_Purpose'])
                : $category)
            ->implode(', ');

        try {
            $requestModel = $this->submissions->submitEvent($event, $request->user(), $validated);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Event request submission failed.', [
                'user_id' => auth()->id(),
                'event_id' => $event->EID,
                'exception' => $exception,
            ]);

            return back()->withInput()->withErrors([
                'submission' => 'We could not submit your request right now. Nothing was saved. Please try again.',
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your event booking request has been submitted successfully.');
    }
}
