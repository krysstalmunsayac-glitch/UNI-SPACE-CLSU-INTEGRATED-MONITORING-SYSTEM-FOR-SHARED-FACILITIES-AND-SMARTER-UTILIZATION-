<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\BookingPolicy;
use App\Services\BookingRequestValidator;
use App\Services\FacilityAvailabilityService;
use App\Services\RequestSubmissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class FacilityRequestController extends Controller
{
    public function __construct(
        private readonly BookingRequestValidator $validator,
        private readonly RequestSubmissionService $submissions,
    ) {}

    public function showRequest(Facility $facility, FacilityAvailabilityService $availability)
    {
        return $this->renderRequestForm($facility, $availability, false);
    }

    public function showGuestRequest(Facility $facility, FacilityAvailabilityService $availability)
    {
        $this->authorizeGuestFacility($facility);

        return $this->renderRequestForm($facility, $availability, true);
    }

    private function renderRequestForm(Facility $facility, FacilityAvailabilityService $availability, bool $guestBooking)
    {
        abort_unless($facility->Status === 'Available', 409, 'This facility is not currently available for requests.');

        $facility->load([
            'images',
            'assignedAdmins' => fn ($query) => $query
                ->where('users.user_type', 'admin')
                ->select('users.id', 'users.name', 'users.email'),
        ]);

        $availableAmenities = $facility->amenities()
            ->where('amenities.Status', 'Available')
            ->get()
            ->values();
        $permanentAmenities = $availableAmenities->filter->isPermanent()->values();
        $additionalAmenities = $availableAmenities->reject->isPermanent()->values();

        $events = Event::orderBy('Event_Title')->get();

        $scheduling = [
            'slots' => $availability->slots(),
            'opens_at' => $availability::OPENS_AT,
            'closes_at' => $availability::CLOSES_AT,
            'minimum_minutes' => $availability::MINIMUM_MINUTES,
            'buffer_minutes' => $availability::BUFFER_MINUTES,
            'availability_url' => $guestBooking
                ? route('admin.requests.availability', $facility)
                : route('requests.availability', $facility),
        ];

        return view('requests.create', compact(
            'facility',
            'events',
            'availableAmenities',
            'permanentAmenities',
            'additionalAmenities',
            'scheduling',
            'guestBooking',
        ));
    }

    public function guestAvailability(Request $request, Facility $facility, FacilityAvailabilityService $availability)
    {
        $this->authorizeGuestFacility($facility);

        return $this->availability($request, $facility, $availability);
    }

    public function availability(Request $request, Facility $facility, FacilityAvailabilityService $availability)
    {
        abort_unless($facility->Status === 'Available', 409);
        $validated = $request->validate([
            'from' => ['required', 'date', 'after_or_equal:'.app(BookingPolicy::class)->earliestDate(auth()->user())],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'schedules' => ['sometimes', 'array', 'max:31'],
            'schedules.*.date' => ['required_with:schedules', 'date_format:Y-m-d'],
            'schedules.*.start' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end' => ['required_with:schedules', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d|24:00$/'],
        ]);

        if (Carbon::parse($validated['from'])->diffInDays(Carbon::parse($validated['to'])) >= FacilityAvailabilityService::MAX_DAYS) {
            throw ValidationException::withMessages(['to' => 'Availability may be requested for no more than 31 days.']);
        }

        $response = $availability->availability($facility->FID, $validated['from'], $validated['to']);
        $response['amenities'] = $facility->amenities()
            ->where('amenities.Status', 'Available')
            ->where('amenities.inventory_type', 'countable')
            ->get()
            ->mapWithKeys(fn ($amenity) => [
                (string) $amenity->AID => $amenity->inventory_quantity,
            ]);

        return response()->json($response);
    }

    public function storeRequest(Request $request, Facility $facility, FacilityAvailabilityService $availability)
    {
        return $this->storeFacilityRequest($request, $facility, $availability, false);
    }

    public function storeGuestRequest(Request $request, Facility $facility, FacilityAvailabilityService $availability)
    {
        $this->authorizeGuestFacility($facility);

        return $this->storeFacilityRequest($request, $facility, $availability, true);
    }

    private function storeFacilityRequest(Request $request, Facility $facility, FacilityAvailabilityService $availability, bool $guestBooking)
    {
        abort_unless($facility->Status === 'Available', 409, 'This facility is not currently available for requests.');

        $earliestReservationDate = app(BookingPolicy::class)->earliestDate(auth()->user());

        $validated = $request->validate([
            'Guest_Name' => [$guestBooking ? 'required' : 'nullable', 'string', 'min:2', 'max:150', 'regex:/^(?=.*\pL).+$/u'],
            'Guest_Organization' => ['nullable', 'string', 'max:200'],
            'Guest_Email' => ['nullable', 'email:rfc', 'max:255'],
            'Guest_Contact' => ['nullable', 'string', 'regex:'.User::PH_CONTACT_REGEX],
            'Amenity_ID' => ['array', 'nullable'],
            'Amenity_ID.*' => [
                'integer', 'distinct',
                Rule::exists('facility_amenity', 'Amenity_ID')->where('Facility_ID', $facility->FID),
                Rule::exists('amenities', 'AID')
                    ->where('Status', 'Available')
                    ->where('inventory_type', 'countable'),
            ],
            'Amenity_Quantity' => ['array', 'nullable'],
            'Amenity_Quantity.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'Event_ID' => ['nullable', 'integer', Rule::exists('events', 'EID')],
            'Event_Title' => ['required', 'string', 'min:3', 'max:255', 'regex:/^(?=.*[\pL\pN]).+$/u'],
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
            'Type_Event' => ['required', Rule::in(['Meeting', 'Seminar', 'Workshop', 'Conference', 'Other'])],
            'Event_Scope' => ['required', Rule::in(['Internal', 'External'])],
            'Other_Event_Type' => ['nullable', 'required_if:Type_Event,Other', 'string', 'max:100', 'regex:/^(?=.*\pL)[\pL\s]+$/u'],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Daily_Schedules' => ['required', 'array', 'min:1', 'max:31'],
            'Daily_Schedules.*.date' => ['required', 'date_format:Y-m-d'],
            'Daily_Schedules.*.start' => ['required', 'date_format:H:i'],
            'Daily_Schedules.*.end' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d|24:00$/'],
            'Capacity' => ['required', 'integer', 'min:1', 'max:'.($facility->Capacity ?? 100000)],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => app(BookingPolicy::class)->noticeMessage(auth()->user()),
            'Guest_Name.regex' => 'The guest name must contain at least one letter.',
            'Event_Title.regex' => 'The event name must contain at least one letter or number.',
            'Purpose_Categories.required' => 'Select at least one purpose of request.',
            'Other_Purpose.required' => 'Describe the other purpose.',
            'Request_Details.regex' => 'The event description must contain at least one letter or number.',
            'Other_Event_Type.regex' => 'The event type may contain letters and spaces only; numbers and special characters are not allowed.',
            'Capacity.required' => 'Enter the expected number of attendees.',
        ]);

        $dailySchedules = $availability->validateSchedules(
            $facility->FID,
            $validated['Proposed_Date'],
            $validated['Proposed_End_Date'],
            $validated['Daily_Schedules'],
        );
        $firstSchedule = $dailySchedules[0];
        app(BookingPolicy::class)->validateFutureStart($firstSchedule['date'], $firstSchedule['start'], 'Daily_Schedules.0.start');

        if (($validated['Type_Event'] ?? null) === 'Other') {
            $validated['Type_Event'] = trim($validated['Other_Event_Type']);
        }

        $validated['Purpose'] = collect($validated['Purpose_Categories'])
            ->map(fn (string $category): string => $category === 'Other'
                ? trim($validated['Other_Purpose'])
                : $category)
            ->implode(', ');

        $amenityQuantities = $this->validator->validatedAmenityQuantities(
            $validated['Amenity_ID'] ?? [],
            $validated['Amenity_Quantity'] ?? [],
        );

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('request-attachments', 'local');
        }

        try {
            $requestModel = $this->submissions->submitFacility(
                $facility,
                $request->user(),
                $validated,
                $amenityQuantities,
                $dailySchedules,
                $attachmentPath,
                $availability,
                $guestBooking,
            );
        } catch (ValidationException $exception) {
            if ($attachmentPath) {
                Storage::disk('local')->delete($attachmentPath);
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('local')->delete($attachmentPath);
            }

            Log::error('Facility request submission failed.', [
                'user_id' => auth()->id(),
                'facility_id' => $facility->FID,
                'exception' => $exception,
            ]);

            return back()->withInput()->withErrors([
                'submission' => 'We could not submit your request right now. Nothing was saved. Please try again.',
            ]);
        }

        return redirect()
            ->route($guestBooking ? 'Request' : 'dashboard', $guestBooking ? ['request' => $requestModel->RID] : [])
            ->with('success', $guestBooking
                ? 'The guest facility request has been submitted successfully.'
                : 'Your request has been submitted successfully.')
            ->with('sweet_alert', [
                'title' => 'Request sent',
                'text' => $guestBooking
                    ? 'The guest facility request has been submitted successfully.'
                    : 'Your request has been submitted successfully.',
                'icon' => 'success',
            ]);
    }

    private function authorizeGuestFacility(Facility $facility): void
    {
        $user = auth()->user();
        abort_unless($user?->isSuperAdminOrAdmin(), 403);

        if ($user->isAdmin()) {
            abort_unless($facility->assignedAdmins()->where('users.id', $user->id)->exists(), 403);
        }
    }
}
