<?php

namespace App\Livewire\Requests\Concerns;

use App\Services\RequestWorkflowService;
use App\Support\Ui;

trait ManagesRequestPayments
{
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
}
