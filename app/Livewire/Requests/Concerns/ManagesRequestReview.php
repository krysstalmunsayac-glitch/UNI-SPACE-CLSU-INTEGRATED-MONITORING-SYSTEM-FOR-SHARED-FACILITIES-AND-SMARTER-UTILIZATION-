<?php

namespace App\Livewire\Requests\Concerns;

use App\Actions\Requests\RejectRequest;
use App\Services\RequestWorkflowService;
use App\Support\Ui;

trait ManagesRequestReview
{
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
}
