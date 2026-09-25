<?php

namespace App\Notifications;

use App\Models\FacilityRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentProofUploaded extends Notification
{
    use Queueable;

    public function __construct(private readonly FacilityRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment proof uploaded for request #'.$this->request->RID)
            ->line($this->request->requesterName().' uploaded a payment proof for '.$this->request->facility?->Facility_Name.'.')
            ->action('Review payment proof', route('requests.index', ['request' => $this->request->RID]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'message' => 'A payment proof was uploaded and is ready for review.',
            'status' => 'Awaiting Payment',
        ];
    }
}
