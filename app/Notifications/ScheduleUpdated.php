<?php

namespace App\Notifications;

use App\Models\Requests;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleUpdated extends Notification
{
    use Queueable;

    public function __construct(
        protected Requests $request,
        protected array $oldSchedule,
        protected array $newSchedule,
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your facility reservation schedule has changed')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.schedule-updated', [
                'userName' => $notifiable->name ?? $this->request->requesterName(),
                'requestId' => $this->request->RID,
                'facilityName' => $this->request->facility?->Facility_Name ?? 'N/A',
                'oldSchedule' => $this->oldSchedule,
                'newSchedule' => $this->newSchedule,
                'actionUrl' => $this->request->user ? route('dashboard').'#requests' : route('home'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->RID,
            'facility' => $this->request->facility?->Facility_Name,
            'user_id' => $this->request->User_ID,
            'message' => 'An administrator changed your reservation schedule.',
            'status' => 'Schedule Updated',
            'status_label' => 'Schedule changed',
            'old_schedule' => $this->oldSchedule,
            'new_schedule' => $this->newSchedule,
        ];
    }
}
