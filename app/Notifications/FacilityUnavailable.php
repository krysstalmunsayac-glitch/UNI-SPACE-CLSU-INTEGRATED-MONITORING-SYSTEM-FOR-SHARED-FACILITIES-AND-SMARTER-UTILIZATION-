<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacilityUnavailable extends Notification
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
            ->subject('Facility unavailable — your reservation was cancelled')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.facility-unavailable', [
                'userName' => $notifiable->name ?? 'there',
                'requestId' => $this->request->RID,
                'facilityName' => $this->request->facility?->Facility_Name ?? 'N/A',
                'proposedDate' => $this->request->Proposed_Date?->format('F j, Y') ?? 'N/A',
                'startTime' => $this->request->Proposed_Start_Time?->format('H:i') ?? 'N/A',
                'endTime' => $this->request->Proposed_End_Time?->format('H:i') ?? 'N/A',
                'actionUrl' => route('waiting.list'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'proposed_date' => $this->request->Proposed_Date?->format('M d, Y'),
            'proposed_end_date' => $this->request->Proposed_End_Date?->format('M d, Y'),
            'facility' => $this->request->facility?->Facility_Name,
            'user_id' => $this->request->User_ID,
            'message' => 'Your request was cancelled because the facility is unavailable.',
            'status' => 'Cancelled',
            'cancellation_reason' => $this->request->Cancellation_Reason,
        ];
    }
}
