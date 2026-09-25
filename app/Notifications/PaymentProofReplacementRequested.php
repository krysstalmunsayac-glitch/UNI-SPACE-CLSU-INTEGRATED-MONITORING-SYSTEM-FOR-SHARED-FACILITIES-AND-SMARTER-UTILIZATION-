<?php

namespace App\Notifications;

use App\Models\FacilityRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentProofReplacementRequested extends Notification
{
    use Queueable;

    public function __construct(private readonly FacilityRequest $request, private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Please re-upload your payment proof')
            ->line('Your payment proof for '.$this->request->facility?->Facility_Name.' needs to be replaced.')
            ->line('Reason: '.$this->reason)
            ->action('Upload a new proof', route('dashboard', ['request' => $this->request->RID]).'#requests');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'message' => 'Please upload a new payment proof: '.$this->reason,
            'status' => 'Awaiting Payment',
        ];
    }
}
