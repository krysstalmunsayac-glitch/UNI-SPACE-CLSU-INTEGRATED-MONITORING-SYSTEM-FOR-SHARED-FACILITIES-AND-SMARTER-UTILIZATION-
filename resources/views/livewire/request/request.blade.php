<?php

use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\RequestNeedsRevision;
use App\Notifications\RequestStatusUpdated;
use App\Support\Ui;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public $editingId = null;

    public $viewingId = null;

    public bool $showModal = false;

    public bool $showViewModal = false;

    public bool $viewingArchived = false;

    public bool $showArchivedModal = false;

    public bool $archiveOnly = false;

    public function mount(): void
    {
        Requests::markPastRequestsAsEnded();
        $this->archiveOnly = request()->boolean('archive');
        $this->showArchivedModal = $this->archiveOnly;

        if (! $this->archiveOnly && request()->integer('request')) {
            $this->showRequest(request()->integer('request'));
        }
    }

    public bool $showRejectModal = false;

    public bool $showReviewModal = false;

    public ?int $rejectingId = null;

    public ?int $reviewingId = null;

    public string $reviewNotes = '';

    public array $rejectionReasons = [];

    public string $otherRejectionReason = '';

    public string $searchInput = '';

    public string $search = '';

    public $sortBy = 'RID';

    public $sortDirection = 'asc';

    public string $statusFilter = '';

    public string $archiveStatusFilter = '';

    #[Validate('nullable|integer')]
    public ?int $Event_ID = null;

    #[Validate('nullable|integer')]
    public ?int $User_ID = null;

    #[Validate('required|date|after:today')]
    public string $Proposed_Date = '';

    #[Validate('required|date|after_or_equal:Proposed_Date')]
    public string $Proposed_End_Date = '';

    #[Validate('required|date_format:H:i')]
    public string $Proposed_Start_Time = '';

    #[Validate('required|date_format:H:i|after:Proposed_Start_Time')]
    public string $Proposed_End_Time = '';

    #[Validate('required|in:Pending,Approved,Rejected,Cancelled')]
    public string $Status = 'Pending';

    #[Validate('required|string|min:5|max:1000')]
    public string $Purpose = '';

    #[Validate('nullable|integer|min:1|max:100000')]
    public ?int $Capacity = null;

    public ?string $Event_Title = null;

    public ?string $Event_Type = null;

    public ?string $Facility_Name = null;

    public ?string $Requester_Name = null;

    public ?string $Requester_Email = null;

    public ?string $Requester_Contact = null;

    public ?string $Requester_Office = null;

    public array $Requested_Amenities = [];

    public array $Purpose_Categories = [];

    public ?string $Other_Purpose = null;

    public ?string $Reservation_Frequency = null;

    public ?string $Facility_Importance = null;

    public ?string $Requirements_Fit = null;

    public ?string $Reserve_Again_Intent = null;

    public ?string $attachmentPath = null;

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage('requestsPage');
        $this->resetPage('archivedRequestsPage');
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage('requestsPage');
    }

    public function updatedArchiveStatusFilter(): void
    {
        $this->resetPage('archivedRequestsPage');
    }

    public function sort($column): void
    {
        if (! in_array($column, ['RID', 'User_ID', 'Proposed_Date', 'Status'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('requestsPage');
    }

    public function resetForm(): void
    {
        $this->Proposed_Date = now()->addDay()->toDateString();
        $this->Proposed_End_Date = $this->Proposed_Date;
        $this->Proposed_Start_Time = '09:00';
        $this->Proposed_End_Time = '10:00';
        $this->Status = 'Pending';
        $this->Purpose = '';
        $this->Capacity = null;
        $this->Event_ID = null;
        $this->User_ID = null;
        $this->Event_Title = null;
        $this->Event_Type = null;
        $this->Facility_Name = null;
        $this->Requester_Name = null;
        $this->Requester_Email = null;
        $this->Requester_Contact = null;
        $this->Requester_Office = null;
        $this->Requested_Amenities = [];
        $this->Purpose_Categories = [];
        $this->Other_Purpose = null;
        $this->Reservation_Frequency = null;
        $this->Facility_Importance = null;
        $this->Requirements_Fit = null;
        $this->Reserve_Again_Intent = null;
        $this->attachmentPath = null;
        $this->editingId = null;
        $this->viewingId = null;
        $this->reviewingId = null;
        $this->reviewNotes = '';
        $this->resetValidation();
    }

    public function showRequest(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)
            ->load(['user', 'event', 'facility.amenities']);

        $this->viewingArchived = false;
        $this->fillRequestDetails($request);
        $this->showViewModal = true;
    }

    public function showArchivedRequest(int $requestId): void
    {
        $query = Requests::query()->onlyTrashed();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', auth()->id()));
        }

        $request = $query->findOrFail($requestId)
            ->load(['user', 'event', 'facility.amenities']);

        $this->viewingArchived = true;
        $this->fillRequestDetails($request);
        $this->showViewModal = true;
    }

    private function fillRequestDetails(Requests $request): void
    {

        $this->viewingId = $request->RID;
        $this->Event_ID = $request->Event_ID;
        $this->User_ID = $request->User_ID;
        $this->Proposed_Date = $request->Proposed_Date->toDateString();
        $this->Proposed_End_Date = ($request->Proposed_End_Date ?? $request->Proposed_Date)->toDateString();
        $this->Proposed_Start_Time = $request->Proposed_Start_Time->format('H:i');
        $this->Proposed_End_Time = $request->Proposed_End_Time->format('H:i');
        $this->Status = $request->Status;
        $this->Purpose = $request->Purpose;
        $this->Capacity = $request->Capacity;
        $this->Event_Title = $request->event?->Event_Title;
        $this->Event_Type = $request->event?->Type_Event;
        $this->Facility_Name = $request->facility?->Facility_Name;
        $this->Requester_Name = $request->user?->name;
        $this->Requester_Email = $request->user?->email;
        $this->Requester_Contact = $request->user?->contact_number;
        $this->Requester_Office = $request->user?->office;
        $this->attachmentPath = $request->attachment_path;
        $this->Requested_Amenities = $request->facility?->amenities
            ->pluck('name')
            ->filter()
            ->values()
            ->all() ?? [];
        $this->Purpose_Categories = $request->Purpose_Categories ?? [];
        $this->Other_Purpose = $request->Other_Purpose;
        $this->Reservation_Frequency = $request->Reservation_Frequency;
        $this->Facility_Importance = $request->Facility_Importance;
        $this->Requirements_Fit = $request->Requirements_Fit;
        $this->Reserve_Again_Intent = $request->Reserve_Again_Intent;

    }

    public function edit(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        $this->editingId = $request->RID;
        $this->Event_ID = $request->Event_ID;
        $this->User_ID = $request->User_ID;
        $this->Proposed_Date = $request->Proposed_Date->toDateString();
        $this->Proposed_End_Date = ($request->Proposed_End_Date ?? $request->Proposed_Date)->toDateString();
        $this->Proposed_Start_Time = $request->Proposed_Start_Time->format('H:i');
        $this->Proposed_End_Time = $request->Proposed_End_Time->format('H:i');
        $this->Status = $request->Status;
        $this->Purpose = $request->Purpose;
        $this->Capacity = $request->Capacity;
        $this->attachmentPath = $request->attachment_path;
        $this->showModal = true;
    }

    public function approve(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)->load(['facility', 'user']);

        if (in_array($request->Status, ['Approved', 'Cancelled', 'Ended'], true)) {
            Ui::toast(text: 'This request cannot be approved from its current status.', variant: 'warning');

            return;
        }

        if (! $request->Proposed_Date->isAfter(today())) {
            Ui::toast(text: 'Outdated booking requests cannot be approved.', variant: 'warning');

            return;
        }

        $dailySchedules = $request->Daily_Schedules ?? [[
            'date' => $request->Proposed_Date->toDateString(),
            'start' => $request->Proposed_Start_Time->format('H:i'),
            'end' => $request->Proposed_End_Time->format('H:i'),
        ]];

        $result = DB::transaction(function () use ($request, $dailySchedules): ?array {
            if ($request->Facility_ID) {
                Facilities::query()->whereKey($request->Facility_ID)->lockForUpdate()->firstOrFail();
            }

            $request = Requests::query()->whereKey($request->RID)->lockForUpdate()->firstOrFail();

            if ($request->Status !== 'Pending') {
                return null;
            }

            if (! $request->Proposed_Date->isAfter(today())) {
                return null;
            }

            if ($request->Facility_ID && Requests::hasActiveDailyScheduleConflict(
                $request->Facility_ID,
                $dailySchedules,
                $request->RID,
                true,
            )) {
                return null;
            }

            $rejectedRequests = $request->Facility_ID
                ? Requests::dailyScheduleConflicts(
                    $request->Facility_ID,
                    $dailySchedules,
                    $request->RID,
                    true,
                    ['Pending'],
                )
                : collect();

            $request->update([
                'Status' => 'Approved',
                'Rejection_Reason' => null,
                'Review_Notes' => null,
                'Review_Requested_At' => null,
            ]);

            $this->createScheduleFromRequest($request);

            foreach ($rejectedRequests as $conflictingRequest) {
                $conflictingRequest->schedules()->delete();
                $conflictingRequest->update([
                    'Status' => 'Rejected',
                    'Rejection_Reason' => "Schedule conflict: request #{$request->RID} was approved for the same facility and time.",
                    'Review_Notes' => null,
                    'Review_Requested_At' => null,
                ]);
            }

            return [
                'approved' => $request->load(['facility', 'user']),
                'rejected' => $rejectedRequests->each->load(['facility', 'user']),
            ];
        }, 3);

        if ($result === null) {
            Ui::toast(text: 'This request can no longer be approved or conflicts with an approved booking.', variant: 'warning');

            return;
        }

        if ($result['approved']->user) {
            Notification::send($result['approved']->user, new RequestStatusUpdated($result['approved'], 'Pending'));
        }

        foreach ($result['rejected'] as $rejectedRequest) {
            if ($rejectedRequest->user) {
                Notification::send($rejectedRequest->user, new RequestStatusUpdated($rejectedRequest, 'Pending'));
            }
        }

        $rejectedCount = $result['rejected']->count();
        Ui::toast(
            text: $rejectedCount > 0
                ? "Request approved; {$rejectedCount} conflicting pending request(s) were automatically rejected and notified."
                : 'Request approved successfully!',
            variant: 'success',
        );
        $this->showViewModal = false;
    }

    public function openRejectModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        if (in_array($request->Status, ['Approved', 'Cancelled', 'Ended'], true)) {
            Ui::toast(text: 'This request can no longer be rejected.', variant: 'warning');

            return;
        }

        $this->rejectingId = $request->RID;
        $this->rejectionReasons = [];
        $this->otherRejectionReason = '';
        $this->resetValidation();
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        $allowedReasons = [
            'Schedule conflict',
            'Facility unavailable',
            'Incomplete request information',
            'Capacity exceeds facility limit',
            'Does not meet facility policies',
            'Other',
        ];

        $this->validate([
            'rejectionReasons' => ['required', 'array', 'min:1'],
            'rejectionReasons.*' => ['string', 'in:'.implode(',', $allowedReasons)],
            'otherRejectionReason' => ['nullable', 'string', 'max:500'],
        ]);

        if (in_array('Other', $this->rejectionReasons, true)) {
            $this->validate([
                'otherRejectionReason' => ['required', 'string', 'max:500'],
            ]);
        }

        $request = $this->getScopedRequest($this->rejectingId);
        abort_if(in_array($request->Status, ['Approved', 'Cancelled', 'Ended'], true), 409);

        $reasons = collect($this->rejectionReasons)
            ->reject(fn (string $reason) => $reason === 'Other')
            ->when(
                in_array('Other', $this->rejectionReasons, true),
                fn ($reasons) => $reasons->push('Other: '.trim($this->otherRejectionReason))
            )
            ->implode('; ');

        $previousStatus = $request->Status;
        $request->schedules()->delete();
        $request->update([
            'Status' => 'Rejected',
            'Rejection_Reason' => $reasons,
            'Review_Notes' => null,
            'Review_Requested_At' => null,
        ]);

        if ($request->user) {
            Notification::send($request->user, new RequestStatusUpdated($request, $previousStatus));
        }

        Ui::toast(text: 'Request rejected successfully.', variant: 'success');
        $this->showRejectModal = false;
        $this->showViewModal = false;
        $this->rejectingId = null;
    }

    public function cancel(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)->load('user');

        if ($request->Status !== 'Approved') {
            Ui::toast(text: 'Only approved requests can be cancelled.', variant: 'warning');

            return;
        }

        $previousStatus = $request->Status;
        $request->schedules()->delete();
        $request->update([
            'Status' => 'Cancelled',
            'Cancellation_Reason' => 'Cancelled by an administrator.',
        ]);

        if ($request->user) {
            Notification::send($request->user, new RequestStatusUpdated($request, $previousStatus));
        }

        Ui::toast(text: 'Approved request cancelled and its schedule removed.', variant: 'success');
        $this->showViewModal = false;
    }

    public function openReviewModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)
            ->load(['user', 'event', 'facility.amenities']);

        if (in_array($request->Status, ['Approved', 'Cancelled', 'Ended'], true)) {
            Ui::toast(text: 'This request can no longer be returned for revision.', variant: 'warning');

            return;
        }

        $this->fillRequestDetails($request);
        $this->showViewModal = false;
        $this->reviewingId = $request->RID;
        $this->reviewNotes = $request->Review_Notes ?? '';
        $this->resetValidation();
        $this->showReviewModal = true;
    }

    public function requestRevision(): void
    {
        $this->validate([
            'reviewNotes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $request = $this->getScopedRequest($this->reviewingId)
            ->load(['user', 'facility']);

        abort_if(in_array($request->Status, ['Approved', 'Cancelled'], true), 409);

        $request->schedules()->delete();
        $request->update([
            'Status' => 'Pending',
            'Review_Notes' => trim($this->reviewNotes),
            'Review_Requested_At' => now(),
            'Rejection_Reason' => null,
        ]);

        if ($request->user) {
            Notification::send($request->user, new RequestNeedsRevision($request));
        }

        Ui::toast(text: 'Review message sent. The user can update the same request.', variant: 'success');
        $this->showReviewModal = false;
        $this->showViewModal = false;
        $this->reviewingId = null;
        $this->reviewNotes = '';
    }

    public function save(): void
    {
        $this->validate();

        $request = $this->getScopedRequest($this->editingId);
        $previousStatus = $request->Status;
        $wasApproved = $request->Status === 'Approved';
        $facilityId = $request->facility?->FID;

        if (
            $this->Status === 'Approved'
            && $facilityId
            && Requests::activeFacilityConflicts(
                $facilityId,
                $this->Proposed_Date,
                $this->Proposed_End_Date,
                $this->Proposed_Start_Time,
                $this->Proposed_End_Time,
                $request->RID,
            )
                ->where('RID', '<', $request->RID)
                ->exists()
        ) {
            $this->Status = 'Rejected';

            Ui::toast(
                text: 'This request conflicts with an earlier request for the same facility, date, and time. It was marked as rejected.',
                variant: 'warning'
            );
        }

        $request->update([
            'Event_ID' => $this->Event_ID,
            'User_ID' => $this->User_ID,
            'Proposed_Date' => $this->Proposed_Date,
            'Proposed_End_Date' => $this->Proposed_End_Date,
            'Proposed_Start_Time' => $this->Proposed_Start_Time,
            'Proposed_End_Time' => $this->Proposed_End_Time,
            'Daily_Schedules' => collect(CarbonPeriod::create($this->Proposed_Date, $this->Proposed_End_Date))
                ->map(fn ($date) => [
                    'date' => $date->toDateString(),
                    'start' => $this->Proposed_Start_Time,
                    'end' => $this->Proposed_End_Time,
                ])->values()->all(),
            'Status' => $this->Status,
            'Purpose' => $this->Purpose,
            'Capacity' => $this->Capacity,
        ]);

        if ($this->Status !== $previousStatus && $request->user) {
            Notification::send($request->user, new RequestStatusUpdated($request, $previousStatus));
        }

        if ($this->Status === 'Cancelled' && $previousStatus !== 'Cancelled') {
            $this->handleCancelledRequest($request);
        }

        // Auto-create a schedule entry the moment a request becomes Approved.
        // Guarded so it only fires on the transition into Approved, not on
        // every re-save of an already-approved request.
        if ($this->Status === 'Approved' && ! $wasApproved) {
            $this->createScheduleFromRequest($request);
        }

        Ui::toast(text: 'Request updated successfully!', variant: 'success');
        $this->dispatch(
            'swal',
            [
                'title' => 'Request updated',
                'text' => 'Request updated successfully!',
                'icon' => 'success',
            ]
        );

        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Create the matching Schedule row when a request is approved.
     * Mirrors the duplicate/overlap guards used on the Schedule page itself.
     */
    protected function createScheduleFromRequest(Requests $request): void
    {
        $facilityId = $request->facility?->FID;
        $request->schedules()->delete();

        $dailySchedules = $request->Daily_Schedules ?: collect(CarbonPeriod::create($request->Proposed_Date, $request->Proposed_End_Date ?? $request->Proposed_Date))
            ->map(fn ($date) => [
                'date' => $date->toDateString(),
                'start' => $request->Proposed_Start_Time->format('H:i'),
                'end' => $request->Proposed_End_Time->format('H:i'),
            ])->all();

        foreach ($dailySchedules as $dailySchedule) {
            $overlaps = $facilityId && Schedule::whereDate('Date', $dailySchedule['date'])
                ->whereHas('request.facility', fn ($q) => $q->where('FID', $facilityId))
                ->where(function ($q) use ($dailySchedule) {
                    $q->where('Start_Time', '<', $dailySchedule['end'])
                        ->where('End_Time', '>', $dailySchedule['start']);
                })
                ->exists();

            if ($overlaps) {
                continue;
            }

            Schedule::create([
                'Request_ID' => $request->RID,
                'Date' => $dailySchedule['date'],
                'Start_Time' => $dailySchedule['start'],
                'End_Time' => $dailySchedule['end'],
                'Status' => 'Booked',
            ]);
        }

        Ui::toast(text: 'Schedule automatically created from the approved request!', variant: 'success');
    }

    protected function handleCancelledRequest(Requests $request): void
    {
        $request->schedules()->delete();

        Ui::toast(text: 'Request cancelled. It will be archived automatically after 10 days.', variant: 'success');
    }

    public function delete(int $requestId): void
    {
        $this->getScopedRequest($requestId)->delete();
        Ui::toast(text: 'Request archived successfully!', variant: 'success');
        $this->dispatch(
            'swal',
            [
                'title' => 'Request archived',
                'text' => 'Request archived successfully!',
                'icon' => 'success',
            ]
        );
    }

    public function openArchivedRecords(): void
    {
        $this->showArchivedModal = true;
    }

    public function restore(int $requestId): void
    {
        Requests::withTrashed()->findOrFail($requestId)->restore();
        Ui::toast(text: 'Request restored successfully!', variant: 'success');
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $requestId): void
    {
        Requests::withTrashed()->findOrFail($requestId)->forceDelete();
        Ui::toast(text: 'Request permanently deleted.', variant: 'danger');
        $this->dispatch('$refresh');
    }

    private function getScopedRequest(int $requestId): Requests
    {
        $query = Requests::query();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', auth()->id())
            );
        }

        return $query->findOrFail($requestId);
    }

    #[Computed]
    public function archivedRequests()
    {
        $query = Requests::query()->onlyTrashed();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', auth()->id())
            );
        }

        if (in_array($this->archiveStatusFilter, ['Cancelled', 'Approved', 'Rejected', 'Ended'], true)) {
            $query->where('Status', $this->archiveStatusFilter);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($query) use ($term) {
                $query->where('RID', 'like', $term)
                    ->orWhere('Purpose', 'like', $term)
                    ->orWhere('Status', 'like', $term)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $term))
                    ->orWhereHas('facility', fn ($facilityQuery) => $facilityQuery->where('Facility_Name', 'like', $term));
            });
        }

        return $query->with([
            'user:id,name,email',
            'facility:FID,Facility_Name',
        ])
            ->orderByDesc('deleted_at')
            ->paginate(8, pageName: 'archivedRequestsPage');
    }

    #[Computed]
    public function requests()
    {
        return Requests::query()
            ->with([
                'user:id,name,email',
                'facility:FID,Facility_Name',
            ])
            ->when(auth()->user()->isAdmin(), fn ($query) => $query->whereHas('facility.assignedAdmins', fn ($facilityQuery) => $facilityQuery->where('users.id', auth()->id())
            ))
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('Purpose', 'like', "%{$this->search}%")
                    ->orWhere('Status', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter === 'Needs Revision', fn ($query) => $query
                ->where('Status', 'Pending')
                ->whereNotNull('Review_Requested_At'))
            ->when(
                $this->statusFilter && $this->statusFilter !== 'Needs Revision',
                fn ($query) => $query->where('Status', $this->statusFilter)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->orderBy('RID', $this->sortDirection)
            ->paginate(8, pageName: 'requestsPage');
    }

    #[Computed]
    public function requestStats(): array
    {
        $query = Requests::query()
            ->when(auth()->user()->isAdmin(), fn ($query) => $query->whereHas(
                'facility.assignedAdmins',
                fn ($facilityQuery) => $facilityQuery->where('users.id', auth()->id()),
            ));

        $stats = $query
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN Status = 'Pending' AND Review_Requested_At IS NULL THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN Status = 'Pending' AND Review_Requested_At IS NOT NULL THEN 1 ELSE 0 END) as revisions")
            ->selectRaw("SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'revisions' => (int) $stats->revisions,
            'approved' => (int) $stats->approved,
            'rejected' => (int) $stats->rejected,
        ];
    }

    #[Computed]
    public function users()
    {
        if (! $this->showModal) {
            return collect();
        }

        return User::query()->orderBy('name')->get(['id', 'name']);
    }
}; ?>

<div class="w-full">
    @if ($archiveOnly)
        <div class="mx-auto max-w-7xl">
            <x-ui::card>
                @include('request.components.archived-requests-modal', ['archiveOnly' => true])
            </x-ui::card>
        </div>
        @if ($showViewModal)
            @include('request.components.request-view-modal')
        @endif
    @else
    @include('request.components.page-header')
    @include('request.components.requests-table')
    @if ($showArchivedModal)
        <x-ui::modal wire:model.self="showArchivedModal" class="w-[95vw] max-w-7xl">
            @include('request.components.archived-requests-modal')
        </x-ui::modal>
    @endif
    @if ($showViewModal)
        @include('request.components.request-view-modal')
    @endif
    @if ($showReviewModal)
        @include('request.components.request-review-modal')
    @endif
    @if ($showRejectModal)
        @include('request.components.request-reject-modal')
    @endif
    @if ($showModal)
        @include('request.components.request-edit-modal')
    @endif
    @endif
</div>
