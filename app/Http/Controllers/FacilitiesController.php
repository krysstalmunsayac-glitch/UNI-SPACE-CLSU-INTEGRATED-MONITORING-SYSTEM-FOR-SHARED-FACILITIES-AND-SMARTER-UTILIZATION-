<?php

namespace App\Http\Controllers;

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\NewRequestSubmitted;
use App\Notifications\RequestCancelledByUser;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class FacilitiesController extends Controller
{
    public function showRequest(Facilities $facility)
    {
        abort_unless($facility->Status === 'Available', 409, 'This facility is not currently available for requests.');

        $facility->load('images');

        $availableAmenities = $facility->amenities()
            ->where('amenities.Status', 'Available')
            ->withCount([
                'requests as current_usage_count' => fn ($requestQuery) => $requestQuery
                    ->whereIn('Status', ['Pending', 'Approved']),
            ])
            ->get()
            ->filter(fn (Amenities $amenity) => $amenity->reservation_limit === null
                || $amenity->current_usage_count < $amenity->reservation_limit)
            ->values();

        $events = Events::orderBy('Event_Title')->get();

        return view('requests.create', compact('facility', 'events', 'availableAmenities'));
    }

    public function storeRequest(Request $request, Facilities $facility)
    {
        abort_unless($facility->Status === 'Available', 409, 'This facility is not currently available for requests.');

        $earliestReservationDate = now()->addDays(3)->toDateString();

        $validated = $request->validate([
            'Amenity_ID' => ['array', 'nullable'],
            'Amenity_ID.*' => [
                'integer',
                Rule::exists('facility_amenity', 'Amenity_ID')->where('Facility_ID', $facility->FID),
                Rule::exists('amenities', 'AID')->where('Status', 'Available'),
            ],
            'Event_ID' => ['nullable', 'integer', Rule::exists('events', 'EID')],
            'Event_Title' => ['required', 'string', 'min:3', 'max:255'],
            'Description' => ['required', 'string', 'min:5', 'max:2000'],
            'Type_Event' => ['required', 'string', 'max:100'],
            'Other_Event_Type' => ['nullable', 'required_if:Type_Event,Other', 'string', 'max:100'],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Daily_Schedules' => ['required', 'array', 'min:1', 'max:31'],
            'Daily_Schedules.*.date' => ['required', 'date_format:Y-m-d'],
            'Daily_Schedules.*.start' => ['required', 'date_format:H:i'],
            'Daily_Schedules.*.end' => ['required', 'date_format:H:i'],
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
            ],
            'Reservation_Frequency' => ['required', Rule::in(['First time', 'Occasionally', 'Regularly', 'Frequently'])],
            'Facility_Importance' => ['required', Rule::in(['Very Important', 'Important', 'Neutral', 'Slightly Important', 'Not Important'])],
            'Requirements_Fit' => ['required', Rule::in(['Yes, completely', 'Mostly', 'Partially', 'No'])],
            'Reserve_Again_Intent' => ['required', Rule::in(['Definitely Yes', 'Probably Yes', 'Not Sure', 'Probably No', 'Definitely No'])],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:'.($facility->Capacity ?? 100000)],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => 'Reservations must be submitted at least 3 days before the event date.',
        ]);

        $dailySchedules = $this->validatedDailySchedules($validated);
        $firstSchedule = $dailySchedules[0];
        $lastSchedule = $dailySchedules[array_key_last($dailySchedules)];

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('request-attachments', 'local');
        }

        if (($validated['Type_Event'] ?? null) === 'Other') {
            $validated['Type_Event'] = trim($validated['Other_Event_Type']);
        }

        try {
            $requestModel = DB::transaction(function () use ($validated, $dailySchedules, $firstSchedule, $lastSchedule, $facility, $attachmentPath): Requests {
                User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                Facilities::query()->whereKey($facility->FID)->lockForUpdate()->firstOrFail();

                $this->validateDailyRequestLimit($validated['Proposed_Date'], $validated['Proposed_End_Date'], lockForUpdate: true);
                if (Requests::hasActiveDailyScheduleConflict($facility->FID, $dailySchedules, lockForUpdate: true)) {
                    throw ValidationException::withMessages([
                        'Daily_Schedules' => 'This facility already has a request during one or more selected time slots. Please adjust the highlighted schedule.',
                    ]);
                }

                foreach ($dailySchedules as $schedule) {
                    $this->validateAmenityAvailability(
                        $validated['Amenity_ID'] ?? [],
                        $schedule['date'],
                        $schedule['date'],
                        $schedule['start'],
                        $schedule['end'],
                        lockForUpdate: true,
                    );
                }

                $event = Events::create([
                    'User_ID' => auth()->id(),
                    'Event_Title' => $validated['Event_Title'],
                    'Description' => $validated['Description'],
                    'Type_Event' => $validated['Type_Event'],
                ]);

                $requestModel = Requests::create([
                    'User_ID' => auth()->id(),
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
                    'Reservation_Frequency' => $validated['Reservation_Frequency'],
                    'Facility_Importance' => $validated['Facility_Importance'],
                    'Requirements_Fit' => $validated['Requirements_Fit'],
                    'Reserve_Again_Intent' => $validated['Reserve_Again_Intent'],
                    'Capacity' => $validated['Capacity'] ?? null,
                    'attachment_path' => $attachmentPath,
                ]);

                $requestModel->amenities()->sync($validated['Amenity_ID'] ?? []);

                return $requestModel;
            }, 3);
        } catch (ValidationException $exception) {
            if ($attachmentPath) Storage::disk('local')->delete($attachmentPath);
            throw $exception;
        } catch (Throwable $exception) {
            if ($attachmentPath) Storage::disk('local')->delete($attachmentPath);

            Log::error('Facility request submission failed.', [
                'user_id' => auth()->id(),
                'facility_id' => $facility->FID,
                'exception' => $exception,
            ]);

            return back()->withInput()->withErrors([
                'submission' => 'We could not submit your request right now. Nothing was saved. Please try again.',
            ]);
        }

        Notification::send(
            $this->notificationRecipientsFor($facility),
            new NewRequestSubmitted($requestModel)
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request has been submitted successfully.')
            ->with('sweet_alert', [
                'title' => 'Request sent',
                'text' => 'Your request has been submitted successfully.',
                'icon' => 'success',
            ]);
    }

    public function waitingList()
    {
        return redirect(route('dashboard').'#requests');
    }

    public function updateWaitingList(Request $request, Requests $requestModel)
    {
        if ($requestModel->User_ID !== auth()->id()) {
            abort(403);
        }

        if ($requestModel->Status === 'Ended') {
            return redirect()
                ->route('dashboard', ['request' => $requestModel->RID])
                ->with('warning', 'This event has ended. Its request details can no longer be changed.');
        }

        $earliestReservationDate = now()->addDays(3)->toDateString();

        $validated = $request->validate([
            'Event_Title' => ['nullable', 'string', 'min:3', 'max:255'],
            'Description' => ['nullable', 'string', 'min:5', 'max:2000'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Proposed_Start_Time' => ['required', 'date_format:H:i'],
            'Proposed_End_Time' => ['required', 'date_format:H:i', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => 'Reservations must be scheduled at least 3 days from today.',
        ]);

        $this->validateBookingDuration(
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
        );

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

        $this->validateAmenityAvailability(
            $requestModel->amenities()->pluck('amenities.AID')->all(),
            $validated['Proposed_Date'],
            $validated['Proposed_End_Date'],
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
            $requestModel->RID,
        );

        $attachmentPath = $requestModel->attachment_path;

        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('local')->delete($attachmentPath);
                // Remove a legacy public copy after an older request is updated.
                Storage::disk('public')->delete($attachmentPath);
            }

            $attachmentPath = $request->file('attachment')->store('request-attachments', 'local');
        }

        if ($requestModel->event) {
            $requestModel->event->update([
                'Event_Title' => $validated['Event_Title'] ?? $requestModel->event->Event_Title,
                'Description' => $validated['Description'] ?? $requestModel->event->Description,
                'Type_Event' => $validated['Type_Event'] ?? $requestModel->event->Type_Event,
            ]);
        }

        $requestModel->update([
            'Proposed_Date' => $validated['Proposed_Date'],
            'Proposed_End_Date' => $validated['Proposed_End_Date'],
            'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
            'Proposed_End_Time' => $validated['Proposed_End_Time'],
            'Purpose' => $validated['Purpose'],
            'Capacity' => $validated['Capacity'] ?? null,
            'attachment_path' => $attachmentPath,
            'Status' => $requestModel->Status === 'Rejected' ? 'Pending' : $requestModel->Status,
            'Review_Notes' => null,
            'Review_Requested_At' => null,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request details were updated and resubmitted successfully.')
            ->with('sweet_alert', [
                'title' => 'Request updated',
                'text' => 'Your request details were updated and resubmitted successfully.',
                'icon' => 'success',
            ]);
    }

    public function cancelWaitingList(Request $request, Requests $requestModel)
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

        $requestModel = DB::transaction(function () use ($requestModel, $cancellationReason): Requests {
            $lockedRequest = Requests::query()->lockForUpdate()->findOrFail($requestModel->RID);

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

    /**
     * Download a request attachment after enforcing record-level access.
     */
    public function downloadAttachment(Request $request, Requests $requestModel): StreamedResponse
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

    /**
     * Prevent limited shared amenities from being reserved beyond their stock
     * during an overlapping date and time window.
     *
     * @param  array<int, int|string>  $amenityIds
     */
    private function validateAmenityAvailability(
        array $amenityIds,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): void {
        $limitedAmenities = Amenities::query()
            ->whereIn('AID', $amenityIds)
            ->whereNotNull('reservation_limit')
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->get();

        foreach ($limitedAmenities as $amenity) {
            if (! $amenity->isFullyReserved($startDate, $endDate, $startTime, $endTime, $ignoreRequestId)) {
                continue;
            }

            throw ValidationException::withMessages([
                'Amenity_ID' => "{$amenity->name} is fully reserved for the selected date and time. Please choose another time or remove this amenity.",
            ]);
        }
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

        if ($end->lessThanOrEqualTo($start) || $start->diffInMinutes($end) >= 120) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => 'A booking must be at least 2 hours.',
        ]);
    }

    private function validateDailyRequestLimit(string $startDate, string $endDate, ?int $ignoreRequestId = null, bool $lockForUpdate = false): void
    {
        if (! Requests::userHasRequestOnDate(auth()->id(), $startDate, $endDate, $ignoreRequestId, $lockForUpdate)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Date' => 'You may only submit one reservation request per event date. Please choose another date.',
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
        if (! Requests::hasActiveFacilityConflict($facilityId, $startDate, $endDate, $startTime, $endTime, $ignoreRequestId, $lockForUpdate)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Start_Time' => 'This facility already has a request during the selected time. Please choose another time.',
        ]);
    }

    public function showEventRequest(Events $event)
    {
        $amenities = Amenities::where('Status', 'Available')->orderBy('name')->get();

        return view('RequestForm.event-request', compact('event', 'amenities'));
    }

    public function storeEventRequest(Request $request, Events $event)
    {
        $earliestReservationDate = now()->addDays(3)->toDateString();

        $validated = $request->validate([
            'Amenity_ID' => ['nullable', 'array'],
            'Amenity_ID.*' => ['integer', Rule::exists('amenities', 'AID')],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Proposed_Start_Time' => ['required', 'date_format:H:i'],
            'Proposed_End_Time' => ['required', 'date_format:H:i', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'Proposed_Date.after_or_equal' => 'Reservations must be submitted at least 3 days before the event date.',
        ]);

        $this->validateBookingDuration(
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
        );

        try {
            $requestModel = DB::transaction(function () use ($validated, $event): Requests {
                User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $this->validateDailyRequestLimit($validated['Proposed_Date'], $validated['Proposed_End_Date'], lockForUpdate: true);

                $requestModel = Requests::create([
                    'Event_ID' => $event->EID,
                    'User_ID' => auth()->id(),
                    'Proposed_Date' => $validated['Proposed_Date'],
                    'Proposed_End_Date' => $validated['Proposed_End_Date'],
                    'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
                    'Proposed_End_Time' => $validated['Proposed_End_Time'],
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

        Notification::send(
            User::query()->where('user_type', 'super_admin')->get(),
            new NewRequestSubmitted($requestModel)
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your event booking request has been submitted successfully.');
    }

    private function notificationRecipientsFor(?Facilities $facility): Collection
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
