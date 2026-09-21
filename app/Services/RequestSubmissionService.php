<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Notifications\NewRequestSubmitted;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RequestSubmissionService
{
    public function __construct(private readonly BookingRequestValidator $validator) {}

    public function submitEvent(Event $event, User $actor, array $validated): FacilityRequest
    {
        $request = DB::transaction(function () use ($event, $actor, $validated): FacilityRequest {
            User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $this->validator->validateDailyRequestLimit(
                $actor->id,
                $validated['Proposed_Date'],
                $validated['Proposed_End_Date'],
                lockForUpdate: true,
            );

            $request = FacilityRequest::create([
                'Event_ID' => $event->EID,
                'User_ID' => $actor->id,
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

            $request->amenities()->sync($validated['Amenity_ID'] ?? []);

            return $request;
        }, 3);

        $this->notifySubmitted($request);

        return $request;
    }

    /**
     * @param array<int, int> $amenityQuantities
     * @param array<int, array{date:string,start:string,end:string}> $dailySchedules
     */
    public function submitFacility(
        Facility $facility,
        User $actor,
        array $validated,
        array $amenityQuantities,
        array $dailySchedules,
        ?string $attachmentPath,
        FacilityAvailabilityService $availability,
        bool $guestBooking,
    ): FacilityRequest {
        $firstSchedule = $dailySchedules[0];
        $lastSchedule = $dailySchedules[array_key_last($dailySchedules)];

        $request = DB::transaction(function () use ($facility, $actor, $validated, $amenityQuantities, $dailySchedules, $firstSchedule, $lastSchedule, $attachmentPath, $availability, $guestBooking): FacilityRequest {
            User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            Facility::query()->whereKey($facility->FID)->lockForUpdate()->firstOrFail();

            if (! $guestBooking) {
                $this->validator->validateDailyRequestLimit($actor->id, $validated['Proposed_Date'], $validated['Proposed_End_Date'], lockForUpdate: true);
            }

            $availability->validateSchedules($facility->FID, $validated['Proposed_Date'], $validated['Proposed_End_Date'], $dailySchedules, lock: true);

            foreach ($dailySchedules as $schedule) {
                $this->validator->validateAmenityAvailability(
                    $amenityQuantities,
                    $schedule['date'],
                    $schedule['date'],
                    $schedule['start'],
                    $schedule['end'],
                    lockForUpdate: true,
                );
            }

            $event = Event::create([
                'User_ID' => $guestBooking ? null : $actor->id,
                'Event_Title' => $validated['Event_Title'],
                'Description' => $validated['Description'],
                'Type_Event' => $validated['Type_Event'],
                'Event_Scope' => $validated['Event_Scope'],
            ]);

            $request = FacilityRequest::create([
                'User_ID' => $guestBooking ? null : $actor->id,
                'Is_Guest_Booking' => $guestBooking,
                'Guest_Name' => $guestBooking ? trim($validated['Guest_Name']) : null,
                'Guest_Organization' => $guestBooking ? ($validated['Guest_Organization'] ?? null) : null,
                'Guest_Email' => $guestBooking ? ($validated['Guest_Email'] ?? null) : null,
                'Guest_Contact' => $guestBooking ? ($validated['Guest_Contact'] ?? null) : null,
                'Created_By' => $guestBooking ? $actor->id : null,
                'Event_ID' => $event->EID,
                'Facility_ID' => $facility->FID,
                'Proposed_Date' => $validated['Proposed_Date'],
                'Proposed_End_Date' => $validated['Proposed_End_Date'],
                'Proposed_Start_Time' => $firstSchedule['start'],
                'Proposed_End_Time' => $lastSchedule['end'],
                'Daily_Schedules' => $dailySchedules,
                'Status' => 'Pending',
                'Purpose' => collect($validated['Purpose_Categories'])
                    ->map(fn (string $category): string => $category === 'Other' ? $validated['Other_Purpose'] : $category)
                    ->implode(', '),
                'Purpose_Categories' => $validated['Purpose_Categories'],
                'Other_Purpose' => $validated['Other_Purpose'] ?? null,
                'Capacity' => $validated['Capacity'] ?? null,
                'attachment_path' => $attachmentPath,
            ]);

            $request->amenities()->sync(
                collect($amenityQuantities)->mapWithKeys(
                    fn (int $quantity, int $amenityId) => [$amenityId => ['quantity' => $quantity]]
                )->all()
            );

            return $request;
        }, 3);

        $this->notifySubmitted($request, $facility);

        return $request;
    }

    private function notifySubmitted(FacilityRequest $request, ?Facility $facility = null): void
    {
        try {
            Notification::send($this->notificationRecipientsFor($facility), new NewRequestSubmitted($request));
        } catch (Throwable $exception) {
            Log::warning('Request was saved, but its notification could not be delivered.', [
                'request_id' => $request->RID,
                'facility_id' => $facility?->FID,
                'exception' => $exception,
            ]);
        }
    }

    private function notificationRecipientsFor(?Facility $facility): Collection
    {
        $superAdmins = User::query()->where('user_type', 'super_admin')->get();

        if (! $facility) {
            return $superAdmins;
        }

        return $superAdmins
            ->merge($facility->assignedAdmins()->where('users.user_type', 'admin')->get())
            ->unique('id')
            ->values();
    }
}
