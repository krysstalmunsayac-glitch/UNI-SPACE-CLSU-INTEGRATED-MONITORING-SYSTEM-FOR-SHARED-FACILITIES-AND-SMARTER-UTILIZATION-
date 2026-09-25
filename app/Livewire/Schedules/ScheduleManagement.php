<?php

namespace App\Livewire\Schedules;

use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Actions\Schedules\UpdateSchedule;
use App\Livewire\Forms\ScheduleForm;
use App\Livewire\Queries\ScheduleListQuery;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Services\BookingPolicy;
use App\Services\FacilityAvailabilityService;
use App\Support\Ui;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ScheduleManagement extends Component
{
    use WithPagination;

    // ---- View state ----
    #[Url]
    public string $view = 'weekly'; // weekly | monthly

    public $editingId = null;

    public bool $showModal = false;

    public bool $showArchivedModal = false;

    public bool $scheduleReadOnly = false;

    public string $scheduleReadOnlyReason = '';

    public ?string $selectedDate = null;

    public $noticeDays = 3;

    // ---- Filters ----
    public string $searchInput = '';

    public string $search = '';

    public ?int $facilityFilter = null;

    // ---- Form fields ----
    public ScheduleForm $form;

    public function mount(): void
    {
        $this->form->Date = Carbon::now()->toDateString();
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

    public function updatedFormStartTime(string $value): void
    {
        try {
            $endMinutes = $this->timeToMinutes($value) + FacilityAvailabilityService::MINIMUM_MINUTES;

            if ($endMinutes <= $this->timeToMinutes(FacilityAvailabilityService::CLOSES_AT)) {
                $this->form->End_Time = $this->formatMinutes($endMinutes);
            }
        } catch (\Throwable) {
            // Validation will present a useful message for malformed values.
        }
    }

    // ---- CRUD ----
    public function resetForm(): void
    {
        $this->form->reset();
        $this->form->Date = $this->selectedDate ?? Carbon::now()->toDateString();
        $this->form->Start_Time = '08:00';
        $this->form->End_Time = '09:00';
        $this->form->Status = 'Booked';
        $this->editingId = null;
        $this->scheduleReadOnly = false;
        $this->scheduleReadOnlyReason = '';
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

        if ($this->isReadOnlySchedule($schedule)) {
            $this->scheduleReadOnly = true;
            $this->scheduleReadOnlyReason = $this->readOnlyReason($schedule);
            Ui::toast(text: $this->scheduleReadOnlyReason, variant: 'warning');

            return;
        }

        $this->form->earliestDate = app(BookingPolicy::class)->earliestDate(auth()->user());
        $this->form->noticeMessage = app(BookingPolicy::class)->noticeMessage(auth()->user());
        $validated = $this->form->validate();

        app(BookingPolicy::class)->validateFutureStart($validated['Date'], $validated['Start_Time'], 'form.Start_Time');

        if ((int) $validated['Request_ID'] !== (int) $schedule->Request_ID) {
            $this->form->Request_ID = (int) $schedule->Request_ID;
            $this->addError('form.Request_ID', 'The request assigned to a schedule cannot be changed.');

            return;
        }
        $request = $this->getScopedRequest($validated['Request_ID']);
        app(UpdateSchedule::class)->handle($schedule, $request, $validated);

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
            'Date' => 'date',
            'Start_Time' => 'start time',
            'End_Time' => 'end time',
            'Status' => 'status',
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
        $minimum = $this->timeToMinutes($this->form->Start_Time ?: FacilityAvailabilityService::OPENS_AT)
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
        try {
            $schedule = $this->getScopedSchedule($scheduleId);
        } catch (ModelNotFoundException) {
            $this->showModal = false;
            $this->editingId = null;
            $this->dispatch('calendar-refresh', events: $this->calendarEvents);
            Ui::toast(text: 'This schedule is no longer available. The calendar has been refreshed.', variant: 'warning');

            return;
        }

        $this->editingId = $schedule->SID;
        $this->form->Request_ID = $schedule->Request_ID;
        $this->form->Date = Carbon::parse($schedule->Date)->toDateString();
        $this->form->Start_Time = Carbon::parse($schedule->Start_Time)->format('H:i');
        $rawEndTime = substr((string) $schedule->getRawOriginal('End_Time'), 0, 5);
        $this->form->End_Time = $rawEndTime === '24:00' ? '24:00' : Carbon::parse($schedule->End_Time)->format('H:i');
        $this->form->Status = $schedule->Status;
        $this->scheduleReadOnly = $this->isReadOnlySchedule($schedule);
        $this->scheduleReadOnlyReason = $this->scheduleReadOnly ? $this->readOnlyReason($schedule) : '';
        $this->showModal = true;
    }

    public function openArchivedRecords(): void
    {
        $this->resetPage('archivedSchedulesPage');
        $this->showArchivedModal = true;
    }

    public function restore(int $scheduleId): void
    {
        app(RestoreRecord::class)->handle($this->getScopedSchedule($scheduleId, withTrashed: true));
        Ui::toast(text: 'Schedule restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $scheduleId): void
    {
        $schedule = $this->getScopedSchedule($scheduleId, withTrashed: true);
        app(PermanentlyDeleteRecord::class)->handle($schedule);
        Ui::toast(text: 'Schedule permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }

    private function getScopedSchedule(int $scheduleId, bool $withTrashed = false): Schedule
    {
        return app(ScheduleListQuery::class)->schedule(auth()->user(), $scheduleId, $withTrashed);
    }

    private function getScopedRequest(int $requestId): FacilityRequest
    {
        return app(ScheduleListQuery::class)->request(auth()->user(), $requestId);
    }

    private function isReadOnlySchedule(Schedule $schedule): bool
    {
        $request = $schedule->request;

        if (! $request || $request->trashed() || in_array($request->Status, ['Ended', 'Expired', 'Rejected', 'Cancelled'], true)) {
            return true;
        }

        $rawEndTime = substr((string) $schedule->getRawOriginal('End_Time'), 0, 8);
        $endMinutes = $this->timeToMinutes($rawEndTime);
        $endsAt = Carbon::parse($schedule->Date)->startOfDay()->addMinutes($endMinutes);

        return $endsAt->lte(now());
    }

    private function readOnlyReason(Schedule $schedule): string
    {
        return $schedule->request?->Status === 'Ended'
            ? 'Completed schedules are available for reference only and can no longer be changed.'
            : 'Past or archived schedules are available for reference only and can no longer be changed.';
    }

    // ---- Computed ----
    #[Computed]
    public function archivedSchedules()
    {
        return app(ScheduleListQuery::class)->archivedSchedules(auth()->user());
    }

    #[Computed]
    public function facilitiesList()
    {
        return app(ScheduleListQuery::class)->facilities(auth()->user());
    }

    #[Computed]
    public function requestsList()
    {
        if (! $this->showModal) {
            return collect();
        }

        return app(ScheduleListQuery::class)->requests(auth()->user(), $this->form->Request_ID);
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

    #[Computed]
    public function facilityTypeLegend(): array
    {
        return app(ScheduleListQuery::class)->legend($this->facilitiesList);
    }

    /**
     * Returns filtered FullCalendar events.
     */
    #[Computed]
    public function calendarEvents(): array
    {
        return app(ScheduleListQuery::class)->calendarEvents(
            auth()->user(), $this->facilityFilter, $this->search
        );
    }

    public function render(): View
    {
        return view('livewire.schedules.index');
    }
}
