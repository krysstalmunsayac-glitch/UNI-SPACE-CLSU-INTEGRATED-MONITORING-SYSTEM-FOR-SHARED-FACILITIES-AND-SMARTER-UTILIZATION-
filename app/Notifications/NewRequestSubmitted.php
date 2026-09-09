<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRequestSubmitted extends Notification
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
            ->subject('New facility request submitted')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.new-request-submitted', [
                'adminName' => $notifiable->name ?? 'there',
                'requestId' => $this->request->RID,
                'requesterName' => $this->request->requesterName(),
                'requesterEmail' => $this->request->requesterEmail(),
                'createdBy' => $this->request->creator?->name,
                'facilityName' => $this->request->facility?->Facility_Name ?? 'N/A',
                'proposedDate' => $this->request->Proposed_Date?->format('F j, Y') ?? 'N/A',
                'startTime' => $this->request->Proposed_Start_Time?->format('H:i') ?? 'N/A',
                'endTime' => $this->request->Proposed_End_Time?->format('H:i') ?? 'N/A',
                'expectedCapacity' => $this->request->Capacity ?? 'N/A',
                'purpose' => $this->request->Purpose,
                'status' => $this->request->Status,
                'actionUrl' => route('Request'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'proposed_date' => $this->request->Proposed_Date?->format('M d, Y'),
            'proposed_end_date' => $this->request->Proposed_End_Date?->format('M d, Y'),
            'facility' => $this->request->facility?->Facility_Name,
            'amenities' => $this->request->facility?->amenities
                ->pluck('name')
                ->unique()
                ->implode(', '),
            'user_id' => $this->request->User_ID,
            'user_name' => $this->request->requesterName(),
            'is_guest_booking' => $this->request->Is_Guest_Booking,
            'created_by' => $this->request->creator?->name,
            'message' => $this->request->Is_Guest_Booking
                ? 'A guest facility request has been submitted by an administrator.'
                : 'A new facility request has been submitted.',
            'status' => $this->request->Status,
        ];
    }
}
