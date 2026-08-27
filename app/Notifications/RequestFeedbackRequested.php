<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestFeedbackRequested extends Notification
{
    use Queueable;

    public function __construct(protected Requests $request) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requestReference = 'REQ-'.str_pad((string) $this->request->RID, 5, '0', STR_PAD_LEFT);

        return (new MailMessage)
            ->subject("Your event for {$requestReference} has ended")
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('Your event has ended. You may optionally share feedback about the facility and your experience.')
            ->line('Facility: '.($this->request->facility?->Facility_Name ?? 'N/A'))
            ->action('Rate your experience', route('facility-feedback.create', $this->request))
            ->line('Thank you for helping us improve our shared facilities.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'proposed_date' => $this->request->Proposed_Date?->format('M d, Y'),
            'proposed_end_date' => $this->request->Proposed_End_Date?->format('M d, Y'),
            'facility' => $this->request->facility?->Facility_Name,
            'message' => 'Your event has ended. You may optionally rate the facility and share feedback.',
            'status' => 'Ended',
            'action_url' => route('facility-feedback.create', $this->request),
        ];
    }
}
