<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $url,
        private readonly string $expiresAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Set up your SIEL SPACE account')
            ->greeting("Hello {$notifiable->name},")
            ->line('You have been invited to SIEL SPACE, the CLSU facility reservation system.')
            ->line('Assigned role: '.$notifiable->roleLabel())
            ->action('Verify email and set password', $this->url)
            ->line("This secure, single-use invitation expires {$this->expiresAt}.")
            ->line('If you were not expecting this invitation, do not open the link and contact the system administrator.');
    }
}
