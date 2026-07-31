<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRegistrationApproved extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $companyName,
        private readonly string $username
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Elevanix company account is approved')
            ->greeting('Welcome to Elevanix')
            ->line($this->companyName.' has been approved.')
            ->line('Username: '.$this->username)
            ->line('Use the password you created during registration. For security, it is not included in this email.')
            ->action('Sign In', route('login'));
    }
}
