<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestFeedbackRequested extends Notification
{
    use Queueable;

    public function __construct(protected Requests $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'message' => 'Your event has ended. Please rate the facility and share your feedback.',
            'status' => 'Ended',
            'action_url' => route('facility-feedback.create', $this->request),
        ];
    }
}
