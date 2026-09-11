<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

class RequestAwaitingPayment extends Notification
{
    use Queueable;

    public function __construct(protected Requests $request) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment required for your SIEL SPACE request')
            ->markdown('emails.request-awaiting-payment', [
                'userName' => $notifiable->name ?? 'there',
                'request' => $this->request,
                'actionUrl' => route('dashboard', ['request' => $this->request->RID]).'#requests',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'message' => 'Payment is required to continue processing your facility request.',
            'status' => 'Awaiting Payment',
            'status_label' => 'Awaiting Payment',
            'amount' => (float) $this->request->Payment_Amount,
            'deadline' => $this->request->Payment_Deadline?->toIso8601String(),
        ];
    }
}
