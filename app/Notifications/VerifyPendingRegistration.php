<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyPendingRegistration extends Notification
{
    use Queueable;

    public function __construct(private readonly string $verificationUrl) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email address')
            ->greeting('Welcome!')
            ->line('Please verify your email address to finish creating your account.')
            ->action('Verify Email Address', $this->verificationUrl)
            ->line('This verification link expires in 60 minutes.')
            ->line('If you did not create an account, no further action is required.');
    }
}
