<?php

namespace App\Livewire\Requests;

use App\Actions\Requests\UpdateRequest;
use App\Livewire\Forms\RequestForm;
use App\Livewire\Queries\RequestListQuery;
use App\Livewire\Requests\Concerns\ManagesRequestLifecycle;
use App\Livewire\Requests\Concerns\ManagesRequestPayments;
use App\Livewire\Requests\Concerns\ManagesRequestReview;
use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Support\Ui;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class RequestManagement extends Component
{
    use ManagesRequestLifecycle;
    use ManagesRequestPayments;
    use ManagesRequestReview;
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
        $this->archiveOnly = auth()->user()->isSuperAdmin() && request()->boolean('archive');
        $this->showArchivedModal = $this->archiveOnly;

        if (! $this->archiveOnly && request()->integer('request')) {
            $this->showRequest(request()->integer('request'));
        }
    }

    public bool $showRejectModal = false;

    public bool $showReviewModal = false;

    public bool $showCancelModal = false;

    public bool $showPaymentModal = false;

    public bool $showPaymentProofReplacementModal = false;

    public ?int $paymentProofReplacementRequestId = null;

    public string $paymentProofReplacementReason = '';

    public bool $showPaymentProofPreview = false;

    public ?string $paymentProofPreviewUrl = null;

    public string $documentPreviewTitle = 'Payment proof';

    public ?int $paymentRequestId = null;

    public string $paymentAmount = '';

    public string $paymentDeadline = '';

    public string $paymentDeadlineMaximum = '';

    public ?int $rejectingId = null;

    public ?int $cancellingId = null;

    public string $adminCancellationReason = '';

    public bool $emailCancellationNotice = true;

    public ?int $reviewingId = null;

    public string $reviewNotes = '';

    public array $rejectionReasons = [];

    public string $otherRejectionReason = '';

    public string $searchInput = '';

    public string $search = '';

    public $sortBy = 'Created_at';

    public $sortDirection = 'asc';

    public string $statusFilter = '';

    public string $archiveStatusFilter = '';

    public string $archiveSortBy = 'deleted_at';

    public string $archiveSortDirection = 'asc';

    public RequestForm $form;

    public ?string $Event_Title = null;

    public ?string $Event_Type = null;

    public ?string $Facility_Name = null;

    public ?string $Facility_Office = null;

    public ?string $Facility_Location = null;

    public ?string $Requester_Name = null;

    public ?string $Requester_Email = null;

    public ?string $Requester_Contact = null;

    public ?string $Requester_Office = null;

    public ?string $Requester_Address = null;

    public bool $Is_Guest_Booking = false;

    public ?string $Guest_Organization = null;

    public ?string $Created_By_Name = null;

    public array $Requested_Amenities = [];

    public array $Purpose_Categories = [];

    public ?string $Other_Purpose = null;

    public ?string $attachmentPath = null;

    public array $View_Daily_Schedules = [];

    public array $Schedule_Changes = [];

    public ?string $Cancellation_Reason = null;

    public ?string $Rejection_Reason = null;

    public ?string $View_Review_Notes = null;

    public ?string $View_Review_Requested_At = null;

    public ?string $Payment_Proof_Path = null;

    public ?string $Payment_Proof_Uploaded_At = null;

    public ?string $Request_Created_At = null;

    public ?string $Request_Updated_At = null;

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

    public function sortArchived(string $column): void
    {
        if (! in_array($column, ['RID', 'requester', 'Proposed_Date', 'Proposed_Start_Time', 'event_type', 'facility', 'Status', 'deleted_at'], true)) {
            return;
        }

        if ($this->archiveSortBy === $column) {
            $this->archiveSortDirection = $this->archiveSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->archiveSortBy = $column;
            $this->archiveSortDirection = 'asc';
        }

        $this->resetPage('archivedRequestsPage');
    }

    public function resetForm(): void
    {
        $this->form->Proposed_Date = now()->addDay()->toDateString();
        $this->form->Proposed_End_Date = $this->form->Proposed_Date;
        $this->form->Proposed_Start_Time = '09:00';
        $this->form->Proposed_End_Time = '10:00';
        $this->form->Status = 'Pending';
        $this->form->Purpose = '';
        $this->form->Request_Details = '';
        $this->form->Capacity = null;
        $this->form->Event_ID = null;
        $this->form->User_ID = null;
        $this->Event_Title = null;
        $this->Event_Type = null;
        $this->Facility_Name = null;
        $this->Facility_Office = null;
        $this->Facility_Location = null;
        $this->Requester_Name = null;
        $this->Requester_Email = null;
        $this->Requester_Contact = null;
        $this->Requester_Office = null;
        $this->Requester_Address = null;
        $this->Is_Guest_Booking = false;
        $this->Guest_Organization = null;
        $this->Created_By_Name = null;
        $this->Requested_Amenities = [];
        $this->Purpose_Categories = [];
        $this->Other_Purpose = null;
        $this->attachmentPath = null;
        $this->View_Daily_Schedules = [];
        $this->Schedule_Changes = [];
        $this->Cancellation_Reason = null;
        $this->Rejection_Reason = null;
        $this->View_Review_Notes = null;
        $this->View_Review_Requested_At = null;
        $this->Payment_Proof_Path = null;
        $this->Payment_Proof_Uploaded_At = null;
        $this->Request_Created_At = null;
        $this->Request_Updated_At = null;
        $this->editingId = null;
        $this->viewingId = null;
        $this->reviewingId = null;
        $this->reviewNotes = '';
        $this->resetValidation();
    }

    public function showRequest(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)
            ->load(['user', 'creator', 'event', 'facility', 'amenities']);

        $this->viewingArchived = false;
        $this->fillRequestDetails($request);
        $this->showViewModal = true;
    }

    public function showArchivedRequest(int $requestId): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = FacilityRequest::query()->onlyTrashed();

        if (auth()->user()->isAdmin()) {
            $query->whereHas('facility.assignedAdmins', fn ($query) => $query->where('users.id', auth()->id()));
        }

        $request = $query->findOrFail($requestId)
            ->load(['user', 'creator', 'event', 'facility', 'amenities']);

        $this->viewingArchived = true;
        $this->fillRequestDetails($request);
        $this->showViewModal = true;
    }

    public function previewPaymentProof(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        abort_unless($request->Payment_Proof_Path, 404);

        $this->documentPreviewTitle = 'Payment proof';
        $this->paymentProofPreviewUrl = route('requests.payment-proof.view', $request);
        $this->showPaymentProofPreview = true;
    }

    public function previewAttachment(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        abort_unless($request->attachment_path, 404);

        $this->documentPreviewTitle = 'Request attachment';
        $this->paymentProofPreviewUrl = route('requests.attachment.view', $request);
        $this->showPaymentProofPreview = true;
    }

    private function fillRequestDetails(FacilityRequest $request): void
    {

        $this->viewingId = $request->RID;
        $this->form->Event_ID = $request->Event_ID;
        $this->form->User_ID = $request->User_ID;
        $this->form->Proposed_Date = $request->Proposed_Date->toDateString();
        $this->form->Proposed_End_Date = ($request->Proposed_End_Date ?? $request->Proposed_Date)->toDateString();
        $this->form->Proposed_Start_Time = $request->Proposed_Start_Time->format('H:i');
        $this->form->Proposed_End_Time = $request->Proposed_End_Time->format('H:i');
        $this->form->Status = $request->Status;
        $this->paymentAmount = $request->Payment_Amount !== null ? (string) $request->Payment_Amount : '';
        $this->paymentDeadline = $request->Payment_Deadline?->format('Y-m-d\TH:i') ?? '';
        $this->form->Purpose = $request->Purpose;
        $this->form->Request_Details = $request->Request_Details ?? $request->event?->Description ?? '';
        $this->form->Capacity = $request->Capacity;
        $this->Purpose_Categories = $request->Purpose_Categories ?? [];
        $this->Other_Purpose = $request->Other_Purpose;
        $this->Event_Title = $request->event?->Event_Title;
        $this->Event_Type = $request->event?->Type_Event;
        $this->Facility_Name = $request->facility?->Facility_Name;
        $this->Facility_Office = $request->facility?->Office;
        $this->Facility_Location = $request->facility?->Location;
        $this->Is_Guest_Booking = $request->Is_Guest_Booking;
        $this->Requester_Name = $request->requesterName();
        $this->Requester_Email = $request->requesterEmail();
        $this->Requester_Contact = $request->Is_Guest_Booking ? $request->Guest_Contact : $request->user?->contact_number;
        $this->Requester_Office = $request->Is_Guest_Booking ? $request->Guest_Organization : $request->user?->office;
        $this->Requester_Address = $request->Is_Guest_Booking ? null : $request->user?->address;
        $this->Guest_Organization = $request->Guest_Organization;
        $this->Created_By_Name = $request->creator?->name;
        $this->attachmentPath = $request->attachment_path;
        $this->View_Daily_Schedules = $request->Daily_Schedules ?? [];
        $this->Schedule_Changes = AuditLog::query()
            ->with('actor')
            ->where('auditable_type', FacilityRequest::class)
            ->where('auditable_id', $request->RID)
            ->where('action', 'schedule_updated')
            ->latest()
            ->get()
            ->map(fn (AuditLog $auditLog): array => [
                'old' => $this->formatScheduleAuditValues($auditLog->old_values),
                'new' => $this->formatScheduleAuditValues($auditLog->new_values),
                'changed_by' => $auditLog->actor?->name ?? 'System',
                'changed_at' => $auditLog->created_at?->format('M j, Y g:i A') ?? 'Unknown time',
            ])
            ->all();
        $this->Cancellation_Reason = $request->Cancellation_Reason;
        $this->Rejection_Reason = $request->Rejection_Reason;
        $this->View_Review_Notes = $request->Review_Notes;
        $this->View_Review_Requested_At = $request->Review_Requested_At?->format('M j, Y g:i A');
        $this->Payment_Proof_Path = $request->Payment_Proof_Path;
        $this->Payment_Proof_Uploaded_At = $request->Payment_Proof_Uploaded_At?->format('M j, Y g:i A');
        $this->Request_Created_At = $request->Created_at?->format('M j, Y g:i A');
        $this->Request_Updated_At = $request->Updated_at?->format('M j, Y g:i A');
        $this->Requested_Amenities = $request->amenities
            ->map(fn (Amenity $amenity) => $amenity->name.' — '.number_format((int) $amenity->pivot->quantity).' units')
            ->values()
            ->all() ?? [];
        $this->Purpose_Categories = $request->Purpose_Categories ?? [];
        $this->Other_Purpose = $request->Other_Purpose;
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array{date: string, start: string, end: string}
     */
    private function formatScheduleAuditValues(?array $values): array
    {
        return [
            'date' => filled($values['Date'] ?? null)
                ? Carbon::parse($values['Date'])->format('M d, Y')
                : 'Not recorded',
            'start' => $this->formatScheduleAuditTime($values['Start_Time'] ?? null),
            'end' => $this->formatScheduleAuditTime($values['End_Time'] ?? null),
        ];
    }

    private function formatScheduleAuditTime(?string $time): string
    {
        $time = substr((string) $time, 0, 5);

        if ($time === '') {
            return 'Not recorded';
        }

        if ($time === '24:00') {
            return '12:00 AM (next day)';
        }

        return Carbon::createFromFormat('H:i', $time)->format('g:i A');
    }

    public function edit(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        if (in_array($request->Status, ['Cancelled', 'Expired'], true)) {
            Ui::toast(text: "{$request->Status} requests are read-only and cannot be edited.", variant: 'warning');

            return;
        }

        $this->editingId = $request->RID;
        $this->form->Event_ID = $request->Event_ID;
        $this->form->User_ID = $request->User_ID;
        $this->form->Proposed_Date = $request->Proposed_Date->toDateString();
        $this->form->Proposed_End_Date = ($request->Proposed_End_Date ?? $request->Proposed_Date)->toDateString();
        $this->form->Proposed_Start_Time = $request->Proposed_Start_Time->format('H:i');
        $this->form->Proposed_End_Time = $request->Proposed_End_Time->format('H:i');
        $this->form->Status = $request->Status;
        $this->form->Purpose = $request->Purpose;
        $this->form->Request_Details = $request->Request_Details ?? $request->event?->Description ?? '';
        $this->form->Capacity = $request->Capacity;
        $this->Purpose_Categories = $request->selectablePurposeCategories();
        $this->Other_Purpose = $request->selectableOtherPurpose();
        $this->attachmentPath = $request->attachment_path;
        $this->showModal = true;
    }

    public function save(UpdateRequest $action): void
    {
        $request = $this->getScopedRequest($this->editingId);
        abort_if(
            in_array($request->Status, ['Cancelled', 'Expired'], true),
            409,
            'Closed requests are read-only and cannot be edited.',
        );

        $this->validate([
            'Purpose_Categories' => ['required', 'array', 'min:1'],
            'Purpose_Categories.*' => ['string', 'distinct', Rule::in(FacilityRequest::PURPOSE_OPTIONS)],
            'Other_Purpose' => [
                'nullable',
                Rule::requiredIf(fn () => in_array('Other', $this->Purpose_Categories, true)),
                'string',
                'min:3',
                'max:150',
            ],
        ], [
            'Purpose_Categories.required' => 'Select at least one purpose of request.',
            'Other_Purpose.required' => 'Describe the other purpose.',
        ]);

        $this->form->Purpose = collect($this->Purpose_Categories)
            ->map(fn (string $category): string => $category === 'Other'
                ? trim((string) $this->Other_Purpose)
                : $category)
            ->implode(', ');

        $this->form->validate();

        $forcedRejection = $action->handle($request, [
            'Event_ID' => $this->form->Event_ID,
            'User_ID' => $this->form->User_ID,
            'Proposed_Date' => $this->form->Proposed_Date,
            'Proposed_End_Date' => $this->form->Proposed_End_Date,
            'Proposed_Start_Time' => $this->form->Proposed_Start_Time,
            'Proposed_End_Time' => $this->form->Proposed_End_Time,
            'Status' => $this->form->Status,
            'Purpose' => $this->form->Purpose,
            'Request_Details' => $this->form->Request_Details,
            'Purpose_Categories' => $this->Purpose_Categories,
            'Other_Purpose' => $this->Other_Purpose,
            'Capacity' => $this->form->Capacity,
        ]);

        if ($forcedRejection) {
            $this->form->Status = 'Rejected';
            Ui::toast(
                text: 'This request conflicts with an earlier request for the same facility, date, and time. It was marked as rejected.',
                variant: 'warning'
            );
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

    private function getScopedRequest(int $requestId): FacilityRequest
    {
        return app(RequestListQuery::class)->findVisible(auth()->user(), $requestId);
    }

    #[Computed]
    public function archivedRequests()
    {
        return app(RequestListQuery::class)->archived(
            auth()->user(),
            $this->search,
            $this->archiveStatusFilter,
            $this->archiveSortBy,
            $this->archiveSortDirection,
        );
    }

    #[Computed]
    public function requests()
    {
        return app(RequestListQuery::class)->active(
            auth()->user(),
            $this->search,
            $this->statusFilter,
            $this->sortBy,
            $this->sortDirection,
        );
    }

    #[Computed]
    public function requestStats(): array
    {
        return app(RequestListQuery::class)->stats(auth()->user());
    }

    #[Computed]
    public function users()
    {
        if (! $this->showModal) {
            return collect();
        }

        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    public function render(): View
    {
        return view('livewire.requests.index');
    }
}
