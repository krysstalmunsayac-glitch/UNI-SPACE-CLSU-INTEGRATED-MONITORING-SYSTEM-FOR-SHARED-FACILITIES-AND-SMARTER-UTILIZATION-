<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestNeedsRevision extends Notification
{
    use Queueable;

    public function __construct(protected Requests $request) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action required: please update your facility request')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.request-needs-revision', [
                'userName' => $notifiable->name ?? 'there',
                'requestId' => $this->request->RID,
                'facilityName' => $this->request->facility?->Facility_Name ?? 'N/A',
                'reviewNotes' => $this->request->Review_Notes,
                'actionUrl' => route('dashboard').'#requests',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'user_id' => $this->request->User_ID,
            'message' => 'Your facility request needs additional information.',
            'status' => 'Needs Revision',
            'status_label' => 'Review requested',
            'review_notes' => $this->request->Review_Notes,
        ];
    }
}
