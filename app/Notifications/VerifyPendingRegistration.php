<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyPendingRegistration extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $pin,
        private readonly string $token,
    ) {}

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
            ->subject('Your SIEL SPACE verification PIN')
            ->greeting('Welcome!')
            ->line('Enter this one-time PIN on the SIEL SPACE verification page to finish creating your account:')
            ->line("**{$this->pin}**")
            ->action('Return to verification', route('registration.pin', $this->token))
            ->line('This PIN expires in 10 minutes and can only be used once.')
            ->line('If the PIN expires, return to the verification page and request a new one.')
            ->line('If you did not create an account, no further action is required.');
    }
}
