<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Url;
use Flux\Flux;
use App\Models\Facilities;
use App\Models\Schedule;
use App\Models\Requests;
use App\Support\CalendarColor;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {

    // ---- View state ----
    #[Url]
    public string $view = 'weekly'; // weekly | monthly

    public $editingId = null;
    public bool $showModal = false;
    public bool $showArchivedModal = false;

    public ?string $selectedDate = null;

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
        $this->Date = Carbon::now()->toDateString();
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
        $this->dispatch('calendar-refresh', events: $this->calendarEvents);
    }

    public function updatedSearch(): void
    {
        $this->dispatch('calendar-refresh', events: $this->calendarEvents);
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

    public function create(?string $date = null): void
    {
        $this->selectedDate = $date;
        $this->resetForm();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

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

        if ($this->editingId) {
            Schedule::findOrFail($this->editingId)->update($validated);

            $this->dispatch('calendar-refresh', events: $this->calendarEvents);

            Flux::toast(
                text: 'Schedule updated successfully!',
                variant: 'success'
            );

            $this->dispatch('swal', [
                'title' => 'Schedule updated',
                'text' => 'Schedule updated successfully!',
                'icon' => 'success',
            ]);
        } else {
            Schedule::create($validated);

            $this->dispatch('calendar-refresh', events: $this->calendarEvents);

            Flux::toast(
                text: 'Schedule created successfully!',
                variant: 'success'
            );

            $this->dispatch('swal', [
                'title' => 'Schedule created',
                'text' => 'Schedule created successfully!',
                'icon' => 'success',
            ]);
        }

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

    public function edit(int $scheduleId): void
    {
        $schedule = $this->getScopedSchedule($scheduleId);

        $this->editingId  = $schedule->SID;
        $this->Request_ID = $schedule->Request_ID;
        $this->Date       = Carbon::parse($schedule->Date)->toDateString();
        $this->Start_Time = Carbon::parse($schedule->Start_Time)->format('H:i');
        $this->End_Time   = Carbon::parse($schedule->End_Time)->format('H:i');
        $this->Status     = $schedule->Status;
        $this->showModal  = true;
    }

    public function delete(int $scheduleId): void
    {
        $this->getScopedSchedule($scheduleId)->delete();

        $this->dispatch('calendar-refresh', events: $this->calendarEvents);

        Flux::toast(
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
        $this->showArchivedModal = true;
    }

    public function restore(int $scheduleId): void
    {
        Schedule::withTrashed()->findOrFail($scheduleId)->restore();
        Flux::toast(text: 'Schedule restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $scheduleId): void
    {
        Schedule::withTrashed()->findOrFail($scheduleId)->forceDelete();
        Flux::toast(text: 'Schedule permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }

    private function getScopedSchedule(int $scheduleId)
    {
        $query = Schedule::query()
            ->with('request.facility');

        if (auth()->user()->isAdmin()) {
            $query->whereHas('request.facility', function ($facilityQuery) {
                $facilityQuery->whereHas('assignedAdmins', function ($adminQuery) {
                    $adminQuery->where('users.id', auth()->id());
                });
            });
        }

        return $query->findOrFail($scheduleId);
    }

    private function getScopedRequest(int $requestId): Requests
    {
        $query = Requests::query()
            ->with('facility');

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

        return $query->with(['request' => fn ($requestQuery) => $requestQuery->withTrashed()->with('facility')])
            ->orderByDesc('deleted_at')
            ->paginate(10);
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
            ->get();
    }

    #[Computed]
    public function requestsList()
    {
        $query = Requests::with('facility')
            ->when(auth()->user()->isAdmin(), function ($query) {
                $query->whereHas('facility', function ($facilityQuery) {
                    $facilityQuery->whereHas('assignedAdmins', function ($adminQuery) {
                        $adminQuery->where('users.id', auth()->id());
                    });
                });
            })
            ->orderByDesc('RID');

        if ($this->editingId) {
            $query->where(function ($query) {
                $query->whereDoesntHave('schedule');

                if ($this->Request_ID) {
                    $query->orWhere('RID', $this->Request_ID);
                }
            });
        } else {
            $query->whereDoesntHave('schedule');
        }

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
            ->with('request.facility')
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
                $end = Carbon::parse($schedule->End_Time)->format('H:i:s');

                $facility = $schedule->request?->facility?->Facility_Name
                    ?? 'Request #' . $schedule->Request_ID;

                $purpose = $schedule->request?->Purpose ?? 'No purpose';
                $colors = CalendarColor::forValue($facility);

                return [
                    'id' => $schedule->SID,
                    'title' => $facility,
                    'start' => "{$date}T{$start}",
                    'end' => "{$date}T{$end}",
                    'backgroundColor' => $schedule->Status === 'Booked' ? $colors['backgroundColor'] : '#9ca3af',
                    'borderColor' => $schedule->Status === 'Booked' ? $colors['borderColor'] : '#6b7280',
                    'extendedProps' => [
                        'status' => $schedule->Status,
                        'scheduleId' => $schedule->SID,
                        'facility' => $facility,
                        'purpose' => $purpose,
                    ],
                ];
            })
            ->values()
            ->toArray();
    }
}; 
?>

<div
    x-data="scheduleCalendar(@js($this->calendarEvents), @js($view))"
    x-init="initCalendar()"
    class="w-full"
>
    @include('schedule.components.calendar-assets')
    @include('schedule.components.page-header')
    @include('schedule.components.calendar')
    @include('schedule.components.archived-schedules-modal')
    @include('schedule.components.schedule-form-modal')
</div>

@include('schedule.components.calendar-script')
@include('schedule.components.calendar-styles')
