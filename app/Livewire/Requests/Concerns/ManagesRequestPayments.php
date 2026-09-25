<?php

namespace App\Livewire\Requests\Concerns;

use App\Notifications\PaymentProofReplacementRequested;
use App\Services\RequestWorkflowService;
use App\Support\Ui;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

trait ManagesRequestPayments
{
    public function openPaymentModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId)->load('facility');

        if (! $request->canTransitionTo('Awaiting Payment') || (float) ($request->facility?->Price ?? 0) <= 0) {
            Ui::toast(text: 'Awaiting Payment is only available for pending requests with a rental fee.', variant: 'warning');

            return;
        }

        $eventStart = $request->scheduledStartAt();

        if (! $eventStart || $eventStart->lte(now()->addMinute())) {
            Ui::toast(text: 'Payment cannot be requested because the event is about to start or has already started.', variant: 'warning');

            return;
        }

        $defaultDeadline = now()->addDays(3)->min($eventStart->copy()->subMinute());

        $this->paymentRequestId = $request->RID;
        $this->paymentAmount = number_format((float) $request->facility->Price, 2, '.', '');
        $this->paymentDeadline = $defaultDeadline->format('Y-m-d\TH:i');
        $this->paymentDeadlineMaximum = $eventStart->copy()->subMinute()->format('Y-m-d\TH:i');
        $this->resetValidation(['paymentAmount', 'paymentDeadline', 'paymentRequestId']);
        $this->showPaymentModal = true;
    }

    public function requestPayment(RequestWorkflowService $workflow): void
    {
        $request = $this->getScopedRequest($this->paymentRequestId)->load(['facility', 'user']);
        $eventStart = $request->scheduledStartAt();

        $validated = $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'paymentDeadline' => ['required', 'date', 'after:now', 'before:'.$eventStart?->toDateTimeString()],
        ], [
            'paymentDeadline.before' => 'The payment deadline must be before the event starts.',
        ]);

        $workflow->requestPayment($request, $validated);

        Ui::toast(text: 'Payment instructions sent to the requester.', variant: 'success');
        $this->showPaymentModal = false;
        $this->showViewModal = false;
        $this->paymentRequestId = null;
    }

    public function openPaymentProofReplacementModal(int $requestId): void
    {
        $request = $this->getScopedRequest($requestId);

        if ($request->Status !== 'Awaiting Payment' || ! $request->Payment_Proof_Path) {
            Ui::toast(text: 'Only uploaded payment proofs awaiting review can be replaced.', variant: 'warning');

            return;
        }

        $this->paymentProofReplacementRequestId = $request->RID;
        $this->paymentProofReplacementReason = '';
        $this->resetValidation('paymentProofReplacementReason');
        $this->showPaymentProofReplacementModal = true;
    }

    public function requestPaymentProofReplacement(): void
    {
        $validated = $this->validate([
            'paymentProofReplacementReason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $request = $this->getScopedRequest($this->paymentProofReplacementRequestId)->load(['facility', 'user']);

        if ($request->Status !== 'Awaiting Payment' || ! $request->Payment_Proof_Path) {
            Ui::toast(text: 'This payment proof is no longer available for replacement.', variant: 'warning');
            $this->showPaymentProofReplacementModal = false;

            return;
        }

        Storage::disk('local')->delete($request->Payment_Proof_Path);
        $request->update([
            'Payment_Proof_Path' => null,
            'Payment_Proof_Uploaded_At' => null,
            'Payment_Proof_Replacement_Reason' => trim($validated['paymentProofReplacementReason']),
        ]);

        try {
            if ($request->user) {
                Notification::send($request->user, new PaymentProofReplacementRequested($request, $request->Payment_Proof_Replacement_Reason));
            }
        } catch (\Throwable $exception) {
            Log::warning('Payment proof replacement was requested, but the requester could not be notified.', [
                'request_id' => $request->RID,
                'exception' => $exception,
            ]);
        }

        Ui::toast(text: 'The requester was asked to upload a replacement payment proof.', variant: 'success');
        $this->showPaymentProofReplacementModal = false;
        $this->paymentProofReplacementRequestId = null;
        $this->paymentProofReplacementReason = '';
    }
}
