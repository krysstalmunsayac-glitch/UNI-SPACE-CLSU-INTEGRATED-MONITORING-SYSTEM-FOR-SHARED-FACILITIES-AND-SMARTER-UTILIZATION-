<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Notifications\NewRequestSubmitted;
use App\Notifications\RequestCancelledByUser;
use App\Services\BookingPolicy;
use App\Services\FacilityAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FacilityRequestController extends Controller
{
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

        $facility->load('images');

        $availableAmenities = $facility->amenities()
            ->where('amenities.Status', 'Available')
            ->get()
            ->values();

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

        return view('requests.create', compact('facility', 'events', 'availableAmenities', 'scheduling', 'guestBooking'));
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
        ]);

        if (Carbon::parse($validated['from'])->diffInDays(Carbon::parse($validated['to'])) >= FacilityAvailabilityService::MAX_DAYS) {
            throw ValidationException::withMessages(['to' => 'Availability may be requested for no more than 31 days.']);
        }

        return response()->json($availability->availability($facility->FID, $validated['from'], $validated['to']));
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
                Rule::exists('amenities', 'AID')->where('Status', 'Available'),
            ],
            'Amenity_Quantity' => ['array', 'nullable'],
            'Amenity_Quantity.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'Event_ID' => ['nullable', 'integer', Rule::exists('events', 'EID')],
            'Event_Title' => ['required', 'string', 'min:3', 'max:255', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Description' => ['required', 'string', 'min:5', 'max:2000', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Type_Event' => ['required', Rule::in(['Meeting', 'Seminar', 'Workshop', 'Conference', 'Other'])],
            'Event_Scope' => ['required', Rule::in(['Internal', 'External'])],
            'Other_Event_Type' => ['nullable', 'required_if:Type_Event,Other', 'string', 'max:100', 'regex:/^(?=.*\pL)[\pL\s]+$/u'],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Daily_Schedules' => ['required', 'array', 'min:1', 'max:31'],
            'Daily_Schedules.*.date' => ['required', 'date_format:Y-m-d'],
            'Daily_Schedules.*.start' => ['required', 'date_format:H:i'],
            'Daily_Schedules.*.end' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d|24:00$/'],
            'Purpose_Categories' => ['required', 'array', 'min:1'],
            'Purpose_Categories.*' => ['string', Rule::in([
                'Meeting or Conference', 'Seminar or Workshop', 'Training Session',
                'Class or Educational Activity', 'Student Organization Event', 'Club Meeting',
                'Sports or Recreational Activity', 'Cultural or Arts Program', 'Religious Activity',
                'Community Outreach Program', 'Birthday Celebration', 'Wedding Reception or Ceremony',
                'Family Gathering or Reunion', 'Corporate Event', 'Product Launch or Promotion',
                'Exhibition or Fair', 'Concert or Performance', 'Graduation or Recognition Ceremony',
                'Health or Medical Mission', 'Government or Public Service Activity',
                'Photo or Video Shoot', 'Other',
            ])],
            'Other_Purpose' => [
                'nullable',
                Rule::requiredIf(fn () => in_array('Other', $request->input('Purpose_Categories', []), true)),
                'string',
                'max:150',
                'regex:/^(?=.*[\pL\pN]).+$/u',
            ],
            'Capacity' => ['required', 'integer', 'min:1', 'max:'.($facility->Capacity ?? 100000)],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => app(BookingPolicy::class)->noticeMessage(auth()->user()),
            'Guest_Name.regex' => 'The guest name must contain at least one letter.',
            'Event_Title.regex' => 'The event name must contain at least one letter or number.',
            'Description.regex' => 'The event description must contain at least one letter or number.',
            'Other_Event_Type.regex' => 'The event type may contain letters and spaces only; numbers and special characters are not allowed.',
            'Other_Purpose.regex' => 'The other purpose must contain at least one letter or number.',
            'Capacity.required' => 'Enter the expected number of attendees.',
        ]);

        $dailySchedules = $availability->validateSchedules(
            $facility->FID,
            $validated['Proposed_Date'],
            $validated['Proposed_End_Date'],
            $validated['Daily_Schedules'],
        );
        $firstSchedule = $dailySchedules[0];
        $lastSchedule = $dailySchedules[array_key_last($dailySchedules)];
        app(BookingPolicy::class)->validateFutureStart($firstSchedule['date'], $firstSchedule['start'], 'Daily_Schedules.0.start');

        if (($validated['Type_Event'] ?? null) === 'Other') {
            $validated['Type_Event'] = trim($validated['Other_Event_Type']);
        }

        $amenityQuantities = $this->validatedAmenityQuantities(
            $validated['Amenity_ID'] ?? [],
            $validated['Amenity_Quantity'] ?? [],
        );

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('request-attachments', 'local');
        }

        try {
            $requestModel = DB::transaction(function () use ($validated, $amenityQuantities, $dailySchedules, $firstSchedule, $lastSchedule, $facility, $attachmentPath, $availability, $guestBooking): FacilityRequest {
                User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                Facility::query()->whereKey($facility->FID)->lockForUpdate()->firstOrFail();

                if (! $guestBooking) {
                    $this->validateDailyRequestLimit($validated['Proposed_Date'], $validated['Proposed_End_Date'], lockForUpdate: true);
                }
                $availability->validateSchedules($facility->FID, $validated['Proposed_Date'], $validated['Proposed_End_Date'], $dailySchedules, lock: true);

                foreach ($dailySchedules as $schedule) {
                    $this->validateAmenityAvailability(
                        $amenityQuantities,
                        $schedule['date'],
                        $schedule['date'],
                        $schedule['start'],
                        $schedule['end'],
                        lockForUpdate: true,
                    );
                }

                $event = Event::create([
                    'User_ID' => $guestBooking ? null : auth()->id(),
                    'Event_Title' => $validated['Event_Title'],
                    'Description' => $validated['Description'],
                    'Type_Event' => $validated['Type_Event'],
                    'Event_Scope' => $validated['Event_Scope'],
                ]);

                $requestModel = FacilityRequest::create([
                    'User_ID' => $guestBooking ? null : auth()->id(),
                    'Is_Guest_Booking' => $guestBooking,
                    'Guest_Name' => $guestBooking ? trim($validated['Guest_Name']) : null,
                    'Guest_Organization' => $guestBooking ? ($validated['Guest_Organization'] ?? null) : null,
                    'Guest_Email' => $guestBooking ? ($validated['Guest_Email'] ?? null) : null,
                    'Guest_Contact' => $guestBooking ? ($validated['Guest_Contact'] ?? null) : null,
                    'Created_By' => $guestBooking ? auth()->id() : null,
                    'Event_ID' => $event->EID,
                    'Facility_ID' => $facility->FID,
                    'Proposed_Date' => $validated['Proposed_Date'],
                    'Proposed_End_Date' => $validated['Proposed_End_Date'],
                    'Proposed_Start_Time' => $firstSchedule['start'],
                    'Proposed_End_Time' => $lastSchedule['end'],
                    'Daily_Schedules' => $dailySchedules,
                    'Status' => 'Pending',
                    'Purpose' => collect($validated['Purpose_Categories'])
                        ->map(fn (string $category): string => $category === 'Other'
                            ? $validated['Other_Purpose']
                            : $category)
                        ->implode(', '),
                    'Purpose_Categories' => $validated['Purpose_Categories'],
                    'Other_Purpose' => $validated['Other_Purpose'] ?? null,
                    'Capacity' => $validated['Capacity'] ?? null,
                    'attachment_path' => $attachmentPath,
                ]);

                $requestModel->amenities()->sync(
                    collect($amenityQuantities)->mapWithKeys(
                        fn (int $quantity, int $amenityId) => [$amenityId => ['quantity' => $quantity]]
                    )->all()
                );

                return $requestModel;
            }, 3);
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

        try {
            Notification::send(
                $this->notificationRecipientsFor($facility),
                new NewRequestSubmitted($requestModel)
            );
        } catch (Throwable $exception) {
            Log::warning('Facility request was saved, but its notification could not be delivered.', [
                'request_id' => $requestModel->RID,
                'facility_id' => $facility->FID,
                'exception' => $exception,
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

    public function waitingList()
    {
        return redirect(route('dashboard').'#requests');
    }

    public function updateWaitingList(Request $request, FacilityRequest $requestModel, FacilityAvailabilityService $availability)
    {
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
            'Description' => ['nullable', 'string', 'min:5', 'max:2000', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
            'Event_Scope' => ['nullable', Rule::in(['Internal', 'External'])],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Proposed_Start_Time' => ['required', 'regex:/^(?:0[5-9]|1\d|2[0-3]):(?:00|30)$/'],
            'Proposed_End_Time' => ['required', 'regex:/^(?:(?:0[6-9]|1\d|2[0-3]):(?:00|30)|24:00)$/', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Capacity' => ['required', 'integer', 'min:1', 'max:'.($requestModel->facility?->Capacity ?? 100000)],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => app(BookingPolicy::class)->noticeMessage(auth()->user()),
            'Event_Title.regex' => 'The event name must contain at least one letter or number.',
            'Description.regex' => 'The event description must contain at least one letter or number.',
            'Capacity.required' => 'Enter the expected number of attendees.',
            'Proposed_Start_Time.regex' => 'Choose a start time between 5:00 AM and 11:30 PM in 30-minute intervals.',
            'Proposed_End_Time.regex' => 'Choose an end time between 6:00 AM and 12:00 AM in 30-minute intervals.',
            'Purpose.regex' => 'The purpose must contain at least one letter or number.',
        ]);

        app(BookingPolicy::class)->validateFutureStart($validated['Proposed_Date'], $validated['Proposed_Start_Time'], 'Proposed_Start_Time');

        $this->validateBookingDuration(
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
        );

        $this->validateRequestDateRange($validated['Proposed_Date'], $validated['Proposed_End_Date']);

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

        $this->validateDailyRequestLimit($validated['Proposed_Date'], $validated['Proposed_End_Date'], $requestModel->RID);

        if ($requestModel->Facility_ID) {
            $this->validateFacilityAvailability(
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

        $this->validateAmenityAvailability(
            $amenityQuantities,
            $validated['Proposed_Date'],
            $validated['Proposed_End_Date'],
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
            $requestModel->RID,
        );

        $oldAttachmentPath = $requestModel->attachment_path;
        $attachmentPath = $oldAttachmentPath;
        $newAttachmentPath = null;

        if ($request->hasFile('attachment')) {
            $newAttachmentPath = $request->file('attachment')->store('request-attachments', 'local');
            $attachmentPath = $newAttachmentPath;
        }

        try {
            DB::transaction(function () use ($requestModel, $validated, $dailySchedules, $attachmentPath, $availability, $amenityQuantities): void {
                $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($requestModel->RID);

                $this->validateDailyRequestLimit(
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
                        $this->validateAmenityAvailability(
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

                if ($lockedRequest->event) {
                    $lockedRequest->event->update([
                        'Event_Title' => $validated['Event_Title'] ?? $lockedRequest->event->Event_Title,
                        'Description' => $validated['Description'] ?? $lockedRequest->event->Description,
                        'Type_Event' => $validated['Type_Event'] ?? $lockedRequest->event->Type_Event,
                        'Event_Scope' => $validated['Event_Scope'] ?? $lockedRequest->event->Event_Scope,
                    ]);
                }

                $lockedRequest->update([
                    'Proposed_Date' => $validated['Proposed_Date'],
                    'Proposed_End_Date' => $validated['Proposed_End_Date'],
                    'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
                    'Proposed_End_Time' => $validated['Proposed_End_Time'],
                    'Daily_Schedules' => $dailySchedules,
                    'Purpose' => $validated['Purpose'],
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
            // Remove a legacy public copy after an older request is updated.
            Storage::disk('public')->delete($oldAttachmentPath);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request details were updated and resubmitted successfully.')
            ->with('sweet_alert', [
                'title' => 'Request updated',
                'text' => 'Your request details were updated and resubmitted successfully.',
                'icon' => 'success',
            ]);
    }

    public function cancelWaitingList(Request $request, FacilityRequest $requestModel)
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

        $requestModel = DB::transaction(function () use ($requestModel, $cancellationReason): FacilityRequest {
            $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($requestModel->RID);

            if (! in_array($lockedRequest->Status, ['Pending', 'Approved'], true)) {
                throw ValidationException::withMessages([
                    'Cancellation_Reason' => 'This request has already been cancelled or can no longer be cancelled.',
                ]);
            }

            $lockedRequest->schedules()->delete();
            $lockedRequest->update([
                'Status' => 'Cancelled',
                'Cancellation_Reason' => $cancellationReason,
            ]);

            return $lockedRequest;
        }, 3);

        $requestModel->refresh()->load(['facility', 'user']);

        Notification::send(
            $this->notificationRecipientsFor($requestModel->facility),
            new RequestCancelledByUser($requestModel)
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request has been cancelled. It will be archived automatically after 10 days.')
            ->with('sweet_alert', [
                'title' => 'Request cancelled',
                'text' => 'Your request has been cancelled successfully. It will be archived automatically after 10 days.',
                'icon' => 'success',
            ]);
    }

    public function endWaitingList(Request $request, FacilityRequest $requestModel)
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

        DB::transaction(function () use ($requestModel): void {
            $lockedRequest = FacilityRequest::query()->lockForUpdate()->findOrFail($requestModel->RID);

            if ($lockedRequest->Status !== 'Approved') {
                throw ValidationException::withMessages([
                    'request' => 'This event has already ended or its status has changed.',
                ]);
            }

            $lockedRequest->update(['Status' => 'Ended']);
            $lockedRequest->delete();
        }, 3);

        return redirect()
            ->route('dashboard', ['request' => $requestModel->RID])
            ->with('success', 'Your event has ended. The booking is now read-only and you may leave optional feedback.')
            ->with('sweet_alert', [
                'title' => 'Event ended',
                'text' => 'The booking was marked as ended. Thank you for using the facility.',
                'icon' => 'success',
            ]);
    }

    /**
     * Download a request attachment after enforcing record-level access.
     */
    public function downloadAttachment(Request $request, FacilityRequest $requestModel): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $requestModel->User_ID === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();
        $isAssignedAdmin = $user->isAdmin()
            && $requestModel->facility()
                ->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user->id))
                ->exists();

        abort_unless($isOwner || $isSuperAdmin || $isAssignedAdmin, 403);
        abort_unless($requestModel->attachment_path, 404);

        $path = $requestModel->attachment_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->download(
            $path,
            "request-{$requestModel->RID}-attachment.{$extension}",
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }

    public function uploadPaymentProof(Request $request, FacilityRequest $requestModel)
    {
        abort_unless($requestModel->User_ID === $request->user()->id, 403);
        abort_unless($requestModel->Status === 'Awaiting Payment', 409, 'This request is not awaiting payment.');

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($requestModel->Payment_Proof_Path) {
            Storage::disk('local')->delete($requestModel->Payment_Proof_Path);
        }

        $path = $validated['payment_proof']->store('payment-proofs', 'local');
        $requestModel->update([
            'Payment_Proof_Path' => $path,
            'Payment_Proof_Uploaded_At' => now(),
        ]);

        return redirect()->route('dashboard', ['request' => $requestModel->RID])
            ->with('success', 'Your proof of payment was uploaded successfully and is ready for administrator review.');
    }

    public function downloadPaymentProof(Request $request, FacilityRequest $requestModel): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $requestModel->User_ID === $user->id;
        $isAuthorizedAdmin = $user->isSuperAdmin() || ($user->isAdmin()
            && $requestModel->facility()->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user->id))->exists());

        abort_unless($isOwner || $isAuthorizedAdmin, 403);
        abort_unless($requestModel->Payment_Proof_Path && Storage::disk('local')->exists($requestModel->Payment_Proof_Path), 404);

        return Storage::disk('local')->download(
            $requestModel->Payment_Proof_Path,
            'payment-proof-request-'.$requestModel->RID.'.'.pathinfo($requestModel->Payment_Proof_Path, PATHINFO_EXTENSION),
        );
    }

    /**
     * Prevent limited shared amenities from being reserved beyond their stock
     * during an overlapping date and time window.
     *
     * @param  array<int, int>  $amenityQuantities  Amenity IDs keyed to requested units.
     */
    private function validateAmenityAvailability(
        array $amenityQuantities,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): void {
        $amenities = Amenity::query()
            ->whereIn('AID', array_keys($amenityQuantities))
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->get();

        foreach ($amenities as $amenity) {
            if ($amenity->isPermanent()) {
                continue;
            }

            $requested = $amenityQuantities[(int) $amenity->AID];
            $reserved = $amenity->overlappingReservedQuantity($startDate, $endDate, $startTime, $endTime, $ignoreRequestId);
            $available = max(0, $amenity->inventory_quantity - $reserved);

            if ($requested <= $available) {
                continue;
            }

            throw ValidationException::withMessages([
                "Amenity_Quantity.{$amenity->AID}" => "Only {$amenity->quantityLabel($available)} of {$amenity->quantityLabel()} for {$amenity->name} are available for the selected date and time; {$requested} requested.",
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $amenityIds
     * @param  array<int|string, int|string|null>  $submittedQuantities
     * @return array<int, int>
     */
    private function validatedAmenityQuantities(array $amenityIds, array $submittedQuantities): array
    {
        $ids = collect($amenityIds)->map(fn ($id) => (int) $id)->unique()->values();
        $amenities = Amenity::query()->whereIn('AID', $ids)->get()->keyBy('AID');
        $quantities = [];

        foreach ($ids as $id) {
            $amenity = $amenities->get($id);

            if ($amenity?->isPermanent()) {
                $quantities[$id] = 1;

                continue;
            }

            $quantity = $submittedQuantities[$id] ?? null;

            if (! is_numeric($quantity) || (int) $quantity < 1) {
                throw ValidationException::withMessages([
                    "Amenity_Quantity.{$id}" => 'Enter the number of units needed for each selected amenity.',
                ]);
            }

            $quantities[$id] = (int) $quantity;
        }

        return $quantities;
    }

    /**
     * Ensure that every date in the selected range has exactly one valid time slot.
     *
     * @return array<int, array{date:string,start:string,end:string}>
     */
    private function validatedDailySchedules(array $validated): array
    {
        $expectedDates = collect(CarbonPeriod::create($validated['Proposed_Date'], $validated['Proposed_End_Date']))
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values();

        if ($expectedDates->count() > 31) {
            throw ValidationException::withMessages([
                'Proposed_End_Date' => 'A single request may cover no more than 31 consecutive days.',
            ]);
        }

        $submitted = collect($validated['Daily_Schedules'])
            ->map(fn (array $schedule) => [
                'date' => $schedule['date'],
                'start' => $schedule['start'],
                'end' => $schedule['end'],
            ])
            ->keyBy('date');

        if ($submitted->count() !== $expectedDates->count() || $expectedDates->contains(fn ($date) => ! $submitted->has($date))) {
            throw ValidationException::withMessages([
                'Daily_Schedules' => 'Please provide one time schedule for every selected booking day.',
            ]);
        }

        $schedules = $expectedDates->map(fn (string $date) => $submitted->get($date))->all();

        foreach ($schedules as $index => $schedule) {
            if ($schedule['end'] <= $schedule['start']) {
                throw ValidationException::withMessages([
                    "Daily_Schedules.{$index}.end" => 'The end time must be later than the start time.',
                ]);
            }

            $this->validateBookingDuration(
                $schedule['start'],
                $schedule['end'],
                "Daily_Schedules.{$index}.end",
            );
        }

        return $schedules;
    }

    private function validateBookingDuration(string $startTime, string $endTime, string $errorKey = 'Proposed_End_Time'): void
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        if ($end->greaterThan($start) && $start->diffInMinutes($end) >= 60) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => 'A booking must be at least 1 hour.',
        ]);
    }

    private function validateRequestDateRange(string $startDate, string $endDate): void
    {
        if (Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) < FacilityAvailabilityService::MAX_DAYS) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_End_Date' => 'A reservation may cover no more than 31 consecutive days.',
        ]);
    }

    private function validateDailyRequestLimit(string $startDate, string $endDate, ?int $ignoreRequestId = null, bool $lockForUpdate = false): void
    {
        if (! FacilityRequest::userReachedRequestLimitOnDate(auth()->id(), $startDate, $endDate, $ignoreRequestId, $lockForUpdate)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Date' => 'You may submit up to '.FacilityRequest::MAX_REQUESTS_PER_EVENT_DATE.' active reservation requests per event date. Cancel an existing request or choose another date.',
        ]);
    }

    private function validateFacilityAvailability(
        int $facilityId,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): void {
        if (! FacilityRequest::hasActiveFacilityConflict($facilityId, $startDate, $endDate, $startTime, $endTime, $ignoreRequestId, $lockForUpdate)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Start_Time' => 'This facility already has a request during the selected time. Please choose another time.',
        ]);
    }

    public function showEventRequest(Event $event)
    {
        $amenities = Amenity::where('Status', 'Available')->orderBy('name')->get();

        return view('requests.event-request', compact('event', 'amenities'));
    }

    public function storeEventRequest(Request $request, Event $event)
    {
        $earliestReservationDate = app(BookingPolicy::class)->earliestDate(auth()->user());

        $validated = $request->validate([
            'Amenity_ID' => ['nullable', 'array'],
            'Amenity_ID.*' => ['integer', 'distinct', Rule::exists('amenities', 'AID')->where('Status', 'Available')],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Proposed_Start_Time' => ['required', 'regex:/^(?:0[5-9]|1\d|2[0-3]):(?:00|30)$/'],
            'Proposed_End_Time' => ['required', 'regex:/^(?:(?:0[6-9]|1\d|2[0-3]):(?:00|30)|24:00)$/', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000', 'regex:/^(?=.*[\pL\pN]).+$/u'],
            'Capacity' => ['required', 'integer', 'min:1', 'max:100000'],
        ], [
            'Proposed_Date.after_or_equal' => app(BookingPolicy::class)->noticeMessage(auth()->user()),
            'Proposed_Start_Time.regex' => 'Choose a start time between 5:00 AM and 11:30 PM in 30-minute intervals.',
            'Proposed_End_Time.regex' => 'Choose an end time between 6:00 AM and 12:00 AM in 30-minute intervals.',
            'Purpose.regex' => 'The purpose must contain at least one letter or number.',
            'Capacity.required' => 'Enter the expected number of attendees.',
        ]);

        app(BookingPolicy::class)->validateFutureStart($validated['Proposed_Date'], $validated['Proposed_Start_Time'], 'Proposed_Start_Time');

        $this->validateBookingDuration(
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
        );

        $this->validateRequestDateRange($validated['Proposed_Date'], $validated['Proposed_End_Date']);

        try {
            $requestModel = DB::transaction(function () use ($validated, $event): FacilityRequest {
                User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $this->validateDailyRequestLimit($validated['Proposed_Date'], $validated['Proposed_End_Date'], lockForUpdate: true);

                $requestModel = FacilityRequest::create([
                    'Event_ID' => $event->EID,
                    'User_ID' => auth()->id(),
                    'Proposed_Date' => $validated['Proposed_Date'],
                    'Proposed_End_Date' => $validated['Proposed_End_Date'],
                    'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
                    'Proposed_End_Time' => $validated['Proposed_End_Time'],
                    'Daily_Schedules' => collect(CarbonPeriod::create($validated['Proposed_Date'], $validated['Proposed_End_Date']))
                        ->map(fn ($date) => [
                            'date' => $date->format('Y-m-d'),
                            'start' => $validated['Proposed_Start_Time'],
                            'end' => $validated['Proposed_End_Time'],
                        ])->all(),
                    'Status' => 'Pending',
                    'Purpose' => $validated['Purpose'],
                    'Capacity' => $validated['Capacity'] ?? null,
                ]);

                $requestModel->amenities()->sync($validated['Amenity_ID'] ?? []);

                return $requestModel;
            }, 3);
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

        try {
            Notification::send(
                User::query()->where('user_type', 'super_admin')->get(),
                new NewRequestSubmitted($requestModel)
            );
        } catch (Throwable $exception) {
            Log::warning('Event booking request was saved, but its notification could not be delivered.', [
                'request_id' => $requestModel->RID,
                'event_id' => $event->EID,
                'exception' => $exception,
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your event booking request has been submitted successfully.');
    }

    private function notificationRecipientsFor(?Facility $facility): Collection
    {
        $superAdmins = User::query()->where('user_type', 'super_admin')->get();

        if (! $facility) {
            return $superAdmins;
        }

        $officeAdmins = $facility->assignedAdmins()
            ->where('users.user_type', 'admin')
            ->get();

        return $superAdmins
            ->merge($officeAdmins)
            ->unique('id')
            ->values();
    }
}
