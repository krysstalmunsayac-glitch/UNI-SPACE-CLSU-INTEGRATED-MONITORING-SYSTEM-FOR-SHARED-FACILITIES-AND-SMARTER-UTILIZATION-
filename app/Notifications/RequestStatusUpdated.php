<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        protected Requests $request,
        protected string $previousStatus = 'Pending',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->buildSubject())
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.request-status-updated', [
                'userName' => $notifiable->name ?? 'there',
                'message' => $this->buildMessage(),
                'requestId' => $this->request->RID,
                'facilityName' => $this->request->facility?->Facility_Name ?? 'N/A',
                'proposedDate' => $this->request->Proposed_Date?->format('F j, Y') ?? 'N/A',
                'startTime' => $this->request->Proposed_Start_Time?->format('H:i') ?? 'N/A',
                'endTime' => $this->request->Proposed_End_Time?->format('H:i') ?? 'N/A',
                'status' => $this->request->Status,
                'rejectionReason' => $this->request->Status === 'Rejected'
                    ? $this->request->Rejection_Reason
                    : null,
                'actionUrl' => route('waiting.list'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'user_id' => $this->request->User_ID,
            'user_name' => $this->request->user?->name,
            'message' => $this->buildMessage(),
            'status' => $this->request->Status,
            'previous_status' => $this->previousStatus,
            'status_label' => $this->resolveStatusLabel(),
            'rejection_reason' => $this->request->Status === 'Rejected'
                ? $this->request->Rejection_Reason
                : null,
        ];
    }

    protected function buildSubject(): string
    {
        return match ($this->request->Status) {
            'Approved' => 'Your facility request has been approved',
            'Rejected' => 'Your facility request has been reviewed',
            'Cancelled' => 'Your facility request has been cancelled',
            default => 'Your facility request status has been updated',
        };
    }

    protected function buildMessage(): string
    {
        return match ($this->request->Status) {
            'Approved' => 'Your request has been approved and is moving forward.',
            'Rejected' => 'Your request has been reviewed and was not approved.',
            'Cancelled' => 'Your request has been cancelled.',
            default => 'Your request status has been updated.',
        };
    }

    protected function resolveStatusLabel(): string
    {
        return match ($this->request->Status) {
            'Approved' => 'Approved',
            'Rejected' => 'Rejected',
            'Cancelled' => 'Cancelled',
            default => 'Updated',
        };
    }
}
