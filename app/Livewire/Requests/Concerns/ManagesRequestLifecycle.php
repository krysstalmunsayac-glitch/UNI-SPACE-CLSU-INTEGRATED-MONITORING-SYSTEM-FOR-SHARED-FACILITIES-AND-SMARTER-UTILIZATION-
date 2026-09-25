<?php

namespace App\Livewire\Requests\Concerns;

use App\Actions\Lifecycle\ArchiveRecord;
use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Actions\Lifecycle\RestoreRecord;
use App\Actions\Requests\ApproveRequest;
use App\Actions\Requests\CancelRequest;
use App\Models\FacilityRequest;
use App\Support\Ui;

trait ManagesRequestLifecycle
{
    public function approve(int $requestId, ApproveRequest $action): void
    {
        $request = $this->getScopedRequest($requestId)->load(['facility', 'user']);

        if (! $request->canTransitionTo('Approved')) {
            Ui::toast(text: 'This request cannot be approved from its current status.', variant: 'warning');

            return;
        }

        if ($request->scheduledEndAt()?->lte(now())) {
            FacilityRequest::markPastRequestsAsEnded();
            Ui::toast(text: 'This request has expired because its scheduled event time has already passed.', variant: 'warning');

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
}
