<?php

namespace App\Livewire\Requests;

use App\Actions\Lifecycle\ArchiveRecord;
use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Actions\Requests\ApproveRequest;
use App\Actions\Requests\CancelRequest;
use App\Actions\Requests\RejectRequest;
use App\Actions\Requests\UpdateRequest;
use App\Livewire\Forms\RequestForm;
use App\Livewire\Queries\RequestListQuery;
use App\Models\Amenity;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\RequestWorkflowService;
use App\Support\Ui;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class RequestManagement extends Component
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

    public ?int $paymentRequestId = null;

    public string $paymentAmount = '';

    public string $paymentDeadline = '';

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

    public $sortBy = 'priority';

    public $sortDirection = 'asc';

    public string $statusFilter = '';

    public string $archiveStatusFilter = '';

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

    public function resetForm(): void
    {
        $this->form->Proposed_Date = now()->addDay()->toDateString();
        $this->form->Proposed_End_Date = $this->form->Proposed_Date;
        $this->form->Proposed_Start_Time = '09:00';
        $this->form->Proposed_End_Time = '10:00';
        $this->form->Status = 'Pending';
        $this->form->Purpose = '';
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
        $this->form->Capacity = $request->Capacity;
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

    public function edit(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        if ($request->Status === 'Cancelled') {
            Ui::toast(text: 'Cancelled requests are read-only and cannot be edited.', variant: 'warning');

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
        $this->form->Capacity = $request->Capacity;
        $this->attachmentPath = $request->attachment_path;
        $this->showModal = true;
    }

    public function approve(int $requestId, ApproveRequest $action): void
    {
        $request = $this->getScopedRequest($requestId)->load(['facility', 'user']);

        if (! $request->canTransitionTo('Approved')) {
            Ui::toast(text: 'This request cannot be approved from its current status.', variant: 'warning');

            return;
        }

        if (! $request->Proposed_Date->isAfter(today())) {
            Ui::toast(text: 'Outdated booking requests cannot be approved.', variant: 'warning');

            return;
        }

        $result = $action->handle($request);

        if ($result === null) {
            Ui::toast(text: 'This request can no longer be approved or conflicts with an approved booking.', variant: 'warning');

            return;
        }

        $rejectedCount = $result['rejected']->count();
        Ui::toast(
            text: $rejectedCount > 0
                ? "Request approved; {$rejectedCount} conflicting pending request(s) were automatically rejected and notified."
                : 'Request approved successfully!',
            variant: 'success',
        );
        $this->dispatch('swal', [
            'title' => 'Request approved',
            'text' => $rejectedCount > 0
                ? "The request was approved and {$rejectedCount} conflicting pending request(s) were automatically rejected."
                : 'The request was approved and added to the facility schedule.',
            'icon' => 'success',
        ]);
        $this->showViewModal = false;
    }

    public function openRejectModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        if (! $request->canTransitionTo('Rejected')) {
            Ui::toast(text: 'This request can no longer be rejected.', variant: 'warning');

            return;
        }

        $this->rejectingId = $request->RID;
        $this->rejectionReasons = [];
        $this->otherRejectionReason = '';
        $this->resetValidation();
        $this->showRejectModal = true;
    }

    public function openPaymentModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)->load('facility');

        if (! $request->canTransitionTo('Awaiting Payment') || (float) ($request->facility?->Price ?? 0) <= 0) {
            Ui::toast(text: 'Awaiting Payment is only available for pending requests with a rental fee.', variant: 'warning');

            return;
        }

        $this->paymentRequestId = $request->RID;
        $this->paymentAmount = number_format((float) $request->facility->Price, 2, '.', '');
        $this->paymentDeadline = now()->addDays(3)->format('Y-m-d\TH:i');
        $this->resetValidation(['paymentAmount', 'paymentDeadline']);
        $this->showPaymentModal = true;
    }

    public function requestPayment(RequestWorkflowService $workflow): void
    {
        $validated = $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'paymentDeadline' => ['required', 'date', 'after:now'],
        ]);

        $request = $this->getScopedRequest($this->paymentRequestId)->load(['facility', 'user']);
        $workflow->requestPayment($request, $validated);

        Ui::toast(text: 'Payment instructions sent to the requester.', variant: 'success');
        $this->showPaymentModal = false;
        $this->showViewModal = false;
        $this->paymentRequestId = null;
    }

    public function reject(RejectRequest $action): void
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
        $reasons = collect($this->rejectionReasons)
            ->reject(fn (string $reason) => $reason === 'Other')
            ->when(
                in_array('Other', $this->rejectionReasons, true),
                fn ($reasons) => $reasons->push('Other: '.trim($this->otherRejectionReason))
            )
            ->implode('; ');

        $action->handle($request, $reasons);

        Ui::toast(text: 'Request rejected successfully.', variant: 'success');
        $this->dispatch('swal', [
            'title' => 'Request rejected',
            'text' => 'The request was rejected successfully and the requester was notified.',
            'icon' => 'success',
        ]);
        $this->showRejectModal = false;
        $this->showViewModal = false;
        $this->rejectingId = null;
    }

    public function openCancelModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        if ($request->Status !== 'Approved') {
            Ui::toast(text: 'Only approved requests can be cancelled.', variant: 'warning');

            return;
        }

        $this->cancellingId = $request->RID;
        $this->adminCancellationReason = '';
        $this->emailCancellationNotice = true;
        $this->resetValidation(['adminCancellationReason']);
        $this->showCancelModal = true;
    }

    public function confirmCancellation(CancelRequest $action): void
    {
        $this->validate([
            'adminCancellationReason' => ['required', 'string', 'min:5', 'max:500'],
            'emailCancellationNotice' => ['boolean'],
        ], [
            'adminCancellationReason.required' => 'Enter the reason for cancelling this approved request.',
            'adminCancellationReason.min' => 'Please provide a little more detail (at least 5 characters).',
            'adminCancellationReason.max' => 'Keep the cancellation reason within 500 characters.',
        ]);

        $request = $this->getScopedRequest($this->cancellingId)->load('user');

        if ($request->Status !== 'Approved') {
            $this->showCancelModal = false;
            Ui::toast(text: 'Only approved requests can be cancelled.', variant: 'warning');

            return;
        }

        $action->handle($request, $this->adminCancellationReason, $this->emailCancellationNotice);

        Ui::toast(text: 'Approved request cancelled and its schedule removed.', variant: 'success');
        $this->dispatch('swal', [
            'title' => 'Request cancelled',
            'text' => $this->emailCancellationNotice
                ? 'The request was cancelled, its schedule was released, and the requester was emailed.'
                : 'The request was cancelled and its schedule was released without sending an email.',
            'icon' => 'success',
        ]);
        $this->showCancelModal = false;
        $this->showViewModal = false;
        $this->cancellingId = null;
    }

    public function openReviewModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)
            ->load(['user', 'creator', 'event', 'facility', 'amenities']);

        if ($request->Is_Guest_Booking) {
            Ui::toast(text: 'Guest requests can be edited directly by an administrator instead of being returned for revision.', variant: 'warning');

            return;
        }

        if (! $request->canBeReviewed()) {
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

    public function requestRevision(RequestWorkflowService $workflow): void
    {
        $this->validate([
            'reviewNotes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $request = $this->getScopedRequest($this->reviewingId)
            ->load(['user', 'facility']);

        $workflow->requestRevision($request, $this->reviewNotes);

        Ui::toast(text: 'Review message sent. The user can update the same request.', variant: 'success');
        $this->dispatch('swal', [
            'title' => 'Revision requested',
            'text' => 'The user can now update and resubmit the same request.',
            'icon' => 'success',
        ]);
        $this->showReviewModal = false;
        $this->showViewModal = false;
        $this->reviewingId = null;
        $this->reviewNotes = '';
    }

    public function save(UpdateRequest $action): void
    {
        $request = $this->getScopedRequest($this->editingId);
        abort_if(
            $request->Status === 'Cancelled',
            409,
            'Cancelled requests are read-only and cannot be edited.',
        );

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

    public function delete(int $requestId): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        app(ArchiveRecord::class)->handle($this->getScopedRequest($requestId));
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
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->showArchivedModal = true;
    }

    public function restore(int $requestId): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        app(RestoreRecord::class)->handle(FacilityRequest::onlyTrashed()->findOrFail($requestId));
        Ui::toast(text: 'Request restored successfully!', variant: 'success');
        $this->dispatch('swal', [
            'title' => 'Request restored',
            'text' => 'The request is available in Request Management again.',
            'icon' => 'success',
        ]);
        $this->dispatch('$refresh');
    }

    public function forceDelete(int $requestId): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        app(PermanentlyDeleteRecord::class)->handle(FacilityRequest::onlyTrashed()->findOrFail($requestId));
        Ui::toast(text: 'Request permanently deleted.', variant: 'success');
        $this->dispatch('swal', [
            'title' => 'Request permanently deleted',
            'text' => 'This request can no longer be restored.',
            'icon' => 'success',
        ]);
        $this->dispatch('$refresh');
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
