<?php

namespace App\Http\Controllers;

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Notifications\NewRequestSubmitted;
use App\Notifications\RequestCancelledByUser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacilitiesController extends Controller
{
    public function showRequest(Facilities $facility)
    {
        $facility->load(['amenities', 'images']);
        $events = Events::orderBy('Event_Title')->get();

        return view('requests.create', compact('facility', 'events'));
    }

    public function storeRequest(Request $request, Facilities $facility)
    {
        $earliestReservationDate = now()->addDays(3)->toDateString();

        $validated = $request->validate([
            'Amenity_ID' => ['array', 'nullable'],
            'Amenity_ID.*' => [
                'integer',
                Rule::exists('facility_amenity', 'Amenity_ID')->where('Facility_ID', $facility->FID),
                Rule::exists('amenities', 'AID')->where('Status', 'Available'),
            ],
            'Event_ID' => ['nullable', 'integer', Rule::exists('events', 'EID')],
            'Event_Title' => ['nullable', 'string', 'min:3', 'max:255'],
            'Description' => ['nullable', 'string', 'min:5', 'max:2000'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
            'Other_Event_Type' => ['nullable', 'required_if:Type_Event,Other', 'string', 'max:100'],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_Start_Time' => ['required', 'date_format:H:i'],
            'Proposed_End_Time' => ['required', 'date_format:H:i', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:'.($facility->Capacity ?? 100000)],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => 'Reservations must be submitted at least 3 days before the event date.',
        ]);

        $this->validateDailyRequestLimit($validated['Proposed_Date']);

        $this->validateAmenityAvailability(
            $validated['Amenity_ID'] ?? [],
            $validated['Proposed_Date'],
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
        );

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('request-attachments', 'public');
        }

        if (($validated['Type_Event'] ?? null) === 'Other') {
            $validated['Type_Event'] = trim($validated['Other_Event_Type']);
        }

        $event = null;

        if (! empty($validated['Event_Title']) || ! empty($validated['Description']) || ! empty($validated['Type_Event'])) {
            $event = Events::create([
                'User_ID' => auth()->id(),
                'Event_Title' => $validated['Event_Title'] ?? 'Untitled Event',
                'Description' => $validated['Description'] ?? null,
                'Type_Event' => $validated['Type_Event'] ?? null,
                'Status' => 'Upcoming',
            ]);
        }

        $requestModel = Requests::create([
            'User_ID' => auth()->id(),
            'Event_ID' => $event?->EID ?? $validated['Event_ID'] ?? null,
            'Facility_ID' => $facility->FID,
            'Proposed_Date' => $validated['Proposed_Date'],
            'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
            'Proposed_End_Time' => $validated['Proposed_End_Time'],
            'Status' => 'Pending',
            'Purpose' => $validated['Purpose'],
            'Capacity' => $validated['Capacity'] ?? null,
            'attachment_path' => $attachmentPath,
        ]);

        $requestModel->amenities()->sync($validated['Amenity_ID'] ?? []);

        Notification::send(
            $this->notificationRecipientsFor($facility),
            new NewRequestSubmitted($requestModel)
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request has been submitted successfully.');
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

        $earliestReservationDate = now()->addDays(3)->toDateString();

        $validated = $request->validate([
            'Event_Title' => ['nullable', 'string', 'min:3', 'max:255'],
            'Description' => ['nullable', 'string', 'min:5', 'max:2000'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
            'Proposed_Date' => ['required', 'date', 'after_or_equal:'.$earliestReservationDate],
            'Proposed_Start_Time' => ['required', 'date_format:H:i'],
            'Proposed_End_Time' => ['required', 'date_format:H:i', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'Event_Status' => ['nullable', 'string', 'in:Upcoming,Ongoing,Completed,Cancelled'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'Proposed_Date.after_or_equal' => 'Reservations must be scheduled at least 3 days from today.',
        ]);

        $this->validateDailyRequestLimit($validated['Proposed_Date'], $requestModel->RID);

        $this->validateAmenityAvailability(
            $requestModel->amenities()->pluck('amenities.AID')->all(),
            $validated['Proposed_Date'],
            $validated['Proposed_Start_Time'],
            $validated['Proposed_End_Time'],
            $requestModel->RID,
        );

        $attachmentPath = $requestModel->attachment_path;

        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            $attachmentPath = $request->file('attachment')->store('request-attachments', 'public');
        }

        if ($requestModel->event) {
            $requestModel->event->update([
                'Event_Title' => $validated['Event_Title'] ?? $requestModel->event->Event_Title,
                'Description' => $validated['Description'] ?? $requestModel->event->Description,
                'Type_Event' => $validated['Type_Event'] ?? $requestModel->event->Type_Event,
                'Status' => $validated['Event_Status'] ?? $requestModel->event->Status,
            ]);
        }

        $requestModel->update([
            'Proposed_Date' => $validated['Proposed_Date'],
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
            ->with('success', 'Your request details were updated and resubmitted successfully.');
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

        $validated = $request->validate([
            'Cancellation_Reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $requestModel->schedule()->delete();

        if ($requestModel->event && $requestModel->event->Status !== 'Completed') {
            $requestModel->event->update([
                'Status' => 'Cancelled',
            ]);
        }

        $requestModel->update([
            'Status' => 'Cancelled',
            'Cancellation_Reason' => $validated['Cancellation_Reason'],
        ]);

        $requestModel->refresh()->load(['facility', 'user']);

        Notification::send(
            $this->notificationRecipientsFor($requestModel->facility),
            new RequestCancelledByUser($requestModel)
        );

        $requestModel->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your request has been cancelled and moved to the archive.');
    }

    /**
     * Prevent limited shared amenities from being reserved beyond their stock
     * during an overlapping date and time window.
     *
     * @param  array<int, int|string>  $amenityIds
     */
    private function validateAmenityAvailability(
        array $amenityIds,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): void {
        $limitedAmenities = Amenities::query()
            ->whereIn('AID', $amenityIds)
            ->whereNotNull('reservation_limit')
            ->get();

        foreach ($limitedAmenities as $amenity) {
            if (! $amenity->isFullyReserved($date, $startTime, $endTime, $ignoreRequestId)) {
                continue;
            }

            throw ValidationException::withMessages([
                'Amenity_ID' => "{$amenity->name} is fully reserved for the selected date and time. Please choose another time or remove this amenity.",
            ]);
        }
    }

    private function validateDailyRequestLimit(string $date, ?int $ignoreRequestId = null): void
    {
        if (! Requests::userHasRequestOnDate(auth()->id(), $date, $ignoreRequestId)) {
            return;
        }

        throw ValidationException::withMessages([
            'Proposed_Date' => 'You may only submit one reservation request per event date. Please choose another date.',
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
            'Proposed_Start_Time' => ['required', 'date_format:H:i'],
            'Proposed_End_Time' => ['required', 'date_format:H:i', 'after:Proposed_Start_Time'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'Proposed_Date.after_or_equal' => 'Reservations must be submitted at least 3 days before the event date.',
        ]);

        $this->validateDailyRequestLimit($validated['Proposed_Date']);

        $requestModel = Requests::create([
            'Event_ID' => $event->EID,
            'User_ID' => auth()->id(),
            'Proposed_Date' => $validated['Proposed_Date'],
            'Proposed_Start_Time' => $validated['Proposed_Start_Time'],
            'Proposed_End_Time' => $validated['Proposed_End_Time'],
            'Status' => 'Pending',
            'Purpose' => $validated['Purpose'],
            'Capacity' => $validated['Capacity'] ?? null,
        ]);

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
