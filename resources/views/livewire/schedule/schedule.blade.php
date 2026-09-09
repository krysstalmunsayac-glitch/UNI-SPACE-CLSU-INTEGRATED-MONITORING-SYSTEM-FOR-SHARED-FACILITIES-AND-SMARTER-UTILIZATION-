<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Support\Ui;
use App\Models\Facilities;
use App\Models\Schedule;
use App\Models\Requests;
use App\Support\CalendarColor;
use App\Services\FacilityAvailabilityService;
use App\Services\BookingPolicy;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {

    use WithPagination;

    // ---- View state ----
    #[Url]
    public string $view = 'weekly'; // weekly | monthly

    public $editingId = null;
    public bool $showModal = false;
    public bool $showArchivedModal = false;

    public ?string $selectedDate = null;
    public $noticeDays = 3;

    // ---- Filters ----
    public string $searchInput = '';
    public string $search = '';
    public ?int $facilityFilter = null;

    // ---- Form fields ----
    #[Validate('required|exists:requests,RID')]
    public ?int $Request_ID = null;

    #[Validate('required|date')]
    public string $Date = '';

    #[Validate('required')]
    public string $Start_Time = '08:00';

    #[Validate('required|after:Start_Time')]
    public string $End_Time = '09:00';

    #[Validate('required|in:Booked,Blocked')]
    public string $Status = 'Booked';

    public function mount(): void
    {
        Requests::markPastRequestsAsEnded();
        $this->Date = Carbon::now()->toDateString();
        $this->noticeDays = app(BookingPolicy::class)->noticeDays();
    }

    public function saveBookingRules(): void
    {
        app(BookingPolicy::class)->saveNoticeDays(auth()->user(), $this->noticeDays);
        $this->noticeDays = app(BookingPolicy::class)->noticeDays();
        $this->resetValidation('noticeDays');
        Ui::toast(text: 'Booking notice period updated.', variant: 'success');
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['weekly', 'monthly']) ? $view : 'weekly';
    }

    public function updatedFacilityFilter(): void
    {
        $this->dispatch('calendar-refresh', events: $this->calendarEvents);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('archivedSchedulesPage');
        $this->dispatch('calendar-refresh', events: $this->calendarEvents);
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function updatedStartTime(string $value): void
    {
        try {
            $endMinutes = $this->timeToMinutes($value) + FacilityAvailabilityService::MINIMUM_MINUTES;

            if ($endMinutes <= $this->timeToMinutes(FacilityAvailabilityService::CLOSES_AT)) {
                $this->End_Time = $this->formatMinutes($endMinutes);
            }
        } catch (\Throwable) {
            // Validation will present a useful message for malformed values.
        }
    }

    // ---- CRUD ----
    public function resetForm(): void
    {
        $this->reset(['Request_ID']);
        $this->Date = $this->selectedDate ?? Carbon::now()->toDateString();
        $this->Start_Time = '08:00';
        $this->End_Time = '09:00';
        $this->Status = 'Booked';
        $this->editingId = null;
        $this->resetValidation();
    }

    /**
     * Ignore create calls from browser tabs that still have the old calendar
     * JavaScript loaded. Manual schedule creation is intentionally disabled.
     */
    public function create(?string $date = null): void
    {
        $this->showModal = false;
    }

    public function save(): void
    {
        abort_if($this->editingId === null, 403, 'Creating schedules manually is not allowed.');

        // Authorize the existing record before validating any user-controlled
        // fields, then enforce the saved notice period and super-admin exception.
        $schedule = $this->getScopedSchedule((int) $this->editingId);
        $validated = $this->validate([
            'Request_ID' => ['required', 'exists:requests,RID'],
            'Date' => ['required', 'date', 'after_or_equal:'.app(BookingPolicy::class)->earliestDate(auth()->user())],
            'Start_Time' => ['required', 'date_format:H:i'],
            'End_Time' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d|24:00$/', 'after:Start_Time'],
            'Status' => ['required', 'in:Booked,Blocked'],
        ], [
            'Date.after_or_equal' => app(BookingPolicy::class)->noticeMessage(auth()->user()),
        ]);

        app(BookingPolicy::class)->validateFutureStart($validated['Date'], $validated['Start_Time'], 'Start_Time');

        $startTime = $this->timeToMinutes($validated['Start_Time']);
        $endTime = $this->timeToMinutes($validated['End_Time']);

        if (($endTime - $startTime) < 60) {
            $this->addError('End_Time', 'A booking must be at least 1 hour.');

            return;
        }

        if (
            $startTime < $this->timeToMinutes(FacilityAvailabilityService::OPENS_AT)
            || $endTime > $this->timeToMinutes(FacilityAvailabilityService::CLOSES_AT)
            || $startTime % FacilityAvailabilityService::SLOT_MINUTES !== 0
            || $endTime % FacilityAvailabilityService::SLOT_MINUTES !== 0
        ) {
            $this->addError('Start_Time', 'Choose a 30-minute time slot between 5:00 AM and 12:00 AM.');

            return;
        }

        // A schedule belongs to the request that created it. Do not allow a
        // modified Livewire payload to move it to a different request.
        if ((int) $validated['Request_ID'] !== (int) $schedule->Request_ID) {
            $this->Request_ID = (int) $schedule->Request_ID;
            $this->addError('Request_ID', 'The request assigned to a schedule cannot be changed.');

            return;
        }

        // Prevent the same request from being scheduled twice
        $duplicateRequestQuery = Schedule::where('Request_ID', $validated['Request_ID']);

        if ($this->editingId) {
            $duplicateRequestQuery->where('SID', '!=', $this->editingId);
        }

        if ($duplicateRequestQuery->exists()) {
            $this->addError('Request_ID', 'This request already has a schedule.');
            return;
        }

        // Prevent overlapping bookings on the same facility/date/time
        $request = $this->getScopedRequest($validated['Request_ID']);
        $facilityId = $request->facility?->FID;

        if ($facilityId) {
            $overlapQuery = Schedule::where('Date', $validated['Date'])
                ->whereHas('request.facility', function ($query) use ($facilityId) {
                    $query->where('FID', $facilityId);
                })
                ->where(function ($query) use ($validated) {
                    $query->where('Start_Time', '<', $validated['End_Time'])
                        ->where('End_Time', '>', $validated['Start_Time']);
                });

            if ($this->editingId) {
                $overlapQuery->where('SID', '!=', $this->editingId);
            }

            if ($overlapQuery->exists()) {
                $this->addError('Date', 'This facility is already booked during that time.');
                return;
            }
        }

        $schedule->update($validated);

        $this->dispatch('calendar-refresh', events: $this->calendarEvents);

        Ui::toast(
            text: 'Schedule updated successfully!',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Schedule updated',
            'text' => 'Schedule updated successfully!',
            'icon' => 'success',
        ]);

        $this->showModal = false;
        $this->resetForm();
    }

    protected function validationAttributes(): array
    {
        return [
            'Request_ID' => 'request',
            'Date'       => 'date',
            'Start_Time' => 'start time',
            'End_Time'   => 'end time',
            'Status'     => 'status',
        ];
    }

    #[Computed]
    public function startTimeSlots(): array
    {
        return app(FacilityAvailabilityService::class)->slots();
    }

    #[Computed]
    public function endTimeSlots(): array
    {
        $slots = [];
        $minimum = $this->timeToMinutes($this->Start_Time ?: FacilityAvailabilityService::OPENS_AT)
            + FacilityAvailabilityService::MINIMUM_MINUTES;
        $close = $this->timeToMinutes(FacilityAvailabilityService::CLOSES_AT);

        for ($minute = $minimum; $minute <= $close; $minute += FacilityAvailabilityService::SLOT_MINUTES) {
            $slots[] = $this->formatMinutes($minute);
        }

        return $slots;
    }

    private function timeToMinutes(string $time): int
    {
        if ($time === '24:00') {
            return 24 * 60;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }

    private function formatMinutes(int $minutes): string
    {
        return $minutes >= 24 * 60
            ? '24:00'
            : sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function edit(int $scheduleId): void
    {
        $schedule = $this->getScopedSchedule($scheduleId);

        $this->editingId  = $schedule->SID;
        $this->Request_ID = $schedule->Request_ID;
        $this->Date       = Carbon::parse($schedule->Date)->toDateString();
        $this->Start_Time = Carbon::parse($schedule->Start_Time)->format('H:i');
        $rawEndTime = substr((string) $schedule->getRawOriginal('End_Time'), 0, 5);
        $this->End_Time   = $rawEndTime === '24:00' ? '24:00' : Carbon::parse($schedule->End_Time)->format('H:i');
        $this->Status     = $schedule->Status;
        $this->showModal  = true;
    }

    public function delete(int $scheduleId): void
    {
        $this->getScopedSchedule($scheduleId)->delete();

        $this->dispatch('calendar-refresh', events: $this->calendarEvents);

        Ui::toast(
            text: 'Schedule archived successfully!',
            variant: 'success'
        );

        $this->dispatch('swal', [
            'title' => 'Schedule archived',
            'text' => 'Schedule archived successfully!',
            'icon' => 'success',
        ]);

        $this->showModal = false;
    }

    public function openArchivedRecords(): void
    {
        $this->resetPage('archivedSchedulesPage');
        $this->showArchivedModal = true;
    }

    public function restore(int $scheduleId): void
    {
        $this->getScopedSchedule($scheduleId, withTrashed: true)->restore();
        Ui::toast(text: 'Schedule restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $scheduleId): void
    {
        $schedule = $this->getScopedSchedule($scheduleId, withTrashed: true);
        abort_unless($schedule->trashed(), 409, 'Only archived schedules can be permanently deleted.');
        $schedule->forceDelete();
        Ui::toast(text: 'Schedule permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }

    private function getScopedSchedule(int $scheduleId, bool $withTrashed = false): Schedule
    {
        $query = Schedule::query()
            ->when($withTrashed, fn ($query) => $query->withTrashed());

        if (auth()->user()->isAdmin()) {
            $query->whereHas('request', function ($requestQuery) {
                $requestQuery->withTrashed()->whereHas('facility', function ($facilityQuery) {
                    $facilityQuery->whereHas('assignedAdmins', function ($adminQuery) {
                        $adminQuery->where('users.id', auth()->id());
                    });
                });
            });
        }

        return $query->findOrFail($scheduleId);
    }

    private function getScopedRequest(int $requestId): Requests
    {
        $query = Requests::query()
            ->with('facility:FID,Facility_Name');

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facility', function ($facilityQuery) {
                $facilityQuery->whereHas('assignedAdmins', function ($adminQuery) {
                    $adminQuery->where('users.id', auth()->id());
                });
            });
        }

        return $query->findOrFail($requestId);
    }

    // ---- Computed ----
    #[Computed]
    public function archivedSchedules()
    {
        $query = Schedule::query()->onlyTrashed();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('request', function ($requestQuery) {
                $requestQuery->withTrashed()->whereHas('facility.assignedAdmins', function ($facilityQuery) {
                    $facilityQuery->where('users.id', auth()->id());
                });
            });
        }

        return $query->with([
            'request' => fn ($requestQuery) => $requestQuery
                ->withTrashed()
                ->select(['RID', 'Facility_ID', 'Purpose'])
                ->with('facility:FID,Facility_Name'),
        ])
            ->orderByDesc('deleted_at')
            ->paginate(8, pageName: 'archivedSchedulesPage');
    }

    #[Computed]
    public function facilitiesList()
    {
        return Facilities::query()
            ->when(auth()->user()->isAdmin(), function ($query) {
                $query->whereHas('assignedAdmins', function ($adminQuery) {
                    $adminQuery->where('users.id', auth()->id());
                });
            })
            ->orderBy('Facility_Name')
            ->get(['FID', 'Facility_Name']);
    }

    #[Computed]
    public function requestsList()
    {
        if (! $this->showModal) {
            return collect();
        }

        $query = Requests::with('facility:FID,Facility_Name')
            ->select(['RID', 'Facility_ID', 'Purpose'])
            ->when(auth()->user()->isAdmin(), function ($query) {
                $query->whereHas('facility', function ($facilityQuery) {
                    $facilityQuery->whereHas('assignedAdmins', function ($adminQuery) {
                        $adminQuery->where('users.id', auth()->id());
                    });
                });
            })
            ->orderByDesc('RID');

        $query->where(function ($query) {
            $query->whereDoesntHave('schedules');

            if ($this->Request_ID) {
                $query->orWhere('RID', $this->Request_ID);
            }
        });

        return $query->get();
    }

    #[Computed]
    public function scheduleStats(): array
    {
        $events = collect($this->calendarEvents);
        $now = Carbon::now();

        return [
            'total' => $events->count(),
            'booked' => $events->where('extendedProps.status', 'Booked')->count(),
            'blocked' => $events->where('extendedProps.status', 'Blocked')->count(),
            'upcoming' => $events->filter(fn ($event) => Carbon::parse($event['start'])->greaterThanOrEqualTo($now))->count(),
            'facilities' => $this->facilitiesList->count(),
        ];
    }

    /**
     * Returns filtered FullCalendar events.
     */
    #[Computed]
    public function calendarEvents(): array
    {
        return Schedule::query()
            ->with([
                'request:RID,Event_ID,Facility_ID,User_ID,Is_Guest_Booking,Guest_Name,Purpose,Status',
                'request.facility:FID,Facility_Name',
                'request.event:EID,Event_Title',
                'request.user:id,name',
            ])
            ->when(auth()->user()->isAdmin(), function ($query) {
                $query->whereHas('request.facility', function ($facilityQuery) {
                    $facilityQuery->whereHas('assignedAdmins', function ($adminQuery) {
                        $adminQuery->where('users.id', auth()->id());
                    });
                });
            })
            ->when($this->facilityFilter, function ($query) {
                $query->whereHas('request.facility', function ($requestQuery) {
                    $requestQuery->where('Facility_ID', $this->facilityFilter);
                });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->whereHas('request', function ($requestQuery) {
                        $requestQuery->where('Purpose', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('request.facility', function ($facilityQuery) {
                        $facilityQuery->where('Facility_Name', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->orderBy('Date')
            ->orderBy('Start_Time')
            ->get()
            ->map(function ($schedule) {
                $date = Carbon::parse($schedule->Date)->toDateString();
                $start = Carbon::parse($schedule->Start_Time)->format('H:i:s');
                $rawEnd = substr((string) $schedule->getRawOriginal('End_Time'), 0, 5);
                $endsAtMidnight = $rawEnd === '24:00';
                $endDate = $endsAtMidnight ? Carbon::parse($schedule->Date)->addDay()->toDateString() : $date;
                $end = $endsAtMidnight ? '00:00:00' : Carbon::parse($schedule->End_Time)->format('H:i:s');

                $facility = $schedule->request?->facility?->Facility_Name
                    ?? 'Request #' . $schedule->Request_ID;

                $eventName = $schedule->request?->event?->Event_Title ?? 'Reserved facility';
                $colors = CalendarColor::forValue($facility);
                $isEnded = $schedule->request?->Status === 'Ended';
                $isBlocked = $schedule->Status === 'Blocked';

                return [
                    'id' => $schedule->SID,
                    'title' => ($isBlocked ? 'Blocked' : $eventName).' · '.$facility,
                    'start' => "{$date}T{$start}",
                    'end' => "{$endDate}T{$end}",
                    'backgroundColor' => $isEnded
                        ? '#dc2626'
                        : ($schedule->Status === 'Booked' ? $colors['backgroundColor'] : '#9ca3af'),
                    'borderColor' => $isEnded
                        ? '#991b1b'
                        : ($schedule->Status === 'Booked' ? $colors['borderColor'] : '#6b7280'),
                    'extendedProps' => [
                        'status' => $isEnded ? 'Ended' : $schedule->Status,
                        'scheduleId' => $schedule->SID,
                        'facility' => $facility,
                        'event' => $eventName,
                        'purpose' => $schedule->request?->Purpose,
                        'requester' => $schedule->request?->requesterName(),
                    ],
                ];
            })
            ->values()
            ->toArray();
    }
}; 
?>

<div
    wire:ignore.self
    x-data="scheduleCalendar(@js($this->calendarEvents), @js($view), $wire)"
    x-init="initCalendar()"
    class="min-w-0 w-full max-w-full"
>
    @include('schedule.components.calendar-assets')
    @include('schedule.components.page-header')
    @include('schedule.components.calendar')
    @if ($showArchivedModal)
        @include('schedule.components.archived-schedules-modal')
    @endif
    @if ($showModal)
        @include('schedule.components.schedule-form-modal')
    @endif
</div>

@include('schedule.components.calendar-styles')
